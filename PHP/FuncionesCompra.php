<?php
require_once __DIR__ . '/seguridad.php';
iniciarSesionSegura();
exigirPostLocal();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$transaccion = false;
try {
    if (empty($_SESSION['ID_Usuario'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Inicia sesión para registrar un pedido.']);
        exit;
    }
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
        throw new InvalidArgumentException('Se requiere una solicitud JSON.');
    }
    $raw = file_get_contents('php://input', false, null, 0, 65537);
    if (strlen($raw) > 65536) throw new InvalidArgumentException('Pedido demasiado grande.');
    $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    $cart = $data['cart'] ?? null;
    $clave = $data['requestId'] ?? '';
    if (!is_string($clave) || !preg_match('/^[a-f0-9-]{32,64}$/D', $clave)) {
        throw new InvalidArgumentException('Falta el identificador del pedido. Actualiza la página.');
    }
    if (!is_array($cart) || count($cart) < 1 || count($cart) > 100) {
        throw new InvalidArgumentException('El carrito debe contener entre 1 y 100 productos.');
    }
    $cantidades = [];
    foreach ($cart as $item) {
        if (!is_array($item)) throw new InvalidArgumentException('Producto no válido.');
        $id = enteroPositivo($item['id'] ?? null);
        $cantidad = enteroPositivo($item['quantity'] ?? null);
        if ($cantidad > 999 || isset($cantidades[$id])) {
            throw new InvalidArgumentException('Cantidad excesiva o producto duplicado.');
        }
        $cantidades[$id] = $cantidad;
    }
    ksort($cantidades);
    $huella = hash('sha256', json_encode($cantidades));
    require __DIR__ . '/../BD/conexion.php';
    $usuario = (int) $_SESSION['ID_Usuario'];
    $connection->begin_transaction();
    $transaccion = true;
    // La clave única serializa reintentos incluso desde conexiones simultáneas.
    $q = $connection->prepare('INSERT INTO solicitudes_compra (ID_Usuario, clave, huella) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE clave=VALUES(clave)');
    $q->bind_param('iss', $usuario, $clave, $huella);
    $q->execute();
    $q = $connection->prepare('SELECT huella, respuesta FROM solicitudes_compra WHERE ID_Usuario=? AND clave=? FOR UPDATE');
    $q->bind_param('is', $usuario, $clave);
    $q->execute();
    $solicitud = $q->get_result()->fetch_assoc();
    if (!hash_equals($solicitud['huella'], $huella)) {
        throw new InvalidArgumentException('El identificador ya corresponde a otro carrito.');
    }
    if ($solicitud['respuesta'] !== null) {
        $connection->commit();
        $transaccion = false;
        echo $solicitud['respuesta'];
        exit;
    }
    $productos = [];
    $precios = [];
    $centavos = 0;
    $q = $connection->prepare('SELECT Product_Nombre, Costo FROM menuproductos WHERE id=? LOCK IN SHARE MODE');
    foreach ($cantidades as $id => $cantidad) {
        $q->bind_param('i', $id);
        $q->execute();
        $producto = $q->get_result()->fetch_assoc();
        if (!$producto || !preg_match('/^([0-9]+)\\.([0-9]{2})$/D', $producto['Costo'], $partes)) {
            throw new InvalidArgumentException('Un producto no está disponible o tiene un precio no válido.');
        }
        $precios[$id] = $producto['Costo'];
        $precio = (int) $partes[1] * 100 + (int) $partes[2];
        $centavos += $precio * $cantidad;
        if ($centavos > 9999999999) throw new InvalidArgumentException('Total fuera de rango.');
        $productos[] = ['Nombre_Producto' => $producto['Product_Nombre'], 'Cantidad' => $cantidad];
    }
    $total = number_format($centavos / 100, 2, '.', '');
    $q = $connection->prepare('INSERT INTO ordenes (ID_Usuario, Total, Fecha_Compra) VALUES (?, ?, CURRENT_DATE())');
    $q->bind_param('is', $usuario, $total);
    $q->execute();
    $orden = $connection->insert_id;
    $q = $connection->prepare('INSERT INTO detallesorden (ID_Orden, ID_Productos, Cantidad, Precio_Unitario) VALUES (?, ?, ?, ?)');
    foreach ($cantidades as $id => $cantidad) {
        $precioGuardado = $precios[$id];
        $q->bind_param('iiis', $orden, $id, $cantidad, $precioGuardado);
        $q->execute();
    }
    require_once __DIR__ . '/Inventario.php';
    $insumosBajos = descontarInsumosDePedido($connection, $cantidades, $orden);
    $q = $connection->prepare('SELECT Fecha_Compra FROM ordenes WHERE ID_Ordenes=?');
    $q->bind_param('i', $orden);
    $q->execute();
    $fecha = $q->get_result()->fetch_assoc()['Fecha_Compra'];
    $respuesta = json_encode(['success' => true, 'productosInsertados' => true,
        'data' => ['orderId' => $orden, 'userName' => $_SESSION['nombre_usuario'] ?? '',
            'total' => $total, 'fecha' => $fecha, 'productos' => $productos]], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $q = $connection->prepare('UPDATE solicitudes_compra SET respuesta=? WHERE ID_Usuario=? AND clave=?');
    $q->bind_param('sis', $respuesta, $usuario, $clave);
    $q->execute();
    $connection->commit();
    $transaccion = false;
    if ($insumosBajos) {
        require_once __DIR__ . '/notificaciones_push.php';
        enviarPushAAdministradores($connection, [
            'title' => 'Revisar nivel de inventario',
            'body' => 'Un insumo alcanzó su nivel mínimo. Abre el panel para revisarlo.',
            'url' => './listInventario.php'
        ]);
    }
    echo $respuesta;
} catch (Throwable $e) {
    if ($transaccion) $connection->rollback();
    $validacion = $e instanceof InvalidArgumentException || $e instanceof JsonException;
    http_response_code($validacion ? 422 : 500);
    if (!$validacion) error_log((string) $e);
    echo json_encode(['success' => false, 'message' => $validacion ? $e->getMessage()
        : 'No se pudo confirmar el pedido. Reintenta sin cambiar el carrito.']);
}
