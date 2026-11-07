<?php
require_once __DIR__ . '/seguridad.php';
iniciarSesionSegura();
exigirPersonalCafeteria();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderCola($estado, $respuesta)
{
    http_response_code($estado);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? '';

try {
    require __DIR__ . '/../BD/conexion.php';

    if ($metodo === 'GET') {
        $resultado = $connection->query(
            "SELECT o.ID_Ordenes, o.Total, o.Estado_KDS, o.Creado_En,
                d.ID_Productos, d.Cantidad, p.Product_Nombre
            FROM (
                SELECT ID_Ordenes
                FROM ordenes
                WHERE Estado_KDS IN ('pendiente', 'preparando', 'listo')
                ORDER BY COALESCE(Creado_En, CONCAT(Fecha_Compra, ' 00:00:00')), ID_Ordenes
                LIMIT 50
            ) cola
            INNER JOIN ordenes o ON o.ID_Ordenes = cola.ID_Ordenes
            INNER JOIN detallesorden d ON d.ID_Orden = o.ID_Ordenes
            INNER JOIN menuproductos p ON p.id = d.ID_Productos
            ORDER BY COALESCE(o.Creado_En, CONCAT(o.Fecha_Compra, ' 00:00:00')), o.ID_Ordenes, p.id"
        );
        $pedidos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $id = (int) $fila['ID_Ordenes'];
            if (!isset($pedidos[$id])) {
                $pedidos[$id] = [
                    'id' => $id,
                    'total' => (string) $fila['Total'],
                    'status' => $fila['Estado_KDS'],
                    'createdAt' => $fila['Creado_En'],
                    'items' => []
                ];
            }
            $pedidos[$id]['items'][] = [
                'name' => $fila['Product_Nombre'],
                'quantity' => (int) $fila['Cantidad']
            ];
        }
        responderCola(200, ['success' => true, 'orders' => array_values($pedidos)]);
    }

    if ($metodo !== 'POST') {
        header('Allow: GET, POST');
        responderCola(405, ['success' => false, 'message' => 'Metodo no permitido.']);
    }

    exigirPostLocal();
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
        throw new InvalidArgumentException('Se requiere una solicitud JSON.');
    }
    $raw = file_get_contents('php://input', false, null, 0, 2049);
    if (strlen($raw) > 2048) {
        throw new InvalidArgumentException('Solicitud demasiado grande.');
    }
    $data = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
    $id = enteroPositivo($data['orderId'] ?? null);
    $siguienteEstado = $data['status'] ?? '';
    $transiciones = [
        'pendiente' => 'preparando',
        'preparando' => 'listo',
        'listo' => 'entregado'
    ];
    if (!is_string($siguienteEstado) || !in_array($siguienteEstado, $transiciones, true)) {
        throw new InvalidArgumentException('Estado de comanda no valido.');
    }

    $estadoAnterior = array_search($siguienteEstado, $transiciones, true);
    $actualizar = $connection->prepare(
        'UPDATE ordenes SET Estado_KDS=? WHERE ID_Ordenes=? AND Estado_KDS=?'
    );
    $actualizar->bind_param('sis', $siguienteEstado, $id, $estadoAnterior);
    $actualizar->execute();
    if ($actualizar->affected_rows !== 1) {
        responderCola(409, [
            'success' => false,
            'message' => 'La comanda cambio en otra pantalla o ya no esta disponible.'
        ]);
    }
    responderCola(200, ['success' => true, 'orderId' => $id, 'status' => $siguienteEstado]);
} catch (InvalidArgumentException $e) {
    responderCola(422, ['success' => false, 'message' => $e->getMessage()]);
} catch (JsonException $e) {
    responderCola(422, ['success' => false, 'message' => 'El JSON de la solicitud no es valido.']);
} catch (Throwable $e) {
    error_log('No se pudo procesar la cola de barra: ' . (string) $e);
    responderCola(500, ['success' => false, 'message' => 'No se pudo consultar o actualizar la cola de barra.']);
}
