<?php
function descontarInsumosDePedido(mysqli $connection, array $cantidades, $orden): array
{
    $consumoPorInsumo = [];
    $q = $connection->prepare('SELECT ID_Insumo, Cantidad FROM recetas_insumos WHERE ID_Producto=? ORDER BY ID_Insumo');
    foreach ($cantidades as $productoId => $unidades) {
        $q->bind_param('i', $productoId);
        $q->execute();
        $recetas = $q->get_result();
        while ($receta = $recetas->fetch_assoc()) {
            $idInsumo = (int) $receta['ID_Insumo'];
            $consumo = (int) round((float) $receta['Cantidad'] * $unidades * 1000);
            $consumoPorInsumo[$idInsumo] = ($consumoPorInsumo[$idInsumo] ?? 0) + $consumo;
        }
    }
    if (!$consumoPorInsumo) return [];

    ksort($consumoPorInsumo, SORT_NUMERIC);
    $insumosBajos = [];
    $bloquear = $connection->prepare('SELECT Stock, Stock_Minimo FROM inventario_insumos WHERE ID_Insumo=? FOR UPDATE');
    $actualizar = $connection->prepare('UPDATE inventario_insumos SET Stock=? WHERE ID_Insumo=?');
    $movimiento = $connection->prepare(
        'INSERT INTO movimientos_inventario (ID_Insumo, ID_Orden, Tipo, Cantidad, Stock_Anterior, Stock_Nuevo, Nota)
        VALUES (?, ?, \'consumo\', ?, ?, ?, ?)'
    );
    $nota = 'Consumo por comanda #' . $orden;
    foreach ($consumoPorInsumo as $idInsumo => $cantidadMills) {
        $bloquear->bind_param('i', $idInsumo);
        $bloquear->execute();
        $fila = $bloquear->get_result()->fetch_assoc();
        if (!$fila) {
            throw new RuntimeException('Una receta referencia un insumo que ya no existe.');
        }
        $stockAnteriorMills = (int) round((float) $fila['Stock'] * 1000);
        $stockMinimoMills = (int) round((float) $fila['Stock_Minimo'] * 1000);
        $stockNuevoMills = $stockAnteriorMills - $cantidadMills;
        if ($stockAnteriorMills > $stockMinimoMills && $stockNuevoMills <= $stockMinimoMills) {
            $insumosBajos[] = $idInsumo;
        }
        $cantidad = number_format($cantidadMills / 1000, 3, '.', '');
        $stockAnterior = number_format($stockAnteriorMills / 1000, 3, '.', '');
        $stockNuevo = number_format($stockNuevoMills / 1000, 3, '.', '');
        $actualizar->bind_param('si', $stockNuevo, $idInsumo);
        $actualizar->execute();
        $movimiento->bind_param('iissss', $idInsumo, $orden, $cantidad, $stockAnterior, $stockNuevo, $nota);
        $movimiento->execute();
    }
    return $insumosBajos;
}
