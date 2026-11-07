<?php
require_once __DIR__ . '/PHP/seguridad.php';
exigirPersonalCafeteria();
header('Cache-Control: no-store');

$scheme = solicitudEsHttps() ? 'https' : 'http';
$serverName = (string) ($_SERVER['SERVER_NAME'] ?? '');
$port = (int) ($_SERVER['SERVER_PORT'] ?? ($scheme === 'https' ? 443 : 80));
$portSuffix = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80)
    ? '' : ':' . $port;
$basePath = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
$basePath = $basePath === '/' || $basePath === '.' ? '' : '/' . trim($basePath, '/');
$menuUrl = $scheme . '://' . $serverName . $portSuffix . $basePath . '/menu.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FFFFFF">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="stylesheet" href="CSS/admin.css?v=6">
    <link rel="stylesheet" href="CSS/menu-qr.css">
    <title>QR del menú · CaféLiz</title>
</head>
<body class="admin-page">
<?php include __DIR__ . '/includes/navbar.php'; ?>
<main class="admin-main menu-qr-main">
    <header class="admin-heading">
        <div>
            <span class="admin-eyebrow">ACCESO PARA CLIENTES</span>
            <h1>QR del menú</h1>
            <p>El cliente escanea el código con la cámara y abre el menú en el navegador, sin descargar una aplicación.</p>
        </div>
    </header>
    <section class="menu-qr-card">
        <div id="menu-qr-code" role="img" aria-label="Código QR que abre el menú de CaféLiz"></div>
        <p id="menu-qr-url" class="menu-qr-url"></p>
        <p class="menu-qr-note">Imprime este código y colócalo en las mesas. La traducción automática requiere conexión al menú.</p>
        <button type="button" class="btn boton_m" id="print-menu-qr">Imprimir QR</button>
        <p id="menu-qr-error" class="menu-qr-error" role="alert" hidden>No se pudo generar el código QR.</p>
    </section>
</main>
<script src="node_modules/qrcodejs/qrcode.min.js"></script>
<script>
    (() => {
        const url = <?php echo json_encode($menuUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR); ?>;
        const root = document.getElementById('menu-qr-code');
        document.getElementById('menu-qr-url').textContent = url;
        if (window.QRCode) {
            new QRCode(root, {text: url, width: 256, height: 256, correctLevel: QRCode.CorrectLevel.M});
        } else {
            document.getElementById('menu-qr-error').hidden = false;
        }
        document.getElementById('print-menu-qr').addEventListener('click', () => window.print());
    })();
</script>
</body>
</html>
