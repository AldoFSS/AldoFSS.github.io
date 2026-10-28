<?php
require_once __DIR__ . '/PHP/seguridad.php';
iniciarSesionSegura();
if (empty($_SESSION['ID_Usuario'])) {
    header('Location: login.php', true, 303);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FF4500">
    <title>Huella verde · Caféliz</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="IMG/pwa-icon.svg">
    <link rel="stylesheet" href="CSS/verde.css">
</head>
<body class="verde-page">
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <main class="verde-main">
        <header class="verde-hero">
            <div>
                <span class="verde-eyebrow">FIDELIZACIÓN SOSTENIBLE</span>
                <h1>Tu huella verde</h1>
                <p>Trae tu taza reutilizable, pide al barista que escanee tu pase y suma impacto positivo.</p>
            </div>
            <div class="verde-hero-icon" aria-hidden="true">♻</div>
        </header>

        <section class="verde-panel verde-qr-panel" aria-labelledby="qr-title">
            <div>
                <span class="verde-eyebrow">EN CAJA O BARRA</span>
                <h2 id="qr-title">Genera tu pase reutilizable</h2>
                <p>El código es de un solo uso y vence en cinco minutos. Muéstralo al barista después de pedir con tu termo.</p>
                <button class="verde-button" id="generar-qr" type="button">Generar código temporal</button>
                <p class="verde-status" id="qr-status" role="status" aria-live="polite"></p>
            </div>
            <div class="verde-code" id="qr-code" aria-label="Código QR temporal"></div>
        </section>

        <section aria-labelledby="personal-title">
            <div class="verde-section-heading">
                <div><span class="verde-eyebrow">TU BILLETERA</span><h2 id="personal-title">Impacto acumulado</h2></div>
            </div>
            <div class="verde-metrics" id="metricas-personales" aria-live="polite">
                <article><span>Vasos evitados</span><strong>…</strong></article>
                <article><span>CO₂ no emitido</span><strong>…</strong></article>
                <article><span>Residuos evitados</span><strong>…</strong></article>
                <article><span>Agua ahorrada</span><strong>…</strong></article>
            </div>
            <section class="verde-panel verde-rewards">
                <div>
                    <span class="verde-eyebrow">RECOMPENSAS</span>
                    <h2 id="avance-insignias">Progreso de insignias</h2>
                    <p id="progreso-insignias">Cargando tu progreso…</p>
                    <div class="verde-progress" role="progressbar" aria-label="Progreso para obtener una bebida gratis" aria-valuemin="0" aria-valuemax="8" aria-valuenow="0">
                        <span id="barra-progreso"></span>
                    </div>
                    <p class="verde-muted">Cada vaso evitado suma 10 puntos verdes. Al completar 8, recibes un cupón de bebida gratis.</p>
                </div>
                <div class="verde-badge" aria-hidden="true">★</div>
            </section>
            <section class="verde-coupons" aria-labelledby="cupones-title">
                <h2 id="cupones-title">Cupones disponibles</h2>
                <div id="lista-cupones"><p class="verde-muted">Cargando cupones…</p></div>
            </section>
        </section>

        <section class="verde-community" aria-labelledby="comunidad-title">
            <div class="verde-section-heading">
                <div><span class="verde-eyebrow">CAFÉLIZ EN COMUNIDAD</span><h2 id="comunidad-title">Marcador colectivo</h2></div>
                <span class="verde-live"><i aria-hidden="true"></i> Actualizado en vivo</span>
            </div>
            <div class="verde-metrics" id="metricas-comunidad" aria-live="polite">
                <article><span>Vasos evitados</span><strong>…</strong></article>
                <article><span>CO₂ no emitido</span><strong>…</strong></article>
                <article><span>Residuos evitados</span><strong>…</strong></article>
                <article><span>Agua ahorrada</span><strong>…</strong></article>
            </div>
        </section>
    </main>
    <script src="node_modules/qrcodejs/qrcode.min.js"></script>
    <script src="JS/verde.js" defer></script>
</body>
</html>
