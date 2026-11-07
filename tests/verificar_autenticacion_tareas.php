<?php
$secreto = bin2hex(random_bytes(32));
$marcaTiempo = (string) time();
$ruta = '/PHP/procesar_notificaciones_fcm.php';
putenv('RECORDATORIOS_HMAC_SECRET=' . $secreto);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = $ruta;
$_SERVER['HTTP_X_CAFELIZ_TIMESTAMP'] = $marcaTiempo;
$_SERVER['HTTP_X_CAFELIZ_SIGNATURE'] = hash_hmac(
    'sha256',
    $marcaTiempo . "\n" . $ruta,
    $secreto
);

require_once __DIR__ . '/../PHP/autenticacion_tareas.php';
exigirTareaProgramada('procesar_notificaciones_fcm.php');
echo "OK: firma HMAC válida aceptada.\n";
