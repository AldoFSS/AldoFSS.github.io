<?php
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

function enviarPushAUsuario(mysqli $connection, int $usuarioId, array $notificacion): bool
{
    try {
        $q = $connection->prepare(
            'SELECT Endpoint, Endpoint_Hash, P256DH, Auth FROM notificaciones_push WHERE ID_Usuario=?'
        );
        $q->bind_param('i', $usuarioId);
        $q->execute();
        return enviarPushASuscripciones($connection, $q->get_result()->fetch_all(MYSQLI_ASSOC), $notificacion);
    } catch (Throwable $error) {
        error_log('No se pudieron consultar las suscripciones push del usuario: ' . (string) $error);
        return false;
    }
}

function enviarPushAAdministradores(mysqli $connection, array $notificacion): bool
{
    try {
        $resultado = $connection->query(
            "SELECT DISTINCT p.Endpoint, p.Endpoint_Hash, p.P256DH, p.Auth
            FROM notificaciones_push p
            INNER JOIN usuarios u ON u.id=p.ID_Usuario
            WHERE u.Cargo='administrador'"
        );
        return enviarPushASuscripciones($connection, $resultado->fetch_all(MYSQLI_ASSOC), $notificacion);
    } catch (Throwable $error) {
        error_log('No se pudieron consultar las suscripciones push administrativas: ' . (string) $error);
        return false;
    }
}

function enviarPushASuscripciones(mysqli $connection, array $suscripciones, array $notificacion): bool
{
    if (!$suscripciones) {
        return false;
    }

    $publicKey = getenv('VAPID_PUBLIC_KEY');
    $privateKey = getenv('VAPID_PRIVATE_KEY');
    $subject = getenv('VAPID_SUBJECT');
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!$publicKey || !$privateKey || !$subject || !is_file($autoload)) {
        error_log('Push no enviado: falta configurar VAPID o instalar las dependencias de Composer.');
        return false;
    }

    try {
        require_once $autoload;
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey
            ]
        ]);
        $payload = json_encode([
            'title' => mb_substr((string) ($notificacion['title'] ?? 'CaféLiz'), 0, 80),
            'body' => mb_substr((string) ($notificacion['body'] ?? ''), 0, 180),
            'url' => (string) ($notificacion['url'] ?? './')
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $enviado = false;
        foreach ($suscripciones as $suscripcion) {
            $endpointHash = $suscripcion['Endpoint_Hash'];
            try {
                $reporte = $webPush->sendOneNotification(
                    Subscription::create([
                        'endpoint' => $suscripcion['Endpoint'],
                        'keys' => ['p256dh' => $suscripcion['P256DH'], 'auth' => $suscripcion['Auth']]
                    ]),
                    $payload,
                    ['TTL' => 60]
                );
                if ($reporte->isSuccess()) {
                    $enviado = true;
                    continue;
                }
                error_log('Push falló para una suscripción: ' . $reporte->getReason());
                if ($reporte->isSubscriptionExpired()) {
                    $q = $connection->prepare('DELETE FROM notificaciones_push WHERE Endpoint_Hash=?');
                    $q->bind_param('s', $endpointHash);
                    $q->execute();
                }
            } catch (Throwable $error) {
                error_log('No se pudo enviar un push: ' . (string) $error);
            }
        }
        return $enviado;
    } catch (Throwable $error) {
        error_log('No se pudo inicializar Web Push: ' . (string) $error);
        return false;
    }
}
