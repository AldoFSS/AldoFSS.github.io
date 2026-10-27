<?php
// En producción, configurar estas variables en el servidor, fuera del directorio público.
$entorno = getenv('APP_ENV') ?: 'local';
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');
$host = getenv('DB_HOST') ?: 'localhost';
$datab = getenv('DB_NAME') ?: 'cafeteria';
if ($entorno !== 'local' && ($user === false || $pass === false || $user === 'root' || $pass === '')) {
    throw new RuntimeException('Configura credenciales de base de datos para producción.');
}
$user = $user === false ? 'root' : $user;
$pass = $pass === false ? '' : $pass;
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connection = new mysqli($host, $user, $pass, $datab, (int) (getenv('DB_PORT') ?: 3306));
$connection->set_charset('utf8mb4');
