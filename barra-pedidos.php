<?php
require_once __DIR__ . '/PHP/seguridad.php';
exigirPersonalCafeteria();
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FFFFFF">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="stylesheet" href="CSS/admin.css?v=6">
    <link rel="stylesheet" href="CSS/kds.css">
    <title>Comandas de barra · CaféLiz</title>
</head>
<body class="admin-page">
<?php include __DIR__ . '/includes/navbar.php'; ?>
<main class="admin-main kds-main">
    <header class="admin-heading">
        <div>
            <span class="admin-eyebrow">PREPARACIÓN</span>
            <h1>Comandas de barra</h1>
            <p>Los pedidos nuevos aparecen automáticamente. Los nombres se muestran en español para el equipo.</p>
        </div>
        <span id="kds-connection" class="kds-connection" role="status" aria-live="polite">Conectando…</span>
    </header>
    <section id="kds-orders" class="kds-orders" aria-label="Pedidos pendientes" aria-live="polite"></section>
</main>
<script src="JS/cola-barra.js" defer></script>
</body>
</html>
