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
    <meta name="theme-color" content="#FF4500">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="IMG/pwa-icon.svg">
    <link rel="stylesheet" href="CSS/home.css">
    <title>Productos · Administración CaféLiz</title>
</head>
<body class="admin-page">
<?php include("includes/navbar.php");?>
<main class="admin-main">
    <header class="admin-heading">
        <div><span class="admin-eyebrow">CATÁLOGO</span><h1>Productos</h1><p>Administra las bebidas y alimentos disponibles en CaféLiz.</p></div>
        <button type="button" class="btn boton_m" data-bs-toggle="modal" data-bs-target="#agregarProductModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Registrar producto
        </button>
    </header>
    <section class="admin-card" aria-label="Lista de productos">
            <?php include("PHP/MostrarProductos.php"); ?>
    </section>
</main>
<?php include("includes/footer.html");?>
<?php  include("modales/modalAdd.php")?>
<?php  include("modales/modalEditar.php")?>
<?php  include("modales/modalCateg.php")?>
<?php  include("modales/modalDelete.php") ?>
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
