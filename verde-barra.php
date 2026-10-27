<?php
require_once __DIR__ . '/PHP/seguridad.php';
iniciarSesionSegura();
exigirPersonalCafeteria();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FF4500">
    <title>Caja verde · Caféliz</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="stylesheet" href="CSS/verde.css">
</head>
<body class="verde-page">
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <main class="verde-main verde-counter">
        <header class="verde-hero">
            <div>
                <span class="verde-eyebrow">OPERACIÓN DE BARRA</span>
                <h1>Escáner de fidelidad verde</h1>
                <p>Registra un vaso reutilizable o valida un cupón de bebida gratis.</p>
            </div>
            <div class="verde-hero-icon" aria-hidden="true">♻</div>
        </header>
        <section class="verde-panel">
            <div class="verde-scan-actions">
                <button class="verde-button" id="iniciar-camara" type="button">Activar cámara</button>
                <button class="verde-button verde-button-secondary" id="detener-camara" type="button" disabled>Detener cámara</button>
                <span id="estado-camara" class="verde-muted" role="status" aria-live="polite"></span>
            </div>
            <video id="lector-video" class="verde-video" playsinline muted hidden aria-label="Vista de la cámara para leer el código QR"></video>
            <form id="formulario-qr" class="verde-manual">
                <label for="codigo-qr">También puedes usar un lector QR o pegar el contenido del código</label>
                <div class="verde-input-row">
                    <input id="codigo-qr" name="codigo" autocomplete="off" required maxlength="80" placeholder="CAFELIZ-VERDE:… o CAFELIZ-CUPON:…">
                    <button class="verde-button" type="submit">Procesar código</button>
                </div>
            </form>
            <p id="resultado-escaneo" class="verde-status" role="status" aria-live="polite"></p>
        </section>
    </main>
    <script src="JS/verde-barra.js" defer></script>
</body>
</html>
