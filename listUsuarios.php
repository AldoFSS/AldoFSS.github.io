<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<?php include("includes/navbar.php");?>
<br><br><br><br><br><br>
<div class="container">
    <div class="row justify-content-md-center">
        <div class="col-md-12">
            <h1 class="text-center">
                
            </h1>
            <?php include("PHP/MostrarUsuarios.php"); ?>
        </div>
    </div>
</div>
<br>
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
