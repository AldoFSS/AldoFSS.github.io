<?php
require_once __DIR__ . '/seguridad.php';
exigirPostLocal();
exigirAdministrador();
require __DIR__ . '/../BD/conexion.php';

function imagenProducto($actual = '')
{
    $archivo = $_FILES['ImgProduct'] ?? null;
    if (!$archivo || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return $actual;
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 5 * 1024 * 1024 ||
        !is_uploaded_file($archivo['tmp_name'])) {
        throw new InvalidArgumentException('La imagen debe pesar como máximo 5 MB.');
    }
    $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    if (!isset($tipos[$mime]) || !getimagesize($archivo['tmp_name'])) {
        throw new InvalidArgumentException('Selecciona una imagen JPG o PNG válida.');
    }
    $nombre = bin2hex(random_bytes(16)) . '.' . $tipos[$mime];
    $directorio = __DIR__ . '/../PRODUCTOS/';
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true)) {
        throw new RuntimeException('No se pudo crear el directorio de imágenes.');
    }
    if (!move_uploaded_file($archivo['tmp_name'], $directorio . $nombre)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }
    return '../PRODUCTOS/' . $nombre;
}
$enTransaccion = false;
$imagenNueva = null;
try {
    $accion = $_POST['accion'] ?? '';
    $destino = in_array($accion, ['categoria', 'actualizarCat', 'eliminarCat'], true)
        ? '../listCategorias.php' : '../listProductos.php';
    $connection->begin_transaction();
    $enTransaccion = true;
    switch ($accion) {
        case 'insertar':
        case 'actualizar':
            $nombre = textoFormulario('Product_nombre', 255);
            $descripcion = $_POST['Descripcion'] ?? '';
            if (!is_string($descripcion) || strlen($descripcion) > 60000) {
                throw new InvalidArgumentException('Descripción no válida.');
            }
            $costo = $_POST['Costo'] ?? '';
            if (!is_string($costo) || !preg_match('/^\\d{1,8}(?:\\.\\d{1,2})?$/D', $costo)) {
                throw new InvalidArgumentException('El costo debe ser positivo y tener hasta dos decimales.');
            }
            $categoria = enteroPositivo($_POST['Categoria'] ?? null);
            $imagen = '';
            if ($accion === 'actualizar') {
                $id = enteroPositivo($_POST['id'] ?? null);
                $q = $connection->prepare('SELECT Imagen FROM menuproductos WHERE id = ? FOR UPDATE');
                $q->bind_param('i', $id);
                $q->execute();
                $producto = $q->get_result()->fetch_assoc();
                if (!$producto) {
                    throw new InvalidArgumentException('El producto ya no existe.');
                }
                $imagen = $producto['Imagen'];
            }
            $anterior = $imagen;
            $imagen = imagenProducto($imagen);
            if ($imagen !== $anterior) {
                $imagenNueva = __DIR__ . '/../PRODUCTOS/' . basename($imagen);
            }
            if ($accion === 'insertar') {
                $q = $connection->prepare('INSERT INTO menuproductos (Product_Nombre, Descripcion, Costo, Imagen, id_categoria) VALUES (?, ?, ?, ?, ?)');
                $q->bind_param('ssssi', $nombre, $descripcion, $costo, $imagen, $categoria);
            } else {
                $q = $connection->prepare('UPDATE menuproductos SET Product_Nombre=?, Descripcion=?, Costo=?, Imagen=?, id_categoria=? WHERE id=?');
                $q->bind_param('ssssii', $nombre, $descripcion, $costo, $imagen, $categoria, $id);
            }
            break;
        case 'eliminar':
            $id = enteroPositivo($_POST['id'] ?? null);
            // La FK impide eliminar productos vendidos; nunca borrar detalles de ventas.
            $q = $connection->prepare('DELETE FROM menuproductos WHERE id=?');
            $q->bind_param('i', $id);
            break;
        case 'categoria':
            $categoria = textoFormulario('Categoria', 50);
            $q = $connection->prepare('INSERT INTO menucategoria (Categoria) VALUES (?)');
            $q->bind_param('s', $categoria);
            break;
        case 'actualizarCat':
            $categoria = textoFormulario('Categoria', 50);
            $id = enteroPositivo($_POST['id'] ?? null);
            $q = $connection->prepare('UPDATE menucategoria SET Categoria=? WHERE ID_Cat=?');
            $q->bind_param('si', $categoria, $id);
            break;
        case 'eliminarCat':
            $id = enteroPositivo($_POST['id'] ?? null);
            $q = $connection->prepare('DELETE FROM menucategoria WHERE ID_Cat=?');
            $q->bind_param('i', $id);
            break;
        default:
            throw new InvalidArgumentException('Acción no válida.');
    }
    $q->execute();
    $connection->commit();
    $enTransaccion = false;
    header('Location: ' . $destino, true, 303);
    exit;
} catch (Throwable $e) {
    if ($enTransaccion) $connection->rollback();
    if ($imagenNueva && is_file($imagenNueva)) unlink($imagenNueva);
    error_log((string) $e);
    http_response_code($e instanceof InvalidArgumentException ? 422 : 409);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e instanceof InvalidArgumentException ? $e->getMessage()
        : 'No se pudo completar la operación. No se pueden eliminar productos vendidos ni categorías con productos. Regresa a la pantalla anterior.';
}
