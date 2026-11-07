<?php
if (($_GET['pwa_offline'] ?? '') === '1') {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#FFFFFF">
        <title>Inventario y producción · Caféliz</title>
        <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="node_modules/bootstrap-icons/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="CSS/estilos.css?v=2">
        <link rel="stylesheet" href="CSS/admin.css?v=6">
        <link rel="stylesheet" href="CSS/inventario.css?v=2">
        <link rel="stylesheet" href="CSS/pwa.css">
    </head>
    <body class="admin-page inventario-page" data-offline-page="true">
        <nav class="offline-module-nav" aria-label="Navegación">
            <a href="./index.php">CAFELIZ</a>
            <span>Modo sin conexión</span>
        </nav>
        <main class="inventario-main">
            <header class="inventario-heading">
                <div>
                    <span>BACKOFFICE · CONTROL DE INSUMOS</span>
                    <h1>Inventario y hoja de producción</h1>
                    <p>Existencias, alertas y producción para hoy.</p>
                </div>
                <span class="inventario-link" aria-disabled="true">Pedidos <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
            </header>
            <p class="pwa-offline-banner" role="status">Vista sin conexión: las existencias y movimientos no se guardan en este dispositivo.</p>
            <section class="inventario-resumen" aria-label="Resumen de inventario">
                <article><span>Insumos registrados</span><strong>—</strong><small>Requiere conexión</small></article>
                <article><span>En mínimo o por debajo</span><strong>—</strong><small>Requiere conexión</small></article>
                <article><span>Caducan en los próximos 3 días</span><strong>—</strong><small>Requiere conexión</small></article>
                <article><span>Caducados</span><strong>—</strong><small>Requiere conexión</small></article>
            </section>
            <nav class="inventario-accesos" aria-label="Accesos rápidos">
                <a href="#produccion">Producción</a>
                <a href="#contexto">Clima y eventos</a>
                <a href="#catalogo">Insumos y recetas</a>
                <a href="#movimientos">Registrar movimiento</a>
                <a href="#historial">Historial</a>
            </nav>
            <section id="contexto" class="inventario-panel inventario-contexto">
                <div class="inventario-panel-heading">
                    <div><span class="inventario-eyebrow">PRONÓSTICO</span><h2>Clima y eventos</h2></div>
                </div>
                <p class="inventario-aviso" role="status">El pronóstico y los eventos se consultan al conectarte.</p>
                <p class="inventario-context-caption">La previsión es una referencia estadística.</p>
            </section>
            <section id="produccion" class="inventario-panel">
                <div class="inventario-panel-heading">
                    <div><span class="inventario-eyebrow">PRODUCCIÓN</span><h2>Producción para hoy</h2></div>
                </div>
                <p class="inventario-empty">Las sugerencias de producción no están guardadas offline.</p>
            </section>
            <section id="catalogo" class="inventario-panel">
                <div class="inventario-panel-heading">
                    <div><span class="inventario-eyebrow">CATÁLOGO</span><h2>Insumos y recetas</h2></div>
                </div>
                <p class="inventario-empty">Las existencias, el catálogo y las recetas requieren conexión.</p>
            </section>
            <section id="movimientos" class="inventario-panel">
                <div class="inventario-panel-heading">
                    <div><span class="inventario-eyebrow">MOVIMIENTOS</span><h2>Registrar movimiento</h2></div>
                </div>
                <p class="inventario-empty">Los movimientos no se pueden registrar sin conexión.</p>
                <div class="offline-module" data-offline-local="true" aria-labelledby="offline-inventario-title">
                    <h2 id="offline-inventario-title">Nota local de inventario</h2>
                    <p>Escribe un recordatorio para revisarlo al reconectar. No modifica existencias. Evita anotar datos sensibles.</p>
                    <label for="nota-inventario">Nota para revisar después</label>
                    <textarea id="nota-inventario" maxlength="1000" placeholder="Ej. Revisar existencia de leche de avena"></textarea>
                    <button type="button" id="guardar-nota-inventario">Guardar nota local</button>
                    <button type="button" class="secondary" id="borrar-nota-inventario">Borrar nota</button>
                    <output id="estado-nota-inventario" role="status" aria-live="polite"></output>
                </div>
            </section>
            <section id="historial" class="inventario-panel">
                <div class="inventario-panel-heading">
                    <div><span class="inventario-eyebrow">HISTORIAL</span><h2>Movimientos recientes</h2></div>
                </div>
                <p class="inventario-empty">El historial de movimientos requiere conexión.</p>
            </section>
            <section class="inventario-panel">
                <div class="inventario-panel-heading">
                    <div><span class="inventario-eyebrow">RECETARIO CONFIGURADO</span><h2>Consumos automáticos</h2></div>
                </div>
                <p class="inventario-empty">Las recetas se consultan al conectarte.</p>
            </section>
            <p class="inventario-disclaimer">No se registran cambios ni se sincroniza ningún movimiento automáticamente.</p>
        </main>
        <script src="JS/offline-experience.js" defer></script>
    </body>
    </html>
    <?php
    exit;
}

require_once __DIR__ . '/PHP/seguridad.php';
exigirAdministrador();
header('Cache-Control: no-store');
require __DIR__ . '/BD/conexion.php';
require_once __DIR__ . '/PHP/PronosticoInventario.php';

$productos = [];
$q = $connection->query('SELECT id, Product_Nombre FROM menuproductos ORDER BY Product_Nombre');
while ($row = $q->fetch_assoc()) $productos[] = $row;
$configuracionPronostico = $connection->query(
    'SELECT * FROM inventario_pronostico_config WHERE ID_Config=1'
)->fetch_assoc();
if (!$configuracionPronostico) {
    throw new RuntimeException('Aplica BD/migracion_pronostico_contextual.sql para habilitar el pronóstico contextual.');
}
$clima = cargarPronosticoClima($connection, $configuracionPronostico);
$sensibilidades = [];
$q = $connection->query(
    'SELECT p.id, COALESCE(s.Sensibilidad, \'neutra\') AS Sensibilidad
    FROM menuproductos p
    LEFT JOIN inventario_sensibilidad_clima s ON s.ID_Producto=p.id'
);
while ($row = $q->fetch_assoc()) {
    $sensibilidades[(int) $row['id']] = (string) $row['Sensibilidad'];
}
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
$movimientos = [];
$q = $connection->query(
    'SELECT m.ID_Movimiento, m.ID_Insumo, m.ID_Orden, m.Tipo, m.Cantidad,
        m.Stock_Anterior, m.Stock_Nuevo, m.Nota, m.Creado, i.Nombre, i.Unidad
    FROM movimientos_inventario m
    INNER JOIN inventario_insumos i ON i.ID_Insumo=m.ID_Insumo
    ORDER BY m.Creado DESC, m.ID_Movimiento DESC
    LIMIT 50'
);
while ($row = $q->fetch_assoc()) $movimientos[] = $row;

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

$zonaHoraria = new DateTimeZone('America/Mexico_City');
$inicioDia = new DateTimeImmutable('today', $zonaHoraria);
$finDia = $inicioDia->modify('+1 day');
$inicioDiaSql = $inicioDia->format('Y-m-d H:i:s');
$finDiaSql = $finDia->format('Y-m-d H:i:s');
$consultaEventos = $connection->prepare(
    'SELECT ID_Evento, Nombre, Descripcion, Inicio, Fin, Latitud, Longitud, Impacto_Porcentaje, Activo
    FROM inventario_eventos_locales
    WHERE Activo=1 AND Fin >= ? AND Inicio < ?
    ORDER BY Inicio'
);
$consultaEventos->bind_param('ss', $inicioDiaSql, $finDiaSql);
$consultaEventos->execute();
$resultadoEventos = $consultaEventos->get_result();
$eventosCercanos = [];
while ($evento = $resultadoEventos->fetch_assoc()) {
    $evento['DistanciaKm'] = distanciaKm(
        (float) $configuracionPronostico['Latitud'],
        (float) $configuracionPronostico['Longitud'],
        (float) $evento['Latitud'],
        (float) $evento['Longitud']
    );
    if ($evento['DistanciaKm'] <= 2.0) {
        $eventosCercanos[] = $evento;
    }
}
$eventosAdministracion = [];
$consultaEventosAdmin = $connection->prepare(
    'SELECT ID_Evento, Nombre, Descripcion, Inicio, Fin, Latitud, Longitud,
        Impacto_Porcentaje, Activo
    FROM inventario_eventos_locales
    WHERE Fin >= ?
    ORDER BY Inicio
    LIMIT 100'
);
$consultaEventosAdmin->bind_param('s', $inicioDiaSql);
$consultaEventosAdmin->execute();
$q = $consultaEventosAdmin->get_result();
while ($evento = $q->fetch_assoc()) {
    $evento['DistanciaKm'] = distanciaKm(
        (float) $configuracionPronostico['Latitud'],
        (float) $configuracionPronostico['Longitud'],
        (float) $evento['Latitud'],
        (float) $evento['Longitud']
    );
    $eventosAdministracion[] = $evento;
}

$ajusteEventos = max(0.0, min(200.0, array_sum(array_map(
    static function (array $evento): float {
        return (float) $evento['Impacto_Porcentaje'];
    },
    $eventosCercanos
))));
$demanda = calcularDemandaInsumos(
    $ventas,
    $recetas,
    $sensibilidades,
    $configuracionPronostico,
    $clima,
    $ajusteEventos
);
$ajusteClimaFria = calcularAjusteClima('bebida_fria', $clima, $configuracionPronostico);
$ajusteClimaCaliente = calcularAjusteClima('bebida_caliente', $clima, $configuracionPronostico);
$actualizacionClimaLocal = !empty($clima['actualizado'])
    ? new DateTimeImmutable($clima['actualizado'], $zonaHoraria)
    : null;
$connection->close();
$stockBajo = 0;
$caducanPronto = 0;
$caducados = 0;
$hoy = new DateTimeImmutable('today');
$fechaHoy = $hoy->format('Y-m-d');
$fechaLimiteCaducidad = $hoy->modify('+3 days')->format('Y-m-d');
foreach ($insumos as $insumo) {
    if ((float) $insumo['Stock'] <= (float) $insumo['Stock_Minimo']) $stockBajo++;
    if ($insumo['Caducidad'] !== null) {
        if ($insumo['Caducidad'] < $fechaHoy) {
            $caducados++;
        } elseif ($insumo['Caducidad'] <= $fechaLimiteCaducidad) {
            $caducanPronto++;
        }
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
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#FFFFFF">
    <title>Inventario y producción · Caféliz</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="IMG/cafeliz-pwa-192-v2.png">
    <link rel="stylesheet" href="CSS/inventario.css?v=2">
</head>
<body class="admin-page inventario-page" data-offline-view="true">
    <?php include 'includes/navbar.php'; ?>
    <main class="inventario-main">
        <header class="inventario-heading">
            <div>
                <span>BACKOFFICE · CONTROL DE INSUMOS</span>
                <h1>Inventario y hoja de producción</h1>
                <p>Existencias, alertas y producción para hoy.</p>
            </div>
            <a href="listPedidos.php" class="inventario-link">Pedidos <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </header>
        <?php if (($_GET['resultado'] ?? '') === 'ok'): ?>
            <div class="inventario-status" role="status">Inventario actualizado correctamente.</div>
        <?php endif; ?>
        <section class="inventario-resumen" aria-label="Resumen de inventario">
            <article><span>Insumos registrados</span><strong><?php echo count($insumos); ?></strong></article>
            <article class="<?php echo $stockBajo ? 'inventario-resumen-alerta' : 'inventario-resumen-ok'; ?>"><span>En mínimo o por debajo</span><strong><?php echo $stockBajo; ?></strong><small><?php echo $stockBajo ? 'Revisar existencias' : 'Todo en nivel'; ?></small></article>
            <article class="<?php echo $caducanPronto ? 'inventario-resumen-alerta' : 'inventario-resumen-ok'; ?>"><span>Caducan en los próximos 3 días</span><strong><?php echo $caducanPronto; ?></strong><small><?php echo $caducanPronto ? 'Priorizar su uso' : 'Sin caducidades próximas'; ?></small></article>
            <article class="<?php echo $caducados ? 'inventario-resumen-alerta' : 'inventario-resumen-ok'; ?>"><span>Caducados</span><strong><?php echo $caducados; ?></strong><small><?php echo $caducados ? 'Retirar y revisar' : 'Sin productos vencidos'; ?></small></article>
        </section>
        <nav class="inventario-accesos" aria-label="Accesos rápidos">
            <a href="#produccion">Producción</a>
            <a href="#contexto">Clima y eventos</a>
            <a href="#catalogo">Insumos y recetas</a>
            <a href="#movimientos">Registrar movimiento</a>
            <a href="#historial">Historial</a>
        </nav>
        <section id="contexto" class="inventario-panel inventario-contexto">
            <div class="inventario-panel-heading">
                <div><span class="inventario-eyebrow">PRONÓSTICO</span><h2>Clima y eventos</h2></div>
                <a class="inventario-clima-fuente" href="https://open-meteo.com/" target="_blank" rel="noopener noreferrer">Open-Meteo ↗</a>
            </div>
            <?php if (!empty($clima['disponible'])): ?>
                <div class="inventario-clima-resumen">
                    <strong><?php echo h($clima['descripcion']); ?></strong>
                    <span>Máxima prevista: <?php echo number_format($clima['temperatura_maxima'], 1); ?> °C</span>
                    <span>Lluvia <?php echo (int) $clima['probabilidad_precipitacion']; ?>%</span>
                    <span><?php echo number_format($clima['precipitacion_mm'], 1); ?> mm</span>
                    <small><?php echo h($actualizacionClimaLocal->format('H:i')); ?></small>
                </div>
                <?php if (!empty($clima['obsoleto'])): ?>
                    <p class="inventario-aviso" role="status"><?php echo h($clima['mensaje']); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p class="inventario-aviso" role="status"><?php echo h($clima['mensaje']); ?></p>
            <?php endif; ?>
            <p class="inventario-context-note">
                Frías <?php echo $ajusteClimaFria > 0 ? '+' . number_format($ajusteClimaFria, 0) . '%' : '—'; ?> ·
                Calientes <?php echo $ajusteClimaCaliente > 0 ? '+' . number_format($ajusteClimaCaliente, 0) . '%' : '—'; ?> ·
                Eventos +<?php echo number_format($ajusteEventos, 1); ?>%
            </p>
            <p class="inventario-context-caption">Estimación orientativa, no entrenada con históricos.</p>
            <details class="inventario-detalles">
                <summary>Ajustar ubicación y reglas</summary>
                <p class="inventario-help">Referencia: Tulancingo, Hidalgo · radio de eventos: 2 km. La lluvia no modifica la demanda.</p>
                <form action="PHP/FuncionesInventario.php" method="post" class="inventario-form inventario-config-form">
                <input type="hidden" name="accion" value="configurar_pronostico">
                <label>Latitud del café<input name="latitud" type="number" min="-90" max="90" step="0.000001" value="<?php echo h($configuracionPronostico['Latitud']); ?>" required></label>
                <label>Longitud del café<input name="longitud" type="number" min="-180" max="180" step="0.000001" value="<?php echo h($configuracionPronostico['Longitud']); ?>" required></label>
                <label>Máxima para bebidas frías (°C)<input name="umbral_fria" type="number" min="-30" max="60" step="0.1" value="<?php echo h($configuracionPronostico['Umbral_Bebida_Fria']); ?>" required></label>
                <label>Aumento para bebidas frías (%)<input name="ajuste_fria" type="number" min="0" max="100" step="1" value="<?php echo h($configuracionPronostico['Ajuste_Bebida_Fria']); ?>" required></label>
                <label>Máxima para bebidas calientes (°C)<input name="umbral_caliente" type="number" min="-30" max="60" step="0.1" value="<?php echo h($configuracionPronostico['Umbral_Bebida_Caliente']); ?>" required></label>
                <label>Aumento para bebidas calientes (%)<input name="ajuste_caliente" type="number" min="0" max="100" step="1" value="<?php echo h($configuracionPronostico['Ajuste_Bebida_Caliente']); ?>" required></label>
                <button type="submit">Guardar reglas y ubicación</button>
                </form>
            </details>
            <div class="inventario-eventos-heading">
                <h3>Eventos de hoy <span class="inventario-count"><?php echo count($eventosCercanos); ?></span></h3>
            </div>
            <?php if ($eventosCercanos): ?>
                <ul class="inventario-eventos-hoy">
                    <?php foreach ($eventosCercanos as $evento):
                        $inicioEventoLocal = new DateTimeImmutable($evento['Inicio'], $zonaHoraria);
                        $finEventoLocal = new DateTimeImmutable($evento['Fin'], $zonaHoraria);
                    ?>
                        <li><strong><?php echo h($evento['Nombre']); ?></strong>
                            <span><?php echo h($inicioEventoLocal->format('H:i')); ?>–<?php echo h($finEventoLocal->format('H:i')); ?> ·
                                <?php echo number_format($evento['DistanciaKm'], 2); ?> km · +<?php echo number_format((float) $evento['Impacto_Porcentaje'], 1); ?>%</span>
                            <?php if ($evento['Descripcion'] !== ''): ?><small><?php echo h($evento['Descripcion']); ?></small><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="inventario-empty">Sin eventos dentro de 2 km.</p>
            <?php endif; ?>
            <details class="inventario-detalles">
                <summary>Administrar eventos</summary>
                <p class="inventario-help">Agrega un evento cercano; su impacto es una estimación manual.</p>
                <form action="PHP/FuncionesInventario.php" method="post" class="inventario-form inventario-evento-form">
                <input type="hidden" name="accion" value="crear_evento_local">
                <label>Nombre del evento<input name="nombre_evento" maxlength="120" required></label>
                <label>Descripción opcional<input name="descripcion_evento" maxlength="255"></label>
                <label>Inicio<input name="inicio_evento" type="datetime-local" value="<?php echo h($inicioDia->format('Y-m-d')); ?>T16:00" required></label>
                <label>Fin<input name="fin_evento" type="datetime-local" value="<?php echo h($inicioDia->format('Y-m-d')); ?>T18:00" required></label>
                <label>Latitud del evento<input name="latitud_evento" type="number" min="-90" max="90" step="0.000001" value="<?php echo h($configuracionPronostico['Latitud']); ?>" required></label>
                <label>Longitud del evento<input name="longitud_evento" type="number" min="-180" max="180" step="0.000001" value="<?php echo h($configuracionPronostico['Longitud']); ?>" required></label>
                <label>Impacto estimado (%)<input name="impacto_evento" type="number" min="0" max="200" step="1" value="0" required></label>
                <button type="submit">Agregar evento</button>
                </form>
            <?php if ($eventosAdministracion): ?>
                <div class="table-responsive">
                    <table class="table inventario-table inventario-eventos-tabla">
                        <thead><tr><th>Evento</th><th>Fecha</th><th>Distancia</th><th>Impacto</th><th>Estado</th><th>Acción</th></tr></thead>
                        <tbody><?php foreach ($eventosAdministracion as $evento):
                            $inicioEventoLocal = new DateTimeImmutable($evento['Inicio'], $zonaHoraria);
                        ?>
                            <tr>
                                <th scope="row"><?php echo h($evento['Nombre']); ?><small><?php echo h($evento['Descripcion']); ?></small></th>
                                <td><?php echo h($inicioEventoLocal->format('d/m/Y H:i')); ?></td>
                                <td><?php echo number_format($evento['DistanciaKm'], 2); ?> km<?php echo $evento['DistanciaKm'] > 2 ? ' · fuera del radio' : ''; ?></td>
                                <td>+<?php echo number_format((float) $evento['Impacto_Porcentaje'], 1); ?>%</td>
                                <td><?php echo (int) $evento['Activo'] === 1 ? 'Activo' : 'Pausado'; ?></td>
                                <td><form action="PHP/FuncionesInventario.php" method="post">
                                    <input type="hidden" name="accion" value="alternar_evento_local">
                                    <input type="hidden" name="id_evento" value="<?php echo (int) $evento['ID_Evento']; ?>">
                                    <button type="submit"><?php echo (int) $evento['Activo'] === 1 ? 'Pausar' : 'Activar'; ?></button>
                                </form></td>
                            </tr>
                        <?php endforeach; ?></tbody>
                    </table>
                </div>
            <?php endif; ?>
            </details>
        </section>
        <section id="produccion" class="inventario-panel">
            <div class="inventario-panel-heading">
                <div><span class="inventario-eyebrow">PRODUCCIÓN</span><h2>Hoja sugerida</h2></div>
                <p>Basada en las últimas cuatro semanas.</p>
            </div>
            <?php if (!$insumos): ?>
                <p class="inventario-empty">Registra insumos para ver existencias y recomendaciones de producción.</p>
            <?php else: ?>
                <div class="inventario-filtros" data-inventario-filtros>
                    <label>Buscar insumo<input type="search" data-inventario-busqueda placeholder="Ej. leche, café..." autocomplete="off"></label>
                    <label>Mostrar<select data-inventario-estado>
                        <option value="todos">Todos los insumos</option>
                        <option value="bajo">Stock bajo</option>
                        <option value="proximo">Caducidad próxima</option>
                        <option value="caducado">Caducados</option>
                    </select></label>
                    <button type="button" class="inventario-limpiar" data-inventario-limpiar>Limpiar filtros</button>
                    <p data-inventario-conteo role="status" aria-live="polite"></p>
                </div>
                <div class="table-responsive">
                    <table class="table inventario-table">
                        <thead><tr><th>Insumo</th><th>Stock</th><th>Mínimo</th><th>Demanda base</th><th>Ajuste</th><th>Demanda final</th><th>Reponer</th><th>Caducidad</th></tr></thead>
                        <tbody>
                        <?php foreach ($insumos as $insumo):
                            $id = (int) $insumo['ID_Insumo'];
                            $stockMills = (int) round((float) $insumo['Stock'] * 1000);
                            $minimoMills = (int) round((float) $insumo['Stock_Minimo'] * 1000);
                            $demandaBaseMills = (int) round(($demanda[$id]['base'] ?? 0) * 1000);
                            $demandaMills = (int) round(($demanda[$id]['ajustada'] ?? 0) * 1000);
                            $ajustePorcentaje = $demanda[$id]['porcentaje'] ?? 0;
                            $pedidoMills = max(0, $demandaMills + $minimoMills - $stockMills);
                            $caducidad = $insumo['Caducidad'] ?? '';
                            $estadoStock = $stockMills <= $minimoMills ? 'bajo' : 'ok';
                            $estadoCaducidad = $caducidad === '' ? 'ninguna'
                                : ($caducidad < $fechaHoy ? 'caducado'
                                    : ($caducidad <= $fechaLimiteCaducidad ? 'proximo' : 'ok'));
                            $porcentajeStock = $minimoMills > 0
                                ? min(100, (int) round($stockMills * 100 / $minimoMills))
                                : ($stockMills > 0 ? 100 : 0);
                        ?>
                            <tr data-inventario-fila data-stock="<?php echo $estadoStock; ?>" data-caducidad="<?php echo $estadoCaducidad; ?>">
                                <th scope="row"><?php echo h($insumo['Nombre']); ?><small><?php echo h($insumo['Unidad']); ?></small></th>
                                <td>
                                    <strong class="<?php echo $estadoStock === 'bajo' ? 'inventario-alerta' : 'inventario-ok'; ?>"><?php echo number_format((float) $insumo['Stock'], 3); ?> <?php echo h($insumo['Unidad']); ?></strong>
                                    <div class="inventario-nivel" role="progressbar" aria-label="Existencia de <?php echo h($insumo['Nombre']); ?> comparada con el mínimo" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $porcentajeStock; ?>">
                                        <span class="<?php echo $estadoStock === 'bajo' ? 'inventario-nivel-bajo' : ''; ?>" style="width: <?php echo $porcentajeStock; ?>%"></span>
                                    </div>
                                    <small class="inventario-nivel-texto"><?php echo $estadoStock === 'bajo' ? 'Por debajo del mínimo' : 'Sobre el mínimo'; ?></small>
                                </td>
                                <td><?php echo number_format((float) $insumo['Stock_Minimo'], 3); ?></td>
                                <td><?php echo number_format($demandaBaseMills / 1000, 3) . ' ' . h($insumo['Unidad']); ?></td>
                                <td><?php echo $ajustePorcentaje > 0 ? '+' . number_format($ajustePorcentaje, 1) . '%' : 'Sin ajuste'; ?></td>
                                <td><?php echo number_format($demandaMills / 1000, 3) . ' ' . h($insumo['Unidad']); ?></td>
                                <td><strong class="<?php echo $pedidoMills > 0 ? 'inventario-alerta' : 'inventario-ok'; ?>">
                                    <?php echo number_format($pedidoMills / 1000, 3) . ' ' . h($insumo['Unidad']); ?>
                                </strong></td>
                                <td><?php if ($caducidad !== ''): ?>
                                    <span class="<?php echo $estadoCaducidad === 'ok' ? 'inventario-ok' : 'inventario-alerta'; ?>"><?php echo h($caducidad); ?></span>
                                    <small class="inventario-caducidad-estado"><?php echo $estadoCaducidad === 'caducado' ? 'Caducado' : ($estadoCaducidad === 'proximo' ? 'Próxima' : 'Vigente'); ?></small>
                                <?php else: ?>—<?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-inventario-sin-resultados hidden><td colspan="8">No hay insumos que coincidan con estos filtros.</td></tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        <div id="catalogo" class="inventario-columns">
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
        <section class="inventario-panel inventario-productos-clima">
            <details class="inventario-detalles">
                <summary>Elegir qué productos responden al clima</summary>
                <p class="inventario-help">Solo las bebidas frías o calientes reciben ajuste.</p>
            <div class="inventario-sensibilidades">
                <?php foreach ($productos as $producto): ?>
                    <form action="PHP/FuncionesInventario.php" method="post" class="inventario-sensibilidad-form">
                        <input type="hidden" name="accion" value="sensibilidad_clima">
                        <input type="hidden" name="id_producto" value="<?php echo (int) $producto['id']; ?>">
                        <strong><?php echo h($producto['Product_Nombre']); ?></strong>
                        <label>Sensibilidad<select name="sensibilidad">
                            <option value="neutra" <?php echo ($sensibilidades[(int) $producto['id']] ?? 'neutra') === 'neutra' ? 'selected' : ''; ?>>Neutra</option>
                            <option value="bebida_fria" <?php echo ($sensibilidades[(int) $producto['id']] ?? 'neutra') === 'bebida_fria' ? 'selected' : ''; ?>>Bebida fría</option>
                            <option value="bebida_caliente" <?php echo ($sensibilidades[(int) $producto['id']] ?? 'neutra') === 'bebida_caliente' ? 'selected' : ''; ?>>Bebida caliente</option>
                        </select></label>
                        <button type="submit">Guardar</button>
                    </form>
                <?php endforeach; ?>
            </div>
            </details>
        </section>
        <section id="movimientos" class="inventario-panel">
            <div class="inventario-panel-heading"><div><span class="inventario-eyebrow">MOVIMIENTOS</span><h2>Registrar entradas y mermas</h2></div></div>
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
        <section id="historial" class="inventario-panel">
            <div class="inventario-panel-heading">
                <div><span class="inventario-eyebrow">TRAZABILIDAD</span><h2>Historial de movimientos</h2></div>
                <p>Consulta las últimas 50 entradas, mermas y salidas por pedido.</p>
            </div>
            <?php if (!$movimientos): ?>
                <p class="inventario-empty">Aún no hay movimientos registrados. Las entradas, mermas y consumos aparecerán aquí.</p>
            <?php else: ?>
                <div class="inventario-filtros inventario-filtros-historial">
                    <label>Buscar por insumo o nota<input type="search" data-movimientos-busqueda placeholder="Ej. leche, recepción..." autocomplete="off"></label>
                    <label>Tipo de movimiento<select data-movimientos-tipo>
                        <option value="todos">Todos</option>
                        <option value="inicial">Stock inicial</option>
                        <option value="entrada">Entradas</option>
                        <option value="merma">Mermas</option>
                        <option value="consumo">Consumos por pedido</option>
                    </select></label>
                    <p data-movimientos-conteo role="status" aria-live="polite"></p>
                </div>
                <div class="table-responsive">
                    <table class="table inventario-table inventario-historial">
                        <thead><tr><th>Fecha</th><th>Insumo</th><th>Movimiento</th><th>Cantidad</th><th>Stock anterior</th><th>Stock nuevo</th><th>Detalle</th></tr></thead>
                        <tbody>
                        <?php $tiposMovimiento = [
                            'inicial' => 'Stock inicial',
                            'entrada' => 'Entrada',
                            'merma' => 'Merma',
                            'consumo' => 'Consumo por pedido'
                        ]; ?>
                        <?php foreach ($movimientos as $movimiento):
                        ?>
                            <tr data-movimiento-fila data-tipo="<?php echo h($movimiento['Tipo']); ?>">
                                <td><?php echo h(date('d/m/Y H:i', strtotime($movimiento['Creado']))); ?></td>
                                <th scope="row"><?php echo h($movimiento['Nombre']); ?><small><?php echo h($movimiento['Unidad']); ?></small></th>
                                <td><span class="inventario-movimiento inventario-movimiento-<?php echo h($movimiento['Tipo']); ?>"><?php echo h($tiposMovimiento[$movimiento['Tipo']]); ?></span></td>
                                <td><?php echo number_format((float) $movimiento['Cantidad'], 3) . ' ' . h($movimiento['Unidad']); ?></td>
                                <td><?php echo number_format((float) $movimiento['Stock_Anterior'], 3); ?></td>
                                <td><?php echo number_format((float) $movimiento['Stock_Nuevo'], 3); ?></td>
                                <td><?php echo h($movimiento['Nota'] !== '' ? $movimiento['Nota'] : ($movimiento['ID_Orden'] ? 'Pedido #' . $movimiento['ID_Orden'] : '—')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-movimientos-sin-resultados hidden><td colspan="7">No hay movimientos que coincidan con estos filtros.</td></tr>
                        </tbody>
                    </table>
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
    <script src="JS/inventario.js" defer></script>
</body>
</html>
