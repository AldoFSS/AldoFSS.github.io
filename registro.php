<?php require_once __DIR__ . '/PHP/seguridad.php'; iniciarSesionSegura(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#FFFFFF">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="IMG/cafeliz-pwa-192-v2.png">
    <title>Document</title>
</head>
<body>
<?php include("includes/navbar.php");?>
<center>
    <!--FORMULARIO---->
    <strong> <h1 class="titulo">Regístrate</strong></h1> 
      <form action="PHP/FuncionesUsuario.php"form class="form" method="POST" name="frm"><br>
      <input type="hidden" name="accion" value="InsertarUsuario">
      <!--CAJAS-DE-ENTRADA-DE-DATOS------------------------------------------------>
         Regístrate<br>
        <br><input class="cajas" type="text" name="nombre_usuario" placeholder="Ingresa tu nombre" required maxlength="30">
        <input class="cajas" type="password" name="contraseña_usuario" minlength="8" placeholder="Ingresa tu Password" required maxlength="30">
        <!--BOTON-DE-REGISTRARSE-------------------------->
        <input type="submit" class="btnRegistro" value="Registrarme">

        <p class="tengo-cuenta"><a href="login.php" class="tengo-cuenta">Regresar</a></p>
</center>
</form><br><br>
<?php include("includes/footer.html");?>
<script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>