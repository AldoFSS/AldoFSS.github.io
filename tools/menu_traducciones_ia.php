<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function mostrarAyuda(): void
{
    echo "Generar una revisión local con Ollama:\n";
    echo "  php tools/menu_traducciones_ia.php generar [modelo] [archivo-salida]\n\n";
    echo "Validar el JSON contra el catálogo actual, sin modificar la base:\n";
    echo "  php tools/menu_traducciones_ia.php validar <archivo-json>\n\n";
    echo "Aplicar después de revisar el JSON:\n";
    echo "  php tools/menu_traducciones_ia.php aplicar <archivo-json>\n\n";
    echo "Modelo predeterminado: qwen2.5:3b\n";
    echo "Ollama debe estar activo en http://127.0.0.1:11434.\n";
}

function cargarCatalogo(mysqli $connection): array
{
    $categorias = [];
    $resultado = $connection->query(
        'SELECT ID_Cat, Categoria FROM menucategoria ORDER BY ID_Cat'
    );
    while ($fila = $resultado->fetch_assoc()) {
        $categorias[] = [
            'id' => (int) $fila['ID_Cat'],
            'name' => (string) $fila['Categoria']
        ];
    }

    $productos = [];
    $resultado = $connection->query(
        'SELECT p.id, p.Product_Nombre, COALESCE(p.Descripcion, \'\') AS Descripcion,
            p.id_categoria, c.Categoria
        FROM menuproductos p
        LEFT JOIN menucategoria c ON c.ID_Cat=p.id_categoria
        ORDER BY p.id'
    );
    while ($fila = $resultado->fetch_assoc()) {
        $productos[] = [
            'id' => (int) $fila['id'],
            'name' => (string) $fila['Product_Nombre'],
            'description' => (string) $fila['Descripcion'],
            'categoryId' => (int) $fila['id_categoria'],
            'category' => (string) ($fila['Categoria'] ?? '')
        ];
    }

    return ['categories' => $categorias, 'products' => $productos];
}

function hashCatalogo(array $catalogo): string
{
    return hash('sha256', json_encode(
        $catalogo,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ));
}

function validarMapa(array $mapa, array $ids, string $campo): array
{
    $esperados = array_map('strval', $ids);
    $recibidos = array_map('strval', array_keys($mapa));
    sort($esperados, SORT_STRING);
    sort($recibidos, SORT_STRING);
    if ($esperados !== $recibidos) {
        throw new RuntimeException("La respuesta no contiene exactamente los identificadores de {$campo}.");
    }
    return $mapa;
}

function validarTraduccion(array $traduccion, array $catalogo, string $idioma): array
{
    if (!isset($traduccion['categories'], $traduccion['products']) ||
        !is_array($traduccion['categories']) || !is_array($traduccion['products'])) {
        throw new RuntimeException("La traducción de {$idioma} no tiene el formato esperado.");
    }

    $categorias = validarMapa(
        $traduccion['categories'],
        array_column($catalogo['categories'], 'id'),
        'categorías'
    );
    foreach ($categorias as $nombre) {
        if (!is_string($nombre) || trim($nombre) === '') {
            throw new RuntimeException("La traducción de una categoría en {$idioma} está vacía o no es texto.");
        }
    }

    $idsProducto = array_column($catalogo['products'], 'id');
    $productos = validarMapa($traduccion['products'], $idsProducto, 'productos');
    foreach ($productos as $producto) {
        if (!is_array($producto) || !is_string($producto['name'] ?? null) ||
            trim($producto['name']) === '' || !is_string($producto['description'] ?? null)) {
            throw new RuntimeException("La traducción de un producto en {$idioma} no contiene nombre y descripción válidos.");
        }
    }

    return ['categories' => $categorias, 'products' => $productos];
}

function traducirConOllama(array $catalogo, string $idioma, string $modelo, string $baseUrl): array
{
    $nombresIdioma = ['en' => 'inglés', 'fr' => 'francés'];
    $solicitud = [
        'categories' => $catalogo['categories'],
        'products' => $catalogo['products']
    ];
    $prompt = 'Eres un traductor profesional de menús de cafetería. Traduce TODOS los nombres de categorías, ' .
        'nombres de productos y palabras de las descripciones del español al ' . $nombresIdioma[$idioma] . '. ' .
        'No dejes palabras comunes en español solo porque aparezcan en un nombre de producto. ' .
        'Por ejemplo, traduce Bebidas como Drinks en inglés o Boissons en francés; Desayuno como Breakfast ' .
        'en inglés o Petit-déjeuner en francés; jamón como ham en inglés o jambon en francés. ' .
        'Conserva sin traducir únicamente marcas, nombres propios y referencias culturales mexicanas como ' .
        'Café de Olla, Metate Mocha y Pan de Muerto. ' .
        'Devuelve exclusivamente un objeto JSON con esta forma: ' .
        '{"categories":{"ID":"nombre traducido"},"products":{"ID":{"name":"nombre traducido","description":"descripción traducida"}}}. ' .
        'Incluye exactamente todos los IDs recibidos, sin agregar ni quitar ninguno. ' .
        'Si una descripción de origen está vacía, déjala vacía. No inventes ingredientes, alérgenos, preparación, ' .
        'procedencia, sabor ni beneficios que no estén explícitos en el texto original. ' .
        'Usa nombres naturales para clientes de cafetería. Antes de responder, comprueba que las categorías ' .
        'y descripciones no vacías estén realmente en el idioma de destino; no copies el español salvo nombres culturales. ' .
        'Catálogo JSON: ' .
        json_encode($solicitud, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    $cuerpo = json_encode([
        'model' => $modelo,
        'stream' => false,
        'format' => 'json',
        'keep_alive' => '5m',
        'options' => ['temperature' => 0.1],
        'messages' => [
            ['role' => 'system', 'content' => 'Eres traductor de menús. Responde solo con JSON válido y sigue estrictamente las instrucciones.'],
            ['role' => 'user', 'content' => $prompt]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    $curl = curl_init(rtrim($baseUrl, '/') . '/api/chat');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $cuerpo,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 300
    ]);
    $respuesta = curl_exec($curl);
    $errorCurl = curl_error($curl);
    $estadoHttp = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if (!is_string($respuesta)) {
        throw new RuntimeException(
            'No se pudo conectar con Ollama en esta computadora. Confirma que Ollama esté instalado y abierto: ' .
            $errorCurl
        );
    }
    $resultado = json_decode($respuesta, true);
    if ($estadoHttp < 200 || $estadoHttp >= 300) {
        $detalle = is_array($resultado) ? (string) ($resultado['error'] ?? '') : '';
        if (stripos($detalle, 'not found') !== false) {
            throw new RuntimeException("Ollama no tiene el modelo {$modelo}. Descárgalo con: ollama pull {$modelo}");
        }
        throw new RuntimeException('Ollama respondió HTTP ' . $estadoHttp . ($detalle ? ': ' . $detalle : '.'));
    }
    if (!is_array($resultado) || !is_string($resultado['message']['content'] ?? null)) {
        throw new RuntimeException('Ollama no devolvió el contenido JSON esperado.');
    }
    $traduccion = json_decode($resultado['message']['content'], true, 64, JSON_THROW_ON_ERROR);
    if (!is_array($traduccion)) {
        throw new RuntimeException("Ollama devolvió una traducción inválida para {$idioma}.");
    }

    return validarTraduccion($traduccion, $catalogo, $idioma);
}

function rutaRevisionPredeterminada(): string
{
    $home = getenv('USERPROFILE') ?: getenv('HOME');
    if (!$home) {
        throw new RuntimeException('Indica una ruta de salida porque no se encontró la carpeta personal del usuario.');
    }
    return rtrim($home, "\\/") . DIRECTORY_SEPARATOR . '.cafeliz' .
        DIRECTORY_SEPARATOR . 'menu-traducciones-revision.json';
}

function guardarRevision(string $ruta, array $revision): void
{
    $directorio = dirname($ruta);
    if (!is_dir($directorio) && !mkdir($directorio, 0700, true) && !is_dir($directorio)) {
        throw new RuntimeException("No se pudo crear la carpeta de revisión: {$directorio}");
    }
    $json = json_encode(
        $revision,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . PHP_EOL;
    if (file_put_contents($ruta, $json, LOCK_EX) === false) {
        throw new RuntimeException("No se pudo guardar el archivo de revisión: {$ruta}");
    }
    @chmod($ruta, 0600);
}

function cargarRevisionValidada(string $ruta, array $catalogo): array
{
    if (!is_file($ruta) || !is_readable($ruta)) {
        throw new RuntimeException("No se puede leer el archivo de revisión: {$ruta}");
    }
    $revision = json_decode((string) file_get_contents($ruta), true, 64, JSON_THROW_ON_ERROR);
    if (!is_array($revision) || ($revision['schemaVersion'] ?? null) !== 1 ||
        !is_string($revision['catalogHash'] ?? null) ||
        !hash_equals(hashCatalogo($catalogo), $revision['catalogHash']) ||
        !is_array($revision['translations'] ?? null)) {
        throw new RuntimeException('La revisión no coincide con el catálogo actual o no tiene un formato válido. Genera una nueva.');
    }

    $traducciones = [];
    foreach (['en', 'fr'] as $idioma) {
        if (!is_array($revision['translations'][$idioma] ?? null)) {
            throw new RuntimeException("Falta la traducción revisada de {$idioma}.");
        }
        $traducciones[$idioma] = validarTraduccion($revision['translations'][$idioma], $catalogo, $idioma);
    }

    return $traducciones;
}

function aplicarRevision(mysqli $connection, string $ruta, array $catalogo): void
{
    $traducciones = cargarRevisionValidada($ruta, $catalogo);
    $connection->begin_transaction();
    try {
        $consulta = $connection->prepare(
            'SELECT contenido FROM menu_traducciones_estaticas WHERE idioma=? FOR UPDATE'
        );
        $guardar = $connection->prepare(
            'INSERT INTO menu_traducciones_estaticas (idioma, contenido)
            VALUES (?, ?) ON DUPLICATE KEY UPDATE contenido=VALUES(contenido), actualizado=CURRENT_TIMESTAMP'
        );

        foreach ($traducciones as $idioma => $nuevaTraduccion) {
            $consulta->bind_param('s', $idioma);
            $consulta->execute();
            $fila = $consulta->get_result()->fetch_assoc();
            if (!$fila) {
                throw new RuntimeException("No existe la traducción base de {$idioma}. Aplica primero la migración de traducciones estáticas.");
            }
            $contenido = json_decode($fila['contenido'], true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($contenido) || !is_array($contenido['ui'] ?? null)) {
                throw new RuntimeException("La traducción base de {$idioma} no es válida; no se modificó la base.");
            }
            $contenido['categories'] = $nuevaTraduccion['categories'];
            $contenido['products'] = $nuevaTraduccion['products'];
            $jsonContenido = json_encode(
                $contenido,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
            $guardar->bind_param('ss', $idioma, $jsonContenido);
            $guardar->execute();
        }
        $connection->commit();
    } catch (Throwable $error) {
        $connection->rollback();
        throw $error;
    }
}

if (($argv[1] ?? '') === '' || in_array($argv[1], ['--help', '-h', 'ayuda'], true)) {
    mostrarAyuda();
    exit(0);
}

try {
    $accion = $argv[1];
    if (!in_array($accion, ['generar', 'validar', 'aplicar'], true)) {
        throw new InvalidArgumentException('Acción no válida. Usa "generar", "validar" o "aplicar".');
    }

    if ($accion === 'generar') {
        if ($argc > 4) {
            throw new InvalidArgumentException('Uso: php tools/menu_traducciones_ia.php generar [modelo] [archivo-salida]');
        }
        $modelo = $argv[2] ?? 'qwen2.5:3b';
        $rutaSalida = $argv[3] ?? rutaRevisionPredeterminada();
        if (!preg_match('/^[A-Za-z0-9._:-]{1,100}$/D', $modelo)) {
            throw new InvalidArgumentException('El nombre del modelo contiene caracteres no permitidos.');
        }
        $baseUrl = getenv('OLLAMA_URL') ?: 'http://127.0.0.1:11434';
        $partesUrl = parse_url($baseUrl);
        $hostsLocales = ['localhost', '127.0.0.1', '::1'];
        if (!is_array($partesUrl) || ($partesUrl['scheme'] ?? '') !== 'http' ||
            !in_array($partesUrl['host'] ?? '', $hostsLocales, true) ||
            isset($partesUrl['user']) || isset($partesUrl['pass'])) {
            throw new InvalidArgumentException('OLLAMA_URL debe apuntar a Ollama en localhost; el catálogo no se enviará a servidores remotos.');
        }
    } else {
        if ($argc !== 3) {
            throw new InvalidArgumentException("Uso: php tools/menu_traducciones_ia.php {$accion} <archivo-json>");
        }
        $rutaSalida = $argv[2];
    }

    require __DIR__ . '/../BD/conexion.php';
    $catalogo = cargarCatalogo($connection);

    if ($accion === 'generar') {
        $traducciones = [];
        foreach (['en', 'fr'] as $idioma) {
            fwrite(STDOUT, "Generando traducción {$idioma} con {$modelo}...\n");
            $traducciones[$idioma] = traducirConOllama($catalogo, $idioma, $modelo, $baseUrl);
        }
        $revision = [
            'schemaVersion' => 1,
            'generatedAt' => date(DATE_ATOM),
            'model' => $modelo,
            'catalogHash' => hashCatalogo($catalogo),
            'sourceCatalog' => $catalogo,
            'translations' => $traducciones
        ];
        guardarRevision($rutaSalida, $revision);
        echo "Revisión guardada. Comprueba nombres y descripciones antes de aplicarla:\n{$rutaSalida}\n";
        echo "No se modificó la base de datos. Para aplicar: php tools/menu_traducciones_ia.php aplicar \"{$rutaSalida}\"\n";
    } elseif ($accion === 'validar') {
        cargarRevisionValidada($rutaSalida, $catalogo);
        echo "Revisión válida: coincide con el catálogo actual y contiene traducciones completas de inglés y francés.\n";
        echo "No se modificó la base de datos.\n";
    } else {
        aplicarRevision($connection, $rutaSalida, $catalogo);
        echo "Traducciones en inglés y francés guardadas correctamente; se conservaron los textos de interfaz.\n";
    }

    $connection->close();
} catch (Throwable $error) {
    if (isset($connection) && $connection instanceof mysqli) {
        try {
            $connection->close();
        } catch (Throwable $closeError) {
            error_log((string) $closeError);
        }
    }
    fwrite(STDERR, 'Error: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
