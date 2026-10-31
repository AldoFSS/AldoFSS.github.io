<?php
require_once __DIR__ . '/seguridad.php';
iniciarSesionSegura();
exigirPostLocal();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

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
    $idioma = is_array($data) ? ($data['language'] ?? '') : '';
    if (!is_string($idioma) || !in_array($idioma, ['es', 'en', 'fr'], true)) {
        throw new InvalidArgumentException('El idioma seleccionado no está disponible.');
    }

    require __DIR__ . '/../BD/conexion.php';
    $consulta = $connection->prepare('SELECT contenido FROM menu_traducciones_estaticas WHERE idioma=?');
    $consulta->bind_param('s', $idioma);
    $consulta->execute();
    $fila = $consulta->get_result()->fetch_assoc();
    if (!$fila) {
        respuestaTraduccion(503, 'No se encontró la traducción guardada para este idioma.');
    }

    $traduccion = json_decode($fila['contenido'], true, 32, JSON_THROW_ON_ERROR);
    if (!isset($traduccion['ui'], $traduccion['categories'], $traduccion['products']) ||
        !is_array($traduccion['ui']) || !is_array($traduccion['categories']) ||
        !is_array($traduccion['products'])) {
        throw new RuntimeException('La traducción guardada no tiene un formato válido.');
    }

    if ($idioma === 'es') {
        $categorias = $connection->query('SELECT ID_Cat, Categoria FROM menucategoria ORDER BY ID_Cat');
        while ($categoria = $categorias->fetch_assoc()) {
            $traduccion['categories'][(string) $categoria['ID_Cat']] = $categoria['Categoria'];
        }

        $productos = $connection->query(
            'SELECT id, Product_Nombre, Descripcion FROM menuproductos ORDER BY id'
        );
        while ($producto = $productos->fetch_assoc()) {
            $traduccion['products'][(string) $producto['id']] = [
                'name' => $producto['Product_Nombre'],
                'description' => $producto['Descripcion'] ?: $traduccion['ui']['menuDefaultDescription']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'language' => $idioma,
        'translation' => $traduccion
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $e) {
    respuestaTraduccion(422, $e->getMessage());
} catch (JsonException $e) {
    error_log('Invalid JSON in static menu translation: ' . $e->getMessage());
    respuestaTraduccion(422, 'No se pudo leer la solicitud o la traducción guardada.');
} catch (Throwable $e) {
    error_log('Static menu translation failed: ' . (string) $e);
    respuestaTraduccion(500, 'No se pudo cargar la traducción guardada.');
}
