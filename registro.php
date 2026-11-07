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
    <title>Document</title>
</head>
<body class="auth-page" data-offline-view="true">
<?php include("includes/navbar.php");?>
<main class="auth-content">
    <h1 class="titulo">Crear cuenta</h1>
    <form action="PHP/FuncionesUsuario.php" class="form auth-form" method="POST" name="frm">
        <input type="hidden" name="accion" value="InsertarUsuario">
        <label class="visually-hidden" for="register-username">Nombre de usuario</label>
        <input id="register-username" class="cajas" type="text" name="nombre_usuario"
            placeholder="Ingresa tu nombre" autocomplete="username" required maxlength="30">
        <label class="visually-hidden" for="register-password">Contraseña</label>
        <input id="register-password" class="cajas" type="password" name="contraseña_usuario"
            minlength="8" placeholder="Ingresa tu Password" autocomplete="new-password" required maxlength="30">
        <input type="submit" class="btnRegistro" value="Registrarme">
        <p class="tengo-cuenta"><a href="login.php" class="tengo-cuenta">Regresar</a></p>
    </form>
</main>
<?php include("includes/footer.html");?>
</body>
</html>