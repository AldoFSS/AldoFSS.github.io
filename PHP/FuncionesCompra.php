<?php
session_start();
include('../BD/conexion.php');

// Habilitar la visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Obtener los datos del carrito y el monto total directamente de $_POST
        $data = json_decode(file_get_contents("php://input"), true);
        $cart = $data['cart'] ?? null;
        $totalAmount = $data['total'] ?? null;
        $id_usuario = $_SESSION['ID_Usuario'];

        // Inserción de la orden
        $query = "INSERT INTO ordenes (ID_Usuario, Total, Fecha_Compra) VALUES (?, ?, NOW())";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('id', $id_usuario, $totalAmount);

        if (!$stmt->execute()) {
            throw new Exception('Error al insertar la orden: ' . $stmt->error);
        }

        // Obtener el ID de la orden
        $id_orden = $connection->insert_id;

        // Variable para verificar inserciones
        $productosInsertadosCorrectamente = true;

        // Insertar detalles de la orden
        foreach ($cart as $item) {
            $id_producto = $item['id'];
            $cantidad = $item['quantity'];

            $queryDetalle = "INSERT INTO detallesorden (ID_orden, ID_Productos, Cantidad) VALUES (?, ?, ?)";
            $stmtDetalle = $connection->prepare($queryDetalle);
            $stmtDetalle->bind_param('iii', $id_orden, $id_producto, $cantidad);

            if (!$stmtDetalle->execute()) {
                $productosInsertadosCorrectamente = false; // Cambia a false si hay un error en la inserción
            }
        }

        // Obtener los detalles de la orden para el correo
        $queryEmail = "
            SELECT o.Total, o.Fecha_Compra, u.nombre_usuario as userName, p.Product_Nombre as Nombre_Producto, d.Cantidad 
            FROM ordenes o 
            JOIN usuarios u ON o.ID_Usuario = u.id 
            JOIN detallesorden d ON o.ID_Ordenes = d.ID_Orden 
            JOIN menuproductos p ON d.ID_Productos = p.id
            WHERE o.ID_Ordenes = ?
        ";
        $stmtEmail = $connection->prepare($queryEmail);
        $stmtEmail->bind_param('i', $id_orden);
        $stmtEmail->execute();
        $result = $stmtEmail->get_result();

        $productos = [];
        while ($row = $result->fetch_assoc()) {
            $productos[] = [
                'Nombre_Producto' => $row['Nombre_Producto'],
                'Cantidad' => $row['Cantidad']
            ];
            $total = $row['Total'];
            $fecha = $row['Fecha_Compra'];
            $userName = $row['userName'];
        }

        // Responder con los datos necesarios para el email
        echo json_encode([
            'success' => true,
            'productosInsertados' => $productosInsertadosCorrectamente, // Agrega esta línea
            'data' => [
                'userName' => $userName,
                'total' => $total,
                'fecha' => $fecha,
                'productos' => $productos
            ]
        ]);
    } 
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($stmtDetalle)) $stmtDetalle->close();
    $connection->close();
}
?>
