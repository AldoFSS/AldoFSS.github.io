<?php
require_once __DIR__ . '/PHP/seguridad.php';
iniciarSesionSegura();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && empty($_GET) && empty($_SESSION['ID_Usuario'])) {
    header('Cache-Control: private, no-cache, must-revalidate');
    header('X-PWA-Cacheable: public');
} else {
    header('Cache-Control: no-store');
}
?>
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
<body class="auth-page" data-offline-view="true">
<?php include("includes/navbar.php");?>
<main class="auth-content">
    <h1 class="titulo">Iniciar sesión</h1>
    <form action="PHP/FuncionesUsuario.php" class="form auth-form" method="POST" name="frm">
        <input type="hidden" name="accion" value="BuscarUsuario">
        <label class="visually-hidden" for="login-username">Usuario</label>
        <input id="login-username" class="cajas" type="text" name="nombre_usuario"
            placeholder="Ingresa tu Usuario" autocomplete="username" required maxlength="30">
        <label class="visually-hidden" for="login-password">Contraseña</label>
        <input id="login-password" class="cajas" type="password" name="contraseña_usuario"
            placeholder="Ingresa tu Password" autocomplete="current-password" required maxlength="30">
        <input type="submit" class="btnRegistro" value="Ingresar">
        <p class="nota">*Al registrarse agregamos un café gratis a tu pedido</p>
        <p class="tengo-cuenta"><a href="registro.php" class="tengo-cuenta">Aún no tengo cuenta</a></p>
    </form>
</main>
<?php include("includes/footer.html");?>
</body>
</html>