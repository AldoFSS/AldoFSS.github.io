<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$recordatorioEspecifico = null;
if ($argc > 2 || ($argc === 2 && !preg_match('/\A--recordatorio=([1-9][0-9]*)\z/', $argv[1], $argumento))) {
    fwrite(STDERR, "Uso: php procesar_recordatorios_reserva.php [--recordatorio=ID]\n");
    exit(2);
}
if ($argc === 2) {
    $recordatorioEspecifico = filter_var($argumento[1], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($recordatorioEspecifico === false) {
        fwrite(STDERR, "El identificador del recordatorio no es válido.\n");
        exit(2);
    }
}
$filtroRecordatorio = $recordatorioEspecifico === null
    ? ''
    : ' AND r.ID_Recordatorio=' . (int) $recordatorioEspecifico;

require_once __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/notificaciones_push.php';

$maxIntentos = 5;
$procesados = 0;
$enTransaccion = false;

try {
    while ($procesados < 50) {
        $connection->begin_transaction();
        $enTransaccion = true;
        $resultado = $connection->query(
            "SELECT r.ID_Recordatorio, r.ID_Reserva, r.Intentos, v.ID_Usuario, v.Inicio,
                v.Estado AS Estado_Reserva, (v.Inicio > NOW()) AS Reserva_Vigente
            FROM recordatorios_reserva r
            INNER JOIN reservas_espacios v ON v.ID_Reserva=r.ID_Reserva
            WHERE (
                (r.Estado='pendiente' AND r.Enviar_En <= NOW())
                OR (r.Estado='fallida' AND r.Intentos < {$maxIntentos}
                    AND r.Intentada <= DATE_SUB(NOW(), INTERVAL 5 MINUTE))
                OR (r.Estado='procesando' AND r.Intentada <= DATE_SUB(NOW(), INTERVAL 10 MINUTE))
            ){$filtroRecordatorio}
            ORDER BY r.Enviar_En, r.ID_Recordatorio
            LIMIT 1
            FOR UPDATE"
        );
        $recordatorio = $resultado->fetch_assoc();

        if (!$recordatorio) {
            $connection->commit();
            $enTransaccion = false;
            break;
        }

        $idRecordatorio = (int) $recordatorio['ID_Recordatorio'];
        $reintento = $connection->prepare(
            "UPDATE recordatorios_reserva
            SET Estado='procesando', Intentada=NOW()
            WHERE ID_Recordatorio=?"
        );
        $reintento->bind_param('i', $idRecordatorio);
        $reintento->execute();
        $connection->commit();
        $enTransaccion = false;
        $procesados++;

        if ($recordatorio['Estado_Reserva'] === 'cancelada' || !(int) $recordatorio['Reserva_Vigente']) {
            $omitido = $connection->prepare(
                "UPDATE recordatorios_reserva SET Estado='omitida', Intentada=NOW()
                WHERE ID_Recordatorio=? AND Estado='procesando'"
            );
            $omitido->bind_param('i', $idRecordatorio);
            $omitido->execute();
            echo "Recordatorio {$idRecordatorio}: omitido (reserva cancelada o vencida).\n";
            continue;
        }

        $usuarioId = (int) $recordatorio['ID_Usuario'];
        $enviado = enviarPushAUsuario($connection, $usuarioId, [
            'title' => 'Tu espacio de CaféLiz te espera',
            'body' => 'Tu reserva de coworking comienza en una hora.',
            'url' => './reservas.php'
        ]);
        if ($enviado) {
            $actualizar = $connection->prepare(
                "UPDATE recordatorios_reserva
                SET Estado='enviada', Intentos=Intentos+1, Enviada=NOW(), Intentada=NOW()
                WHERE ID_Recordatorio=? AND Estado='procesando'"
            );
            $actualizar->bind_param('i', $idRecordatorio);
            $actualizar->execute();
            echo "Recordatorio {$idRecordatorio}: push aceptado por al menos una suscripción.\n";
            continue;
        }

        $fallido = $connection->prepare(
            "UPDATE recordatorios_reserva
            SET Estado='fallida', Intentos=Intentos+1, Intentada=NOW()
            WHERE ID_Recordatorio=? AND Estado='procesando'"
        );
        $fallido->bind_param('i', $idRecordatorio);
        $fallido->execute();
        echo "Recordatorio {$idRecordatorio}: envío fallido; reintentos cada cinco minutos hasta completar {$maxIntentos} intentos.\n";
    }

    $connection->close();
} catch (Throwable $error) {
    if ($enTransaccion) {
        try {
            $connection->rollback();
            $enTransaccion = false;
        } catch (Throwable $rollbackError) {
            error_log((string) $rollbackError);
        }
    }
    error_log('Falló el procesador de recordatorios de reserva: ' . (string) $error);
    fwrite(STDERR, "No se pudieron procesar los recordatorios.\n");
    exit(1);
}
