<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="CSS/home.css">
</head>
<body>
<?php include("includes/navbar.php");?>
<br><br>
<div class="container">
    <div class="row justify-content-md-center">
        <div class="col-md-12">
            <h1 class="text-center">
                <span class="float-start">
                    <button type="button" class="btn boton_m" data-bs-toggle="modal" data-bs-target="#agregarProductModal">
                        <i class="bi bi-person-plus"></i> Registrar Producto
                    </button>
                </span>
                <hr>
            </h1>
            <?php include("PHP/MostrarProductos.php"); ?>
        </div>
    </div>
</div>
<br>
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
