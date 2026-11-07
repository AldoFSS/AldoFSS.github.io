<?php
$esCli = PHP_SAPI === 'cli';
if (!$esCli) {
    require_once __DIR__ . '/autenticacion_tareas.php';
    exigirTareaProgramada('procesar_recordatorios_cupon.php');
    ob_start();
}

require_once __DIR__ . '/notificaciones_push.php';
require_once __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/zona_horaria_app.php';

$maxIntentos = 5;
$procesados = 0;
$limiteProcesamiento = $esCli ? 50 : 2;
$enTransaccion = false;

try {
    configurarZonaHorariaAplicacion($connection);
    while ($procesados < $limiteProcesamiento) {
        $connection->begin_transaction();
        $enTransaccion = true;
        $resultado = $connection->query(
            "SELECT ID_Cupon, ID_Usuario, Expira, Recordatorio_Intentos
            FROM eco_cupones
            WHERE Canjeado IS NULL AND Expira > CURRENT_TIMESTAMP
                AND Expira <= DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 3 DAY)
                AND Recordatorio_Enviado IS NULL
                AND Recordatorio_Intentos < {$maxIntentos}
                AND (Recordatorio_Intentado IS NULL
                    OR Recordatorio_Intentado <= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 5 MINUTE))
            ORDER BY Expira, ID_Cupon
            LIMIT 1
            FOR UPDATE"
        );
        $cupon = $resultado->fetch_assoc();

        if (!$cupon) {
            $connection->commit();
            $enTransaccion = false;
            break;
        }

        $idCupon = (int) $cupon['ID_Cupon'];
        $intento = $connection->prepare(
            'UPDATE eco_cupones
            SET Recordatorio_Intentado=CURRENT_TIMESTAMP, Recordatorio_Intentos=Recordatorio_Intentos+1
            WHERE ID_Cupon=? AND Canjeado IS NULL AND Recordatorio_Enviado IS NULL'
        );
        $intento->bind_param('i', $idCupon);
        $intento->execute();
        $connection->commit();
        $enTransaccion = false;
        $procesados++;

        $fechaExpiracion = new DateTimeImmutable($cupon['Expira']);
        $diasRestantes = max(1, (int) ceil(
            ($fechaExpiracion->getTimestamp() - time()) / 86400
        ));
        $enviado = enviarPushAUsuario($connection, (int) $cupon['ID_Usuario'], [
            'title' => 'Tu bebida gratis está por vencer',
            'body' => "Usa tu cupón verde en un pedido; vence en {$diasRestantes} días.",
            'url' => './verde.php',
            'event_key' => 'recordatorio-cupon:' . $idCupon,
            'attempt' => (int) $cupon['Recordatorio_Intentos'] + 1,
            '_queue' => false
        ]);

        if ($enviado) {
            $marcar = $connection->prepare(
                'UPDATE eco_cupones SET Recordatorio_Enviado=CURRENT_TIMESTAMP
                WHERE ID_Cupon=? AND Canjeado IS NULL AND Recordatorio_Enviado IS NULL'
            );
            $marcar->bind_param('i', $idCupon);
            $marcar->execute();
            echo "Cupón {$idCupon}: recordatorio aceptado por al menos una suscripción.\n";
        } else {
            echo "Cupón {$idCupon}: push no enviado; se reintentará hasta {$maxIntentos} veces.\n";
        }
    }

    $connection->close();
    if (!$esCli) {
        ob_end_clean();
        echo "Recordatorios procesados.\n";
    }
} catch (Throwable $error) {
    if ($enTransaccion) {
        try {
            $connection->rollback();
            $enTransaccion = false;
        } catch (Throwable $rollbackError) {
            error_log((string) $rollbackError);
        }
    }
    error_log('Falló el procesador de recordatorios de cupones: ' . (string) $error);
    if ($esCli) {
        fwrite(STDERR, "No se pudieron procesar los recordatorios de cupones.\n");
    } else {
        ob_end_clean();
        http_response_code(500);
        echo "No se pudieron procesar los recordatorios de cupones.\n";
    }
    exit(1);
}
