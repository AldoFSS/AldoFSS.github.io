<?php require_once __DIR__ . '/seguridad.php'; exigirAdministrador(); ?>
<style>
    .table td, .table th {
        text-align: center; /* Centra el texto en las celdas */
    }
    
    .table .btn {
        margin: 0 5px; /* Espacio entre los botones */
    }
    
    .table .btn-group {
        display: flex;
        border-radius: 10px;
        justify-content: center; /* Centra los botones */
    }
</style>
<div class="container mt-4">
    <div class="table-responsive">
        <table class="table table-hover" id="table_User">
            <thead>
                <tr>
                    <th scope="col">ID_Orden</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Producto</th>
                    <th scope="col">Cantidad</th>
                    <th scope="col">Total</th>
                    <th scope="col">Fecha</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $productos = [];
                $ventasPorMes = [];
                $errorPedidos = false;
                try {
                    require __DIR__ . '/../BD/conexion.php';
                    $consulta = "SELECT ord.ID_Ordenes, u.nombre_usuario,
                        COALESCE(product.Product_Nombre, 'Producto no disponible') AS Product_Nombre,
                        COALESCE(DetOrd.Cantidad, 0) AS Cantidad,
                        COALESCE(DetOrd.Cantidad * COALESCE(DetOrd.Precio_Unitario, product.Costo), ord.Total) AS Total,
                        ord.Fecha_Compra
                        FROM ordenes AS ord
                        LEFT JOIN usuarios AS u ON u.id = ord.ID_Usuario
                        LEFT JOIN detallesorden AS DetOrd ON DetOrd.ID_Orden = ord.ID_Ordenes
                        LEFT JOIN menuproductos AS product ON product.id = DetOrd.ID_Productos
                        ORDER BY ord.ID_Ordenes DESC";
                    $resultado = $connection->query($consulta);
                } catch (mysqli_sql_exception $error) {
                    error_log('No se pudo cargar el historial de pedidos: ' . $error->getMessage());
                    $errorPedidos = true;
                }

                if ($errorPedidos):
            ?>
                <tr><td colspan="6" role="alert">No se pudo cargar el historial de pedidos. Revisa la conexión y las tablas de la base de datos.</td></tr>
            <?php
                elseif ($resultado->num_rows === 0):
            ?>
                <tr><td colspan="6">Todavía no hay pedidos registrados.</td></tr>
            <?php
                else:
                while ($fila = $resultado->fetch_assoc()):
                    echo "<tr>";
                    echo "<th scope='row'>" . htmlspecialchars((string) $fila['ID_Ordenes'], ENT_QUOTES, 'UTF-8') . "</th>";
                    echo "<td>" . htmlspecialchars((string) ($fila['nombre_usuario'] ?? 'Usuario no disponible'), ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars((string) $fila['Product_Nombre'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars((string) $fila['Cantidad'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars((string) $fila['Total'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars((string) $fila['Fecha_Compra'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "</tr>";

                    if ($fila['Cantidad'] > 0 && $fila['Product_Nombre'] !== 'Producto no disponible' &&
                        isset($productos[$fila['Product_Nombre']])) {
                        $productos[$fila['Product_Nombre']] += $fila['Cantidad'];
                    } elseif ($fila['Cantidad'] > 0 && $fila['Product_Nombre'] !== 'Producto no disponible') {
                        $productos[$fila['Product_Nombre']] = $fila['Cantidad'];
                    }

                    $mes = date('F Y', strtotime($fila['Fecha_Compra']));
                    if (isset($ventasPorMes[$mes])) {
                        $ventasPorMes[$mes] += $fila['Total'];
                    } else {
                        $ventasPorMes[$mes] = $fila['Total'];
                    }
                endwhile;
                endif;
                $productos_json = json_encode($productos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
                $ventasPorMes_json = json_encode($ventasPorMes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
            ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Pasar los productos y las ventas por mes a JavaScript
    var productos = <?php echo $productos_json; ?>;
    var ventasPorMes = <?php echo $ventasPorMes_json; ?>;
</script>