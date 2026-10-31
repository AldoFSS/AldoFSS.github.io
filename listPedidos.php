<?php
require_once __DIR__ . '/PHP/seguridad.php';
exigirAdministrador();
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#FFFFFF">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="IMG/cafeliz-pwa-192-v2.png">
    <title>Pedidos · Administración CaféLiz</title>
</head>
<body class="admin-page">
<?php include("includes/navbar.php"); ?>
<main class="admin-main">
    <header class="admin-heading admin-heading-stacked">
        <div><span class="admin-eyebrow">VENTAS</span><h1>Pedidos</h1><p>Revisa las órdenes y consulta el resumen de ventas.</p></div>
        <div class="admin-toolbar">
            <label for="productFilter">Mostrar por</label>
            <select id="productFilter" class="form-select">
                <option value="mas_vendidos">Productos Más Vendidos</option>
                <option value="total_mes">Total Vendido por Mes</option>
            </select>
            <button type="button" id="btnShowGraph" class="btn boton_m">
                <i class="bi bi-bar-chart" aria-hidden="true"></i> Mostrar gráfica
            </button>
        </div>
    </header>
    <section class="admin-card" aria-label="Historial de pedidos">
            <?php include("PHP/MostrarPedidos.php"); ?>
    </section>
    <section class="admin-card admin-chart" id="salesChartContainer" aria-label="Gráfica de ventas">
        <h2>Resumen de ventas</h2>
        <canvas id="salesChart"></canvas>
    </section>
</main>
<script>
    $(document).ready(function() {
        // Inicializar DataTables
        $("#table_User").DataTable({
            pageLength: 5,
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json",
            },
        });

        var productos = <?php echo $productos_json; ?>; // Productos y cantidades
        var ventasPorMes = <?php echo json_encode($ventasPorMes); ?>; // Ventas totales por mes
        var salesChart; // Variable para almacenar la instancia de la gráfica

        // Mostrar la gráfica al hacer clic en el botón
        $('#btnShowGraph').click(function() {
            var ctx = document.getElementById('salesChart').getContext('2d');

            // Destruir la gráfica anterior si existe
            if (salesChart) {
                salesChart.destroy();
            }

            var selectedFilter = $('#productFilter').val();
            var data, labels, label;

            if (selectedFilter === 'mas_vendidos') {
                labels = Object.keys(productos);
                data = Object.values(productos);
                label = 'Cantidad Vendida';
            } else if (selectedFilter === 'total_mes') {
                labels = Object.keys(ventasPorMes);
                data = Object.values(ventasPorMes);
                label = 'Total Vendido ($)';
            }

            // Crear una nueva gráfica
            salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        backgroundColor: 'rgba(255, 69, 0, 0.2)',
                        borderColor: '#FF4500',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    });
</script>

<script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.2/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.2/js/dataTables.bootstrap5.js"></script>
</body>
</html>