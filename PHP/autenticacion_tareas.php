<?php
function exigirTareaProgramada(string $nombreScript): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(404);
        exit;
    }

    $secreto = getenv('RECORDATORIOS_HMAC_SECRET');
    $marcaTiempo = $_SERVER['HTTP_X_CAFELIZ_TIMESTAMP'] ?? '';
    $firma = $_SERVER['HTTP_X_CAFELIZ_SIGNATURE'] ?? '';
    $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $rutaEsperada = '/PHP/' . $nombreScript;
    if (!$secreto || strlen($secreto) < 32 || !is_string($ruta) || $ruta !== $rutaEsperada ||
        !preg_match('/\A[0-9]{10}\z/D', $marcaTiempo) ||
        abs(time() - (int) $marcaTiempo) > 300 ||
        !preg_match('/\A[a-f0-9]{64}\z/D', $firma)) {
        http_response_code(404);
        exit;
    }

    $firmaEsperada = hash_hmac('sha256', $marcaTiempo . "\n" . $ruta, $secreto);
    if (!hash_equals($firmaEsperada, $firma)) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
}
