<?php
function configurarZonaHorariaAplicacion(mysqli $connection): void
{
    $nombreZona = getenv('APP_TIMEZONE') ?: 'America/Mexico_City';
    $zona = new DateTimeZone($nombreZona);
    date_default_timezone_set($zona->getName());
    $desfase = (new DateTimeImmutable('now', $zona))->format('P');
    if (!preg_match('/\A[+-](?:0\d|1[0-4]):[0-5]\d\z/D', $desfase)) {
        throw new RuntimeException('La zona horaria configurada no produjo un desfase MySQL válido.');
    }
    $connection->query("SET time_zone = '{$desfase}'");
}
