<?php
function iniciarSesionSegura()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax',
            'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    }
}
function exigirPostLocal()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Método no permitido.');
    }
    $origen = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
    $partes = parse_url($origen);
    $esquema = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $esperado = parse_url($esquema . '://' . ($_SERVER['HTTP_HOST'] ?? ''));
    if (!$partes || !isset($partes['host']) ||
        strtolower($partes['host']) !== strtolower($esperado['host'] ?? '') ||
        ($partes['scheme'] ?? '') !== $esquema ||
        ($partes['port'] ?? ($esquema === 'https' ? 443 : 80)) !== ($esperado['port'] ?? ($esquema === 'https' ? 443 : 80))) {
        http_response_code(403);
        exit('Origen de solicitud no permitido.');
    }
}
function exigirAdministrador()
{
    static $verificado = false;
    iniciarSesionSegura();
    if (empty($_SESSION['ID_Usuario']) || ($_SESSION['user_Cargo'] ?? '') !== 'administrador') {
        http_response_code(403);
        exit('Se requiere una sesión de administrador.');
    }
    if (!$verificado) {
        // Revalidar el cargo para que un permiso revocado no sobreviva en la sesión.
        require __DIR__ . '/../BD/conexion.php';
        $id = (int) $_SESSION['ID_Usuario'];
        $q = $connection->prepare('SELECT Cargo FROM usuarios WHERE id=?');
        $q->bind_param('i', $id);
        $q->execute();
        $usuario = $q->get_result()->fetch_assoc();
        $connection->close();
        if (!$usuario || $usuario['Cargo'] !== 'administrador') {
            unset($_SESSION['user_Cargo']);
            http_response_code(403);
            exit('El permiso administrativo ya no está disponible.');
        }
        $verificado = true;
    }
}
function exigirPersonalCafeteria()
{
    iniciarSesionSegura();
    $cargoSesion = $_SESSION['user_Cargo'] ?? '';
    if (empty($_SESSION['ID_Usuario']) || !in_array($cargoSesion, ['administrador', 'barista'], true)) {
        http_response_code(403);
        exit('Se requiere una cuenta de barista o administrador.');
    }
    require __DIR__ . '/../BD/conexion.php';
    $id = (int) $_SESSION['ID_Usuario'];
    $q = $connection->prepare('SELECT Cargo FROM usuarios WHERE id=?');
    $q->bind_param('i', $id);
    $q->execute();
    $usuario = $q->get_result()->fetch_assoc();
    $connection->close();
    if (!$usuario || !in_array($usuario['Cargo'], ['administrador', 'barista'], true)) {
        unset($_SESSION['user_Cargo']);
        http_response_code(403);
        exit('El acceso de barista ya no está disponible.');
    }
}
function enteroPositivo($valor)
{
    $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        throw new InvalidArgumentException('Identificador o cantidad no válido.');
    }
    return $id;
}
function textoFormulario($clave, $maximo)
{
    $valor = $_POST[$clave] ?? '';
    if (!is_string($valor) || trim($valor) === '' || mb_strlen(trim($valor)) > $maximo) {
        throw new InvalidArgumentException('Revisa el campo ' . $clave . '.');
    }
    return trim($valor);
}
