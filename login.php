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
</head>
<body>
<?php include("includes/navbar.php");?>
<center>
    <!--FORMULARIO---->
    <strong> <h1 class="titulo">Regístrate</strong></h1> 
      <form action="PHP/FuncionesUsuario.php"form class="form" method="POST" name="frm"><br>  
      <input type="hidden" name="accion" value="BuscarUsuario">     
        <!--CAJAS-DE-ENTRADA-DE-DATOS------------------------------------------------>
        Iniciar Sesión<br>
        <br><input class="cajas" type="text" name="nombre_usuario" placeholder="Ingresa tu Usuario" required maxlength="30">
        <input class="cajas" type="password" name="contraseña_usuario" placeholder="Ingresa tu Password" required maxlength="30">
        <br>
        <!--BOTON-DE-INGRESAR-------------------------->
        <input type="submit" class="btnRegistro" value="Ingresar">
         <p class="nota">*Al registrarse agregamos un café gratis a tu pedido</p><br>
            <!--AÚN NO-TENGO-CUENTA----------------------------------------------------------->
        <p class="tengo-cuenta"><a href="registro.php" class="tengo-cuenta">Aún no tengo cuenta</a></p>
		<a href="#" id="back-to-top" title="Back to top" style="display: none;"><i class="fa fa-paper-plane-o" aria-hidden="true"></i></a>
</center>
</form><br>
<?php include("includes/footer.html");?>
<script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>