<?php
require_once __DIR__ . '/PHP/seguridad.php';
exigirAdministrador();
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#FFFFFF">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="IMG/cafeliz-pwa-192-v2.png">
    <title>Usuarios · Administración CaféLiz</title>
</head>
<body class="admin-page">
<?php include("includes/navbar.php");?>
<main class="admin-main">
    <header class="admin-heading">
        <div><span class="admin-eyebrow">CUENTAS Y PERMISOS</span><h1>Usuarios</h1><p>Consulta las cuentas y administra sus roles de acceso.</p></div>
    </header>
    <section class="admin-card" aria-label="Lista de usuarios">
            <?php include("PHP/MostrarUsuarios.php"); ?>
    </section>
</main>
<?php include("includes/footer.html");?>
<?php include("modalUsuarios/modalEditar.php") ?>
<?php include("modalUsuarios/modalDeleteUser.php") ?>
<script>
    $(document).ready(function() {
        $("#table_User").DataTable({
            pageLength: 5,
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json",
            },
        });
    });
</script>
<script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
