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
    <link rel="stylesheet" href="CSS/home.css">
    <title>Categorías · Administración CaféLiz</title>
</head>
<body class="admin-page">
<?php include("includes/navbar.php");?>
<main class="admin-main">
    <header class="admin-heading">
        <div><span class="admin-eyebrow">CATÁLOGO</span><h1>Categorías</h1><p>Organiza los productos por tipo para mantener el menú ordenado.</p></div>
        <button type="button" class="btn boton_m" data-bs-toggle="modal" data-bs-target="#agregarCategoriaModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Registrar categoría
        </button>
    </header>
    <section class="admin-card" aria-label="Lista de categorías">
            <?php include("PHP/MostrarCategorias.php"); ?>
    </section>
</main>
<?php include("includes/footer.html");?>
<?php  include("modales/modalEditCat.php")?>
<?php  include("modales/modalCateg.php")?>
<?php  include("modales/modalDeleteCat.php")?>
<script>
    $(document).ready(function() {
        $("#table_Productos").DataTable({
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
