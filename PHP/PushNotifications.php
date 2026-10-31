<?php
require_once __DIR__ . '/seguridad.php';
iniciarSesionSegura();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respuestaPush(int $estado, array $respuesta): void
{
    http_response_code($estado);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && ($_GET['accion'] ?? '') === 'clave') {
    $clave = getenv('VAPID_PUBLIC_KEY');
    if (!$clave || !preg_match('/^[A-Za-z0-9_-]{32,120}$/D', $clave)) {
        respuestaPush(503, ['success' => false, 'message' => 'Las notificaciones push no están configuradas en el servidor.']);
    }
    respuestaPush(200, ['success' => true, 'publicKey' => $clave]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: GET, POST');
    respuestaPush(405, ['success' => false, 'message' => 'Método no permitido.']);
}

exigirPostLocal();
if (empty($_SESSION['ID_Usuario'])) {
    respuestaPush(401, ['success' => false, 'message' => 'Inicia sesión para administrar tus notificaciones.']);
}
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
    respuestaPush(415, ['success' => false, 'message' => 'Se requiere una solicitud JSON.']);
}

try {
    $raw = file_get_contents('php://input', false, null, 0, 8193);
    if (strlen($raw) > 8192) {
        throw new InvalidArgumentException('La solicitud es demasiado grande.');
    }
    $data = json_decode($raw, true, 12, JSON_THROW_ON_ERROR);
    if (!is_array($data) || !in_array($data['action'] ?? '', ['subscribe', 'unsubscribe'], true)) {
        throw new InvalidArgumentException('La acción de notificaciones no es válida.');
    }
    $subscription = $data['subscription'] ?? null;
    if (!is_array($subscription) || !is_string($subscription['endpoint'] ?? null)) {
        throw new InvalidArgumentException('La suscripción push no es válida.');
    }
    $endpoint = $subscription['endpoint'];
    $endpointParts = parse_url($endpoint);
    if (strlen($endpoint) > 2048 || filter_var($endpoint, FILTER_VALIDATE_URL) === false ||
        ($endpointParts['scheme'] ?? '') !== 'https' || empty($endpointParts['host'])) {
        throw new InvalidArgumentException('El endpoint push debe ser una URL HTTPS válida.');
    }
    $endpointHash = hash('sha256', $endpoint);
    require __DIR__ . '/../BD/conexion.php';
    $usuarioId = (int) $_SESSION['ID_Usuario'];

    if ($data['action'] === 'unsubscribe') {
        $q = $connection->prepare('DELETE FROM notificaciones_push WHERE ID_Usuario=? AND Endpoint_Hash=?');
        $q->bind_param('is', $usuarioId, $endpointHash);
        $q->execute();
        $connection->close();
        respuestaPush(200, ['success' => true]);
    }

    $keys = $subscription['keys'] ?? null;
    if (!is_array($keys) || !is_string($keys['p256dh'] ?? null) ||
        !is_string($keys['auth'] ?? null) ||
        !preg_match('/^[A-Za-z0-9_-]{40,140}$/D', $keys['p256dh']) ||
        !preg_match('/^[A-Za-z0-9_-]{16,80}$/D', $keys['auth'])) {
        throw new InvalidArgumentException('Las claves de la suscripción push no son válidas.');
    }
    $q = $connection->prepare(
        'INSERT INTO notificaciones_push (ID_Usuario, Endpoint, Endpoint_Hash, P256DH, Auth)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE ID_Usuario=VALUES(ID_Usuario), Endpoint=VALUES(Endpoint),
            P256DH=VALUES(P256DH), Auth=VALUES(Auth), Actualizada=CURRENT_TIMESTAMP'
    );
    $q->bind_param('issss', $usuarioId, $endpoint, $endpointHash, $keys['p256dh'], $keys['auth']);
    $q->execute();
    $connection->close();
    respuestaPush(201, ['success' => true]);
} catch (InvalidArgumentException $error) {
    respuestaPush(422, ['success' => false, 'message' => $error->getMessage()]);
} catch (JsonException $error) {
    respuestaPush(422, ['success' => false, 'message' => 'El cuerpo JSON no es válido.']);
} catch (Throwable $error) {
    error_log((string) $error);
    respuestaPush(500, ['success' => false, 'message' => 'No se pudo guardar la suscripción. Revisa la migración de notificaciones.']);
}
