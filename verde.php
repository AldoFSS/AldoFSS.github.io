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
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#FFFFFF">
    <title>Huella verde · Caféliz</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="IMG/cafeliz-pwa-192-v2.png">
    <link rel="stylesheet" href="CSS/verde.css?v=3">
</head>
<body class="verde-page" data-offline-view="true">
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

        <section class="verde-panel verde-qr-panel" id="pase-reutilizable" aria-labelledby="qr-title">
            <div>
                <span class="verde-eyebrow">EN CAJA O BARRA</span>
                <h2 id="qr-title">Genera tu pase reutilizable</h2>
                <p>El código es de un solo uso y vence en cinco minutos. Muéstralo al barista después de pedir con tu termo.</p>
                <button class="verde-button" id="generar-qr" type="button">Generar código temporal</button>
                <p class="verde-status" id="qr-status" role="status" aria-live="polite" tabindex="-1"></p>
            </div>
            <div class="verde-code" id="qr-code" aria-label="Código QR temporal"></div>
        </section>

        <section aria-labelledby="personal-title">
            <div class="verde-section-heading">
                <div><span class="verde-eyebrow">TU BILLETERA</span><h2 id="personal-title">Impacto acumulado</h2></div>
            </div>
            <div class="verde-metrics" id="metricas-personales" aria-live="polite">
                <article><i class="bi bi-cup-hot-fill verde-metric-icon" aria-hidden="true"></i><span>Vasos evitados</span><strong>…</strong></article>
                <article><i class="bi bi-cloud-check-fill verde-metric-icon" aria-hidden="true"></i><span>CO₂ no emitido</span><strong>…</strong></article>
                <article><i class="bi bi-recycle verde-metric-icon" aria-hidden="true"></i><span>Residuos evitados</span><strong>…</strong></article>
                <article><i class="bi bi-droplet-half verde-metric-icon" aria-hidden="true"></i><span>Agua ahorrada</span><strong>…</strong></article>
            </div>
            <p class="verde-impact-note">Estimaciones por vaso registrado: 80 g de CO₂, 15 g de residuos y 250 ml de agua. Son equivalencias orientativas, no mediciones directas.</p>
            <div class="verde-reward-celebration" id="recompensa-celebracion" role="status" aria-live="polite" hidden>
                <span class="verde-celebration-icon" aria-hidden="true">🎉</span>
                <div>
                    <strong>¡Desbloqueaste una bebida gratis!</strong>
                    <p>Tu nuevo cupón ya está en tu billetera y vence en 14 días.</p>
                </div>
                <a class="verde-celebration-link" href="./menu.php">Usarlo en mi pedido</a>
                <button class="verde-celebration-close" id="cerrar-celebracion" type="button" aria-label="Cerrar aviso">×</button>
            </div>
            <section class="verde-panel verde-rewards">
                <div class="verde-rewards-copy">
                    <span class="verde-eyebrow">RECOMPENSAS</span>
                    <h2>Tu próxima bebida gratis</h2>
                    <p class="verde-rewards-earned" id="recompensas-ganadas">Cargando tus recompensas…</p>
                    <p class="verde-progress-label" id="progreso-insignias" aria-live="polite">
                        Cargando tu progreso…
                    </p>
                    <p class="verde-muted">Cada ocho vasos reutilizables recibes una bebida gratis.</p>
                    <p class="verde-muted">El cupón vence 14 días después de generarse; úsalo en un pedido desde el menú.</p>
                    <button class="verde-button verde-button-secondary" id="mostrar-qr-recompensas" type="button">
                        <i class="bi bi-qr-code" aria-hidden="true"></i> Mostrar mi QR en barra
                    </button>
                </div>
                <div class="verde-cup-visual">
                    <div class="verde-cup-progress" id="vasos-progreso" role="progressbar"
                        aria-label="Progreso para obtener un cupón" aria-valuemin="0" aria-valuemax="8" aria-valuenow="0">
                        <svg class="verde-cup-illustration" viewBox="0 0 200 240" aria-hidden="true" focusable="false">
                            <defs>
                                <clipPath id="taza-interior">
                                    <path d="M47 43h106l-14 164q-39 17-78 0L47 43z"/>
                                </clipPath>
                            </defs>
                            <path class="verde-cup-handle" d="M153 66h11c22 0 22 58 0 58h-16"/>
                            <rect id="nivel-taza" class="verde-cup-liquid" x="0" y="207" width="200" height="170"
                                clip-path="url(#taza-interior)"/>
                            <path class="verde-cup-outline" d="M42 38h116l-16 174q-42 19-84 0L42 38z"/>
                            <path class="verde-cup-rim" d="M39 39h122"/>
                            <path class="verde-cup-steam" d="M75 26c-8-8 8-10 0-19m25 19c-8-8 8-10 0-19m25 19c-8-8 8-10 0-19"/>
                        </svg>
                    </div>
                    <span class="verde-cup-count" id="vasos-contador">0 de 8 vasos</span>
                </div>
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
                <article><i class="bi bi-cup-hot-fill verde-metric-icon" aria-hidden="true"></i><span>Vasos evitados</span><strong>…</strong></article>
                <article><i class="bi bi-cloud-check-fill verde-metric-icon" aria-hidden="true"></i><span>CO₂ no emitido</span><strong>…</strong></article>
                <article><i class="bi bi-recycle verde-metric-icon" aria-hidden="true"></i><span>Residuos evitados</span><strong>…</strong></article>
                <article><i class="bi bi-droplet-half verde-metric-icon" aria-hidden="true"></i><span>Agua ahorrada</span><strong>…</strong></article>
            </div>
        </section>
    </main>
    <script src="node_modules/qrcodejs/qrcode.min.js"></script>
    <script src="JS/verde.js?v=3" defer></script>
</body>
</html>
