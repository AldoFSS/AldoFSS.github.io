<?php
require_once __DIR__ . '/seguridad.php';
iniciarSesionSegura();
exigirPostLocal();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$bloqueoAdquirido = false;
$connection = null;

function respuestaTraduccion($estado, $mensaje)
{
    http_response_code($estado);
    echo json_encode(['success' => false, 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
        throw new InvalidArgumentException('Se requiere una solicitud JSON.');
    }
    $raw = file_get_contents('php://input', false, null, 0, 2049);
    if (strlen($raw) > 2048) {
        throw new InvalidArgumentException('Solicitud demasiado grande.');
    }
    $data = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
    $idiomas = [
        'en' => 'English', 'fr' => 'French', 'de' => 'German', 'it' => 'Italian',
        'pt' => 'Portuguese', 'ja' => 'Japanese', 'zh' => 'Simplified Chinese',
        'ko' => 'Korean', 'nl' => 'Dutch', 'ru' => 'Russian', 'ar' => 'Arabic',
        'hi' => 'Hindi', 'tr' => 'Turkish', 'pl' => 'Polish', 'sv' => 'Swedish',
        'da' => 'Danish', 'no' => 'Norwegian Bokmal', 'fi' => 'Finnish',
        'el' => 'Greek', 'he' => 'Hebrew', 'th' => 'Thai', 'vi' => 'Vietnamese',
        'id' => 'Indonesian', 'ms' => 'Malay', 'uk' => 'Ukrainian', 'cs' => 'Czech',
        'hu' => 'Hungarian', 'ro' => 'Romanian', 'bg' => 'Bulgarian',
        'hr' => 'Croatian', 'sk' => 'Slovak', 'sr' => 'Serbian', 'ca' => 'Catalan',
        'is' => 'Icelandic', 'sw' => 'Swahili', 'fa' => 'Persian', 'bn' => 'Bengali',
        'tl' => 'Filipino', 'et' => 'Estonian', 'lv' => 'Latvian', 'lt' => 'Lithuanian'
    ];
    $idioma = $data['language'] ?? '';
    if (!is_string($idioma) || !isset($idiomas[$idioma])) {
        throw new InvalidArgumentException('El idioma seleccionado no está disponible.');
    }

    $ahora = time();
    $solicitudes = $_SESSION['menu_traducciones'] ?? [];
    if (!is_array($solicitudes)) $solicitudes = [];
    $solicitudes = array_values(array_filter($solicitudes, function ($marca) use ($ahora) {
        return is_int($marca) && $marca > $ahora - 600;
    }));
    if (count($solicitudes) >= 30) {
        respuestaTraduccion(429, 'Espera un momento antes de solicitar más traducciones.');
    }
    $solicitudes[] = $ahora;
    $_SESSION['menu_traducciones'] = $solicitudes;

    $apiKey = getenv('OPENAI_API_KEY');
    if (!$apiKey) {
        respuestaTraduccion(503, 'La traducción automática no está configurada en el servidor.');
    }
    $modelo = getenv('OPENAI_MODEL') ?: 'gpt-4o';
    if (!preg_match('/^[a-zA-Z0-9._-]{1,80}$/D', $modelo)) {
        throw new RuntimeException('La configuración del modelo de traducción no es válida.');
    }

    $ui = [
        'languageLabel' => 'Idioma', 'eyebrow' => 'PREPARADO PARA TI',
        'documentTitle' => 'Menú · Caféliz', 'navToggle' => 'Menú', 'navToggleAria' => 'Abrir navegación',
        'homeNav' => 'Inicio', 'menuNav' => 'Menú', 'reservationsNav' => 'Reservar espacio',
        'loginNav' => 'Sesión',
        'contactLabel' => 'Datos:',
        'contactText' => 'Para reservar, contáctanos por redes sociales o directamente por teléfono.',
        'title' => 'El menú de Caféliz', 'lead' => 'Tu café favorito y algo rico para acompañarlo.',
        'searchLabel' => 'Buscar en el menú', 'searchPlaceholder' => '¿Qué se te antoja hoy?',
        'searchButton' => 'Buscar', 'categoriesLabel' => 'Categorías del menú',
        'allCategories' => 'Todo el menú', 'resultsMany' => '{count} opciones para disfrutar',
        'resultsOne' => '{count} opción para disfrutar', 'madeToOrder' => 'Hecho al momento',
        'add' => 'Agregar', 'added' => 'Agregado', 'loginToOrder' => 'Inicia sesión para pedir',
        'noResultsTitle' => 'No encontramos ese antojo',
        'noResultsDescription' => 'Prueba con otro nombre o explora todo el menú.',
        'seeFullMenu' => 'Ver todo el menú', 'menuDefaultDescription' => 'Preparado al momento, para disfrutar a tu gusto.',
        'baristaNote' => 'El nombre original del producto en español se enviará a barra.',
        'cartKicker' => 'UN MOMENTO PARA DISFRUTAR', 'cartTitle' => 'Tu carrito',
        'estimatedTotal' => 'Total estimado',
        'closeCart' => 'Cerrar carrito', 'cartNote' => 'Un paso más y tu pedido estará listo para registrar.',
        'continueOrder' => 'Continuar con mi pedido', 'addedToCart' => 'Agregado a tu carrito',
        'viewCart' => 'Ver carrito', 'cartEmptyTitle' => 'Tu próximo antojo empieza aquí',
        'cartEmptyDescription' => 'Elige algo del menú y lo guardaremos en tu carrito.',
        'exploreMenu' => 'Explorar el menú', 'removeItem' => 'Eliminar {name}',
        'unitPrice' => '${price} por unidad', 'quantityLabel' => 'Cantidad de {name}',
        'removeOne' => 'Quitar una unidad de {name}', 'addOne' => 'Agregar una unidad de {name}',
        'oneUnit' => '{count} unidad', 'manyUnits' => '{count} unidades',
        'cartOne' => '{count} producto en tu pedido', 'cartMany' => '{count} productos en tu pedido',
        'cartAria' => 'Ver carrito: {count} productos, ${total}',
        'badgeCart' => '{count} en tu carrito', 'orderButton' => 'Registrar pedido · ${total}',
        'emptyCartButton' => 'Vaciar carrito', 'checkoutTitle' => 'Confirmar pedido',
        'nameLabel' => 'Nombre:', 'namePlaceholder' => 'Nombre', 'emailLabel' => 'Correo:',
        'emailPlaceholder' => 'Correo', 'paymentNote' => 'Se registrará tu pedido. El pago se realiza en caja; esta aplicación todavía no procesa tarjetas.',
        'registrationButton' => 'Registrar pedido', 'requestFailed' => 'No se pudo completar la solicitud.',
        'orderRegisteredTitle' => 'Pedido registrado',
        'orderRegistered' => 'Pedido #{orderId} por ${total}. El cobro se realiza en caja.',
        'orderError' => 'Pedido sin confirmar', 'tryAgain' => 'Reintenta sin cambiar el carrito.',
        'offlineTitle' => 'Sin conexión',
        'offlineMessage' => 'Tu carrito está guardado. Conéctate para confirmar el pedido.',
        'checkDetails' => 'Revisa tus datos', 'validNameEmail' => 'Escribe tu nombre y un correo válido.',
        'emptyCartTitle' => 'Carrito vacío', 'addBeforeOrder' => 'Agrega productos antes de registrar un pedido.',
        'storageError' => 'No se pudo guardar', 'storageHelp' => 'Habilita el almacenamiento del navegador.',
        'quantityLimitTitle' => 'Cantidad máxima alcanzada',
        'quantityLimitMessage' => 'Puedes agregar hasta 999 unidades.',
        'cartFullTitle' => 'Tu carrito está lleno',
        'cartFullMessage' => 'Puedes elegir hasta 100 productos diferentes.',
        'productRemoved' => 'Producto eliminado', 'cartCleared' => 'Carrito vacío',
        'cartClearedMessage' => 'Puedes elegir algo más del menú.',
        'translationUnavailable' => 'No se pudo traducir el menú. Se muestra el contenido original en español.'
    ];

    require __DIR__ . '/../BD/conexion.php';
    $nombreBloqueo = 'cafeliz_menu_translation_' . $idioma;
    $bloqueoAdquirido = (int) $connection->query("SELECT GET_LOCK('" . $nombreBloqueo . "', 5)")->fetch_row()[0] === 1;
    if (!$bloqueoAdquirido) {
        respuestaTraduccion(503, 'El servicio de traducción está ocupado. Inténtalo de nuevo.');
    }

    $categoriasQuery = $connection->query('SELECT ID_Cat, Categoria FROM menucategoria ORDER BY ID_Cat');
    $categorias = [];
    while ($fila = $categoriasQuery->fetch_assoc()) {
        $categorias[(string) $fila['ID_Cat']] = $fila['Categoria'];
    }
    $productosQuery = $connection->query('SELECT id, Product_Nombre, Descripcion FROM menuproductos ORDER BY id');
    $productos = [];
    $bytesCatalogo = 0;
    while ($fila = $productosQuery->fetch_assoc()) {
        if (count($productos) >= 100) {
            throw new RuntimeException('El catálogo supera el límite admitido para traducción.');
        }
        $nombre = (string) $fila['Product_Nombre'];
        $descripcion = (string) $fila['Descripcion'];
        if (mb_strlen($nombre) > 255 || mb_strlen($descripcion) > 1200) {
            throw new RuntimeException('Un producto supera el límite de texto para traducción.');
        }
        $bytesCatalogo += strlen($nombre) + strlen($descripcion);
        $productos[(string) $fila['id']] = ['name' => $nombre, 'description' => $descripcion];
    }
    if ($bytesCatalogo > 20000) {
        throw new RuntimeException('El catálogo supera el límite admitido para traducción.');
    }

    $catalogo = ['ui' => $ui, 'categories' => $categorias, 'products' => $productos];
    $huella = hash('sha256', json_encode([
        'version' => 1,
        'model' => $modelo,
        'catalog' => $catalogo
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    $q = $connection->prepare('SELECT respuesta FROM menu_traducciones_cache WHERE idioma=? AND huella=?');
    $q->bind_param('ss', $idioma, $huella);
    $q->execute();
    $cache = $q->get_result()->fetch_assoc();
    if ($cache) {
        echo $cache['respuesta'];
        exit;
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('La extensión cURL de PHP no está disponible.');
    }
    $prompt = 'You are a Mexican specialty-coffee menu translator and cultural editor. Translate all values in the JSON input into ' .
        $idiomas[$idioma] . '. Return only a JSON object with the exact top-level keys ui, categories, products, preserving all keys and IDs. ' .
        'For product names, preserve recognizable Mexican dish/drink names and add a concise natural explanation for visitors when helpful; never use misleading literal translations. ' .
        'Translate descriptions faithfully and naturally. Never invent ingredients, allergens, preparation methods, dietary properties, or tasting notes not present in the source. ' .
        'Keep prices and product IDs out of translated text. Translate UI copy naturally. Input JSON: ' .
        json_encode($catalogo, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $body = json_encode([
        'model' => $modelo,
        'messages' => [
            ['role' => 'system', 'content' => 'Translate food menus accurately. Preserve factual claims and JSON keys. Output valid JSON only.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'response_format' => ['type' => 'json_object'],
        'temperature' => 0.2,
        'max_tokens' => 8000
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    $curl = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => $body
    ]);
    $apiResponse = curl_exec($curl);
    $apiStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    if ($apiResponse === false || $apiStatus < 200 || $apiStatus >= 300) {
        error_log('OpenAI menu translation request failed; HTTP ' . $apiStatus . ($curlError ? ': ' . $curlError : ''));
        respuestaTraduccion(502, 'El servicio de traducción no respondió. El menú original sigue disponible.');
    }
    $apiData = json_decode($apiResponse, true, 16, JSON_THROW_ON_ERROR);
    $contenido = $apiData['choices'][0]['message']['content'] ?? null;
    if (!is_string($contenido)) {
        throw new RuntimeException('La respuesta del servicio de traducción está incompleta.');
    }
    $traduccion = json_decode($contenido, true, 32, JSON_THROW_ON_ERROR);

    if (!isset($traduccion['ui'], $traduccion['categories'], $traduccion['products']) ||
        !is_array($traduccion['ui']) || !is_array($traduccion['categories']) ||
        !is_array($traduccion['products']) ||
        array_diff(array_keys($ui), array_keys($traduccion['ui'])) ||
        array_diff(array_keys($categorias), array_keys($traduccion['categories'])) ||
        array_diff(array_keys($productos), array_keys($traduccion['products']))) {
        throw new RuntimeException('La respuesta del servicio de traducción no coincide con el catálogo.');
    }
    foreach ($ui as $clave => $original) {
        if (!is_string($traduccion['ui'][$clave]) || mb_strlen($traduccion['ui'][$clave]) > 300) {
            throw new RuntimeException('La respuesta contiene texto de interfaz no válido.');
        }
    }
    foreach ($categorias as $id => $original) {
        if (!is_string($traduccion['categories'][$id]) || mb_strlen($traduccion['categories'][$id]) > 100) {
            throw new RuntimeException('La respuesta contiene una categoría no válida.');
        }
    }
    foreach ($productos as $id => $original) {
        $producto = $traduccion['products'][$id] ?? null;
        if (!is_array($producto) || !is_string($producto['name'] ?? null) ||
            !is_string($producto['description'] ?? null) ||
            mb_strlen($producto['name']) > 255 || mb_strlen($producto['description']) > 1200) {
            throw new RuntimeException('La respuesta contiene un producto no válido.');
        }
    }

    $respuesta = json_encode([
        'success' => true,
        'language' => $idioma,
        'fingerprint' => $huella,
        'translation' => $traduccion
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $q = $connection->prepare(
        'INSERT INTO menu_traducciones_cache (idioma, huella, respuesta) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE huella=VALUES(huella), respuesta=VALUES(respuesta), actualizado=CURRENT_TIMESTAMP'
    );
    $q->bind_param('sss', $idioma, $huella, $respuesta);
    $q->execute();
    echo $respuesta;
} catch (InvalidArgumentException $e) {
    respuestaTraduccion(422, $e->getMessage());
} catch (JsonException $e) {
    error_log('Invalid JSON in menu translation service: ' . $e->getMessage());
    respuestaTraduccion(422, 'No se pudo leer la solicitud o la respuesta de traducción.');
} catch (Throwable $e) {
    error_log((string) $e);
    respuestaTraduccion(500, 'No se pudo traducir el menú. El contenido original sigue disponible.');
} finally {
    if ($bloqueoAdquirido && $connection instanceof mysqli) {
        try {
            $connection->query("SELECT RELEASE_LOCK('cafeliz_menu_translation_" . $idioma . "')");
        } catch (Throwable $e) {
            error_log('Could not release menu translation lock: ' . $e->getMessage());
        }
    }
}
