<?php
require_once __DIR__ . '/seguridad.php';
iniciarSesionSegura();
exigirPostLocal();
require __DIR__ . '/../BD/conexion.php';
try {
    $accion = $_POST['accion'] ?? '';
    if (in_array($accion, ['actualizar', 'eliminar'], true)) exigirAdministrador();
    switch ($accion) {
        case 'BuscarUsuario':
            $nombre = textoFormulario('nombre_usuario', 50);
            $password = $_POST['contraseña_usuario'] ?? '';
            if (!is_string($password) || strlen($password) > 1024) throw new InvalidArgumentException('Contraseña no válida.');
            $q = $connection->prepare('SELECT id, nombre_usuario, contraseña_usuario, Cargo FROM usuarios WHERE nombre_usuario=?');
            $q->bind_param('s', $nombre);
            $q->execute();
            $usuario = $q->get_result()->fetch_assoc();
            $guardada = $usuario['contraseña_usuario'] ?? '';
            $antigua = preg_match('/^[a-f0-9]{32}$/D', $guardada) === 1;
            $valida = $usuario && ($antigua ? hash_equals($guardada, md5($password)) : password_verify($password, $guardada));
            if (!$valida) {
                header('Location: ../login.php?alert=error', true, 303);
                exit;
            }
            if ($antigua || password_needs_rehash($guardada, PASSWORD_DEFAULT)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $q = $connection->prepare('UPDATE usuarios SET contraseña_usuario=? WHERE id=? AND contraseña_usuario=?');
                $q->bind_param('sis', $hash, $usuario['id'], $guardada);
                $q->execute();
            }
            session_regenerate_id(true);
            $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
            $_SESSION['user_Cargo'] = $usuario['Cargo'];
            $_SESSION['ID_Usuario'] = $usuario['id'];
            header('Location: ../index.php?alert=' . ($usuario['Cargo'] === 'administrador' ? 'admin' : 'success'), true, 303);
            exit;
        case 'InsertarUsuario':
            $nombre = textoFormulario('nombre_usuario', 50);
            $password = $_POST['contraseña_usuario'] ?? '';
            if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
                throw new InvalidArgumentException('La contraseña debe tener entre 8 y 72 bytes.');
            }
            // Serializa el registro para evitar nombres repetidos sin modificar usuarios existentes.
            $lock = $connection->query("SELECT GET_LOCK('cafeliz_registro_usuario', 5) AS adquirido")->fetch_assoc();
            if ((int) $lock['adquirido'] !== 1) throw new RuntimeException('Registro ocupado.');
            try {
                $q = $connection->prepare('SELECT id FROM usuarios WHERE nombre_usuario=?');
                $q->bind_param('s', $nombre);
                $q->execute();
                if ($q->get_result()->num_rows > 0) {
                    header('Location: ../registro.php?alert=errorUser', true, 303);
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $cargo = 'usuario';
                    $q = $connection->prepare('INSERT INTO usuarios (nombre_usuario, contraseña_usuario, Cargo) VALUES (?, ?, ?)');
                    $q->bind_param('sss', $nombre, $hash, $cargo);
                    $q->execute();
                    header('Location: ../login.php?alert=register', true, 303);
                }
            } finally {
                $connection->query("SELECT RELEASE_LOCK('cafeliz_registro_usuario')");
            }
            exit;
        case 'actualizar':
            $id = enteroPositivo($_POST['id'] ?? null);
            $nombre = textoFormulario('Nombre', 50);
            $cargo = $_POST['Cargo'] ?? '';
            if (!in_array($cargo, ['administrador', 'barista', 'usuario'], true)) throw new InvalidArgumentException('Cargo no válido.');
            if ($id === (int) $_SESSION['ID_Usuario'] && $cargo !== 'administrador') {
                throw new InvalidArgumentException('No puedes quitarte tu propio acceso administrativo.');
            }
            $q = $connection->prepare('UPDATE usuarios SET nombre_usuario=?, Cargo=? WHERE id=?');
            $q->bind_param('ssi', $nombre, $cargo, $id);
            $q->execute();
            header('Location: ../listUsuarios.php', true, 303);
            exit;
        case 'eliminar':
            $id = enteroPositivo($_POST['id'] ?? null);
            if ($id === (int) $_SESSION['ID_Usuario']) throw new InvalidArgumentException('No puedes eliminar tu propia cuenta.');
            $q = $connection->prepare('DELETE FROM usuarios WHERE id=?');
            $q->bind_param('i', $id);
            $q->execute();
            header('Location: ../listUsuarios.php', true, 303);
            exit;
        default:
            throw new InvalidArgumentException('Acción no válida.');
    }
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code($e instanceof InvalidArgumentException ? 422 : 409);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e instanceof InvalidArgumentException ? $e->getMessage()
        : 'No se pudo completar la operación. Los usuarios con pedidos conservan su historial.';
}
