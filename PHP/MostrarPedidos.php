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
                include('./BD/conexion.php');
                
                // Consulta para mostrar los pedidos en la tabla
                $consulta = "SELECT ord.ID_Ordenes, user.nombre_usuario, product.Product_Nombre, DetOrd.Cantidad, DetOrd.Cantidad * COALESCE(DetOrd.Precio_Unitario, product.Costo) as Total, ord.Fecha_Compra FROM usuarios as User INNER JOIN ordenes as ord ON User.id = ord.ID_Usuario INNER JOIN detallesorden as DetOrd ON DetOrd.ID_Orden = ord.ID_Ordenes INNER JOIN menuproductos as product ON DetOrd.ID_Productos = product.id ORDER BY ord.ID_Ordenes;";
                $resultado = mysqli_query($connection,$consulta);

                // Array para almacenar productos y sus cantidades
                $productos = [];
                // Array para almacenar ventas por mes
                $ventasPorMes = [];

                while($fila = mysqli_fetch_array($resultado)){
                    echo "<tr>";
                    echo "<th scope='row'>" . htmlspecialchars((string) $fila['ID_Ordenes'], ENT_QUOTES, 'UTF-8') . "</th>";
                    echo "<td>" . htmlspecialchars((string) $fila['nombre_usuario'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars((string) $fila['Product_Nombre'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars((string) $fila['Cantidad'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars((string) $fila['Total'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars((string) $fila['Fecha_Compra'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "</tr>";

                    // Agregar productos al array
                    if(isset($productos[$fila['Product_Nombre']])){
                        $productos[$fila['Product_Nombre']] += $fila['Cantidad'];
                    } else {
                        $productos[$fila['Product_Nombre']] = $fila['Cantidad'];
                    }

                    // Calcular total vendido por mes
                    $mes = date('F Y', strtotime($fila['Fecha_Compra'])); // Cambiar aquí para incluir el año
                    if(isset($ventasPorMes[$mes])){
                        $ventasPorMes[$mes] += $fila['Total'];
                    } else {
                        $ventasPorMes[$mes] = $fila['Total'];
                    }
                }

                // Convertir los arrays a JSON para pasarlos a JavaScript
                $productos_json = json_encode($productos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                $ventasPorMes_json = json_encode($ventasPorMes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
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