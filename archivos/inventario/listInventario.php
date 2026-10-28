<?php
require_once __DIR__ . '/PHP/seguridad.php';
exigirAdministrador();
header('Cache-Control: no-store');
require __DIR__ . '/BD/conexion.php';

$productos = [];
$q = $connection->query('SELECT id, Product_Nombre FROM menuproductos ORDER BY Product_Nombre');
while ($row = $q->fetch_assoc()) $productos[] = $row;
$insumos = [];
$q = $connection->query('SELECT * FROM inventario_insumos ORDER BY Nombre');
while ($row = $q->fetch_assoc()) $insumos[] = $row;
$recetas = [];
$q = $connection->query(
    'SELECT r.ID_Producto, r.ID_Insumo, r.Cantidad, p.Product_Nombre, i.Nombre, i.Unidad
    FROM recetas_insumos r
    INNER JOIN menuproductos p ON p.id=r.ID_Producto
    INNER JOIN inventario_insumos i ON i.ID_Insumo=r.ID_Insumo
    ORDER BY p.Product_Nombre, i.Nombre'
);
while ($row = $q->fetch_assoc()) $recetas[] = $row;

$ventas = [];
$q = $connection->query(
    'SELECT d.ID_Productos, SUM(d.Cantidad) AS Unidades
    FROM ordenes o
    INNER JOIN detallesorden d ON d.ID_Orden=o.ID_Ordenes
    WHERE o.Fecha_Compra >= DATE_SUB(CURRENT_DATE(), INTERVAL 28 DAY)
        AND o.Fecha_Compra < CURRENT_DATE()
        AND DAYOFWEEK(o.Fecha_Compra)=DAYOFWEEK(CURRENT_DATE())
    GROUP BY d.ID_Productos'
);
while ($row = $q->fetch_assoc()) $ventas[(int) $row['ID_Productos']] = (float) $row['Unidades'];
$connection->close();

$demanda = [];
foreach ($recetas as $receta) {
    $ventasMismoDia = $ventas[(int) $receta['ID_Producto']] ?? 0;
    $cantidad = (int) round($ventasMismoDia * (float) $receta['Cantidad'] * 1000 / 4);
    $idInsumo = (int) $receta['ID_Insumo'];
    $demanda[$idInsumo] = ($demanda[$idInsumo] ?? 0) + $cantidad;
}
$stockBajo = 0;
$caducanPronto = 0;
$hoy = new DateTimeImmutable('today');
foreach ($insumos as $insumo) {
    if ((float) $insumo['Stock'] <= (float) $insumo['Stock_Minimo']) $stockBajo++;
    if ($insumo['Caducidad'] !== null) {
        $caducidad = new DateTimeImmutable($insumo['Caducidad']);
        if ($caducidad <= $hoy->modify('+3 days')) $caducanPronto++;
    }
}
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FF4500">
    <title>Inventario y producción · Caféliz</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="IMG/pwa-icon.svg">
    <link rel="stylesheet" href="CSS/inventario.css">
</head>
<body class="admin-page inventario-page">
    <?php include 'includes/navbar.php'; ?>
    <main class="inventario-main">
        <header class="inventario-heading">
            <div>
                <span>BACKOFFICE · CONTROL DE INSUMOS</span>
                <h1>Inventario y hoja de producción</h1>
                <p>Pronóstico de hoy según las cuatro semanas previas del mismo día de la semana.</p>
            </div>
            <a href="listPedidos.php" class="inventario-link">Ver pedidos <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </header>
        <?php if (($_GET['resultado'] ?? '') === 'ok'): ?>
            <div class="inventario-status" role="status">Inventario actualizado correctamente.</div>
        <?php endif; ?>
        <section class="inventario-resumen" aria-label="Resumen de inventario">
            <article><span>Insumos registrados</span><strong><?php echo count($insumos); ?></strong></article>
            <article><span>En mínimo o por debajo</span><strong><?php echo $stockBajo; ?></strong></article>
            <article><span>Caducan en 3 días o menos</span><strong><?php echo $caducanPronto; ?></strong></article>
        </section>
        <section class="inventario-panel">
            <div class="inventario-panel-heading">
                <div><span class="inventario-eyebrow">PRODUCCIÓN DE HOY</span><h2>Hoja sugerida</h2></div>
                <p>Venta promedio del mismo día durante las últimas cuatro semanas. Completa las recetas para generar previsiones.</p>
            </div>
            <?php if (!$insumos): ?>
                <p class="inventario-empty">Registra insumos para ver existencias y recomendaciones de producción.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table inventario-table">
                        <thead><tr><th>Insumo</th><th>Existencia</th><th>Mínimo</th><th>Demanda estimada</th><th>Sugerencia de reposición</th><th>Caducidad</th></tr></thead>
                        <tbody>
                        <?php foreach ($insumos as $insumo):
                            $id = (int) $insumo['ID_Insumo'];
                            $stockMills = (int) round((float) $insumo['Stock'] * 1000);
                            $minimoMills = (int) round((float) $insumo['Stock_Minimo'] * 1000);
                            $demandaMills = $demanda[$id] ?? 0;
                            $pedidoMills = max(0, $demandaMills + $minimoMills - $stockMills);
                            $caducidad = $insumo['Caducidad'] ?? '';
                            $porCaducar = $caducidad !== '' && $caducidad <= $hoy->modify('+3 days')->format('Y-m-d');
                        ?>
                            <tr>
                                <th scope="row"><?php echo h($insumo['Nombre']); ?><small><?php echo h($insumo['Unidad']); ?></small></th>
                                <td><?php echo number_format((float) $insumo['Stock'], 3); ?></td>
                                <td><?php echo number_format((float) $insumo['Stock_Minimo'], 3); ?></td>
                                <td><?php echo number_format($demandaMills / 1000, 3); ?></td>
                                <td><strong class="<?php echo $pedidoMills > 0 ? 'inventario-alerta' : 'inventario-ok'; ?>">
                                    <?php echo number_format($pedidoMills / 1000, 3) . ' ' . h($insumo['Unidad']); ?>
                                </strong></td>
                                <td><?php if ($caducidad !== ''): ?><span class="<?php echo $porCaducar ? 'inventario-alerta' : ''; ?>"><?php echo h($caducidad); ?></span><?php else: ?>—<?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        <div class="inventario-columns">
            <section class="inventario-panel">
                <div class="inventario-panel-heading"><div><span class="inventario-eyebrow">CATÁLOGO</span><h2>Registrar insumo</h2></div></div>
                <form action="PHP/FuncionesInventario.php" method="post" class="inventario-form">
                    <input type="hidden" name="accion" value="crear_insumo">
                    <label>Nombre<input name="nombre" maxlength="100" required placeholder="Leche de avena"></label>
                    <label>Unidad<input name="unidad" maxlength="20" required placeholder="litros, kg, piezas"></label>
                    <label>Existencia inicial<input name="stock" type="number" min="0" step="0.001" value="0" required></label>
                    <label>Stock mínimo<input name="minimo" type="number" min="0" step="0.001" value="0" required></label>
                    <label>Caducidad<input name="caducidad" type="date"></label>
                    <label>Fecha de apertura<input name="apertura" type="date"></label>
                    <button type="submit">Guardar insumo</button>
                </form>
            </section>
            <section class="inventario-panel">
                <div class="inventario-panel-heading"><div><span class="inventario-eyebrow">RECETAS BASE</span><h2>Consumo por producto</h2></div></div>
                <?php if (!$insumos || !$productos): ?>
                    <p class="inventario-empty">Registra al menos un producto y un insumo para definir recetas.</p>
                <?php else: ?>
                    <form action="PHP/FuncionesInventario.php" method="post" class="inventario-form">
                        <input type="hidden" name="accion" value="receta">
                        <label>Producto<select name="id_producto" required><?php foreach ($productos as $producto): ?>
                            <option value="<?php echo (int) $producto['id']; ?>"><?php echo h($producto['Product_Nombre']); ?></option>
                        <?php endforeach; ?></select></label>
                        <label>Insumo<select name="id_insumo" required><?php foreach ($insumos as $insumo): ?>
                            <option value="<?php echo (int) $insumo['ID_Insumo']; ?>"><?php echo h($insumo['Nombre'] . ' (' . $insumo['Unidad'] . ')'); ?></option>
                        <?php endforeach; ?></select></label>
                        <label>Cantidad usada por producto<input name="cantidad" type="number" min="0" step="0.001" required></label>
                        <p class="inventario-help">Usa 0 para quitar esa combinación de la receta.</p>
                        <button type="submit">Guardar receta</button>
                    </form>
                <?php endif; ?>
            </section>
        </div>
        <section class="inventario-panel">
            <div class="inventario-panel-heading"><div><span class="inventario-eyebrow">MOVIMIENTOS</span><h2>Entradas y mermas</h2></div></div>
            <?php if (!$insumos): ?><p class="inventario-empty">Primero registra los insumos.</p><?php else: ?>
                <div class="movimientos-grid">
                    <?php foreach ($insumos as $insumo): ?>
                        <form action="PHP/FuncionesInventario.php" method="post" class="movimiento-form">
                            <input type="hidden" name="accion" value="movimiento">
                            <input type="hidden" name="id_insumo" value="<?php echo (int) $insumo['ID_Insumo']; ?>">
                            <strong><?php echo h($insumo['Nombre']); ?> <small>(<?php echo h($insumo['Unidad']); ?>)</small></strong>
                            <span>Stock actual: <?php echo number_format((float) $insumo['Stock'], 3); ?></span>
                            <select name="tipo" aria-label="Tipo de movimiento de <?php echo h($insumo['Nombre']); ?>">
                                <option value="entrada">Entrada</option><option value="merma">Merma</option>
                            </select>
                            <input type="number" name="cantidad" min="0.001" step="0.001" required aria-label="Cantidad">
                            <input type="text" name="nota" maxlength="255" placeholder="Nota opcional" aria-label="Nota opcional">
                            <button type="submit">Registrar</button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <section class="inventario-panel">
            <div class="inventario-panel-heading"><div><span class="inventario-eyebrow">RECETARIO CONFIGURADO</span><h2>Consumos automáticos</h2></div></div>
            <?php if (!$recetas): ?><p class="inventario-empty">Todavía no hay recetas configuradas.</p><?php else: ?>
                <div class="table-responsive"><table class="table inventario-table">
                    <thead><tr><th>Producto</th><th>Insumo</th><th>Cantidad por venta</th></tr></thead>
                    <tbody><?php foreach ($recetas as $receta): ?><tr>
                        <th scope="row"><?php echo h($receta['Product_Nombre']); ?></th>
                        <td><?php echo h($receta['Nombre']); ?></td>
                        <td><?php echo number_format((float) $receta['Cantidad'], 3) . ' ' . h($receta['Unidad']); ?></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </section>
        <p class="inventario-disclaimer">La previsión es una referencia estadística, no una recomendación automática de compra. Configura las recetas con las cantidades reales del negocio. No se conectan APIs de clima/eventos ni se envían órdenes a proveedores.</p>
    </main>
    <?php include 'includes/footer.html'; ?>
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
