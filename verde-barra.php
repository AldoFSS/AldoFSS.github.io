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
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#FFFFFF">
    <title>Caja verde · Caféliz</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="IMG/cafeliz-pwa-192-v2.png">
    <link rel="stylesheet" href="CSS/verde.css?v=4">
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
        <section class="verde-counter-panel" aria-label="Procesar vaso reutilizable o cupón">
            <div class="verde-mode-switch" role="group" aria-label="Elige qué vas a procesar">
                <button class="verde-mode-option is-active" type="button" data-mode="vaso" aria-pressed="true">
                    <i class="bi bi-cup-hot-fill" aria-hidden="true"></i>
                    <span><strong>Registrar vaso</strong><small>Sumar un vaso reutilizable</small></span>
                </button>
                <button class="verde-mode-option" type="button" data-mode="cupon" aria-pressed="false">
                    <i class="bi bi-ticket-perforated-fill" aria-hidden="true"></i>
                    <span><strong>Canjear cupón</strong><small>Validar una bebida gratis</small></span>
                </button>
            </div>
            <p class="verde-mode-description" id="descripcion-modo" aria-live="polite">
                Confirma que el cliente trajo su taza reutilizable y escanea su pase verde.
            </p>
            <div class="verde-panel verde-scanner-panel">
                <div class="verde-scan-actions">
                    <button class="verde-button" id="iniciar-camara" type="button">
                        <i class="bi bi-camera-video-fill" aria-hidden="true"></i> Activar cámara
                    </button>
                    <button class="verde-button" id="siguiente-escaneo" type="button" hidden>
                        <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Escanear siguiente
                    </button>
                    <button class="verde-button verde-button-secondary" id="detener-camara" type="button" disabled>
                        Detener cámara
                    </button>
                </div>
                <p id="estado-camara" class="verde-camera-hint" role="status" aria-live="polite">
                    Coloca el QR dentro del recuadro. La cámara se pausará después de cada lectura.
                </p>
                <div class="verde-camera-stage" id="marco-camara">
                    <video id="lector-video" class="verde-video" playsinline muted hidden
                        aria-label="Vista de la cámara para leer el código QR"></video>
                    <div class="verde-scan-frame" aria-hidden="true"><span></span></div>
                    <p class="verde-camera-placeholder" id="indicacion-camara">
                        <i class="bi bi-camera" aria-hidden="true"></i>
                        <span>Activa la cámara para leer el código</span>
                    </p>
                </div>
                <div class="verde-scan-result" id="resultado-escaneo" role="status" aria-live="polite" hidden>
                    <span class="verde-result-icon" id="icono-resultado" aria-hidden="true"></span>
                    <div>
                        <strong id="titulo-resultado"></strong>
                        <p id="detalle-resultado"></p>
                    </div>
                </div>
                <form id="formulario-qr" class="verde-manual">
                    <label for="codigo-qr">¿No puedes usar la cámara? Usa un lector QR o escribe el código</label>
                    <div class="verde-input-row">
                        <input id="codigo-qr" name="codigo" autocomplete="off" required maxlength="80"
                            placeholder="Pega o escanea aquí el código">
                        <button class="verde-button" id="procesar-codigo" type="submit">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i> Registrar vaso
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>
    <script src="JS/verde-barra.js?v=2" defer></script>
</body>
</html>
