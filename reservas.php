<?php
require_once __DIR__ . '/PHP/seguridad.php';
iniciarSesionSegura();
require __DIR__ . '/BD/conexion.php';

$paquetes = [
    'medio_dia' => ['nombre' => 'Coworking medio día', 'horas' => 3, 'precio' => 220.00,
        'incluye' => '3 horas, una bebida de especialidad y repostería.'],
    'dia_completo' => ['nombre' => 'Coworking día completo', 'horas' => 6, 'precio' => 405.00,
        'incluye' => '6 horas, refill de café del día, un alimento salado y 10 impresiones.'],
];
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && empty($_GET)) {
    header('X-PWA-Cache-View: 1');
}

function intervaloReserva($fecha, $hora, $codigoPaquete, $paquetes)
{
    if (!isset($paquetes[$codigoPaquete])) {
        throw new InvalidArgumentException('Selecciona un pase válido.');
    }
    $formatoFecha = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    $formatoHora = DateTimeImmutable::createFromFormat('!H:i', $hora);
    if (!$formatoFecha || $formatoFecha->format('Y-m-d') !== $fecha ||
        !$formatoHora || $formatoHora->format('H:i') !== $hora) {
        throw new InvalidArgumentException('La fecha o la hora no son válidas.');
    }

    $inicio = new DateTimeImmutable($fecha . ' ' . $hora);
    $fin = $inicio->modify('+' . $paquetes[$codigoPaquete]['horas'] . ' hours');
    if ($inicio <= new DateTimeImmutable() || (int) $inicio->format('G') < 8 ||
        $inicio->format('i') !== '00' || $fin->format('H:i') > '20:00') {
        throw new InvalidArgumentException('El horario debe iniciar en una hora disponible y terminar a más tardar a las 20:00.');
    }
    return [$inicio, $fin];
}

$fecha = date('Y-m-d', strtotime('+1 day'));
$hora = '09:00';
$paqueteSeleccionado = 'medio_dia';
$requisito = '';
$error = '';
$checkoutDemo = null;
$enTransaccion = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    exigirPostLocal();
    $fecha = is_string($_POST['fecha'] ?? null) ? $_POST['fecha'] : '';
    $hora = is_string($_POST['hora'] ?? null) ? $_POST['hora'] : '';
    $paqueteSeleccionado = is_string($_POST['paquete'] ?? null) ? $_POST['paquete'] : '';
    $requisito = is_string($_POST['requisito'] ?? null) ? $_POST['requisito'] : '';
    $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';

    try {
        if (empty($_SESSION['ID_Usuario'])) {
            http_response_code(401);
            throw new InvalidArgumentException('Inicia sesión para reservar un espacio.');
        }
        if ($accion === 'preparar_pago_demo') {
            if (!in_array($requisito, ['', 'silla_ruedas', 'zurdo'], true)) {
                throw new InvalidArgumentException('El filtro de accesibilidad no es válido.');
            }
            $espacioId = enteroPositivo($_POST['espacio'] ?? null);
            list($inicio, $fin) = intervaloReserva($fecha, $hora, $paqueteSeleccionado, $paquetes);
            $inicioSql = $inicio->format('Y-m-d H:i:s');
            $finSql = $fin->format('Y-m-d H:i:s');
            $q = $connection->prepare(
                "SELECT e.ID_Espacio FROM espacios_reserva e
                WHERE e.ID_Espacio=? AND e.Activo=1
                    AND (? <> 'silla_ruedas' OR e.Silla_Ruedas=1)
                    AND (? <> 'zurdo' OR e.Zurdo=1)
                    AND NOT EXISTS (
                        SELECT 1 FROM reservas_espacios r
                        WHERE r.ID_Espacio=e.ID_Espacio AND r.Estado <> 'cancelada'
                            AND r.Inicio < ? AND r.Fin > ?
                    )"
            );
            $q->bind_param('issss', $espacioId, $requisito, $requisito, $finSql, $inicioSql);
            $q->execute();
            if (!$q->get_result()->num_rows) {
                throw new DomainException('Ese espacio ya no está disponible para el horario seleccionado.');
            }
            $_SESSION['reserva_pago_demo'] = [
                'usuario' => (int) $_SESSION['ID_Usuario'],
                'espacio' => $espacioId,
                'fecha' => $fecha,
                'hora' => $hora,
                'paquete' => $paqueteSeleccionado,
                'requisito' => $requisito
            ];
            header('Location: reservas.php?pago_demo=1', true, 303);
            exit;
        }

        if ($accion === 'rechazar_pago_demo') {
            unset($_SESSION['reserva_pago_demo']);
            header('Location: reservas.php?pago_demo=cancelado', true, 303);
            exit;
        }

        if ($accion !== 'aprobar_pago_demo') {
            throw new InvalidArgumentException('Selecciona una acción de pago de demostración válida.');
        }
        $checkout = $_SESSION['reserva_pago_demo'] ?? null;
        if (!is_array($checkout) || ($checkout['usuario'] ?? null) !== (int) $_SESSION['ID_Usuario']) {
            throw new InvalidArgumentException('La sesión de pago de demostración venció. Selecciona de nuevo el espacio.');
        }
        $espacioId = enteroPositivo($checkout['espacio'] ?? null);
        $fecha = is_string($checkout['fecha'] ?? null) ? $checkout['fecha'] : '';
        $hora = is_string($checkout['hora'] ?? null) ? $checkout['hora'] : '';
        $paqueteSeleccionado = is_string($checkout['paquete'] ?? null) ? $checkout['paquete'] : '';
        $requisito = is_string($checkout['requisito'] ?? null) ? $checkout['requisito'] : '';
        if (!in_array($requisito, ['', 'silla_ruedas', 'zurdo'], true)) {
            throw new InvalidArgumentException('El filtro de accesibilidad no es válido.');
        }
        list($inicio, $fin) = intervaloReserva($fecha, $hora, $paqueteSeleccionado, $paquetes);
        $paquete = $paquetes[$paqueteSeleccionado];
        $connection->begin_transaction();
        $enTransaccion = true;
        $bloqueo = $connection->prepare('SELECT ID_Espacio, Silla_Ruedas, Zurdo FROM espacios_reserva WHERE ID_Espacio=? AND Activo=1 FOR UPDATE');
        $bloqueo->bind_param('i', $espacioId);
        $bloqueo->execute();
        $espacio = $bloqueo->get_result()->fetch_assoc();
        if (!$espacio ||
            ($requisito === 'silla_ruedas' && !(int) $espacio['Silla_Ruedas']) ||
            ($requisito === 'zurdo' && !(int) $espacio['Zurdo'])) {
            throw new InvalidArgumentException('El espacio ya no está disponible para ese requisito.');
        }

        $inicioSql = $inicio->format('Y-m-d H:i:s');
        $finSql = $fin->format('Y-m-d H:i:s');
        $ocupado = $connection->prepare("SELECT ID_Reserva FROM reservas_espacios WHERE ID_Espacio=? AND Estado <> 'cancelada' AND Inicio < ? AND Fin > ? LIMIT 1");
        $ocupado->bind_param('iss', $espacioId, $finSql, $inicioSql);
        $ocupado->execute();
        if ($ocupado->get_result()->num_rows) {
            throw new DomainException('Ese espacio acaba de ser reservado. Elige otro horario o una mesa disponible.');
        }

        $usuarioId = (int) $_SESSION['ID_Usuario'];
        $precio = $paquete['precio'];
        $requisitoDb = $requisito === '' ? null : $requisito;
        $insertar = $connection->prepare('INSERT INTO reservas_espacios (ID_Usuario, ID_Espacio, Paquete, Inicio, Fin, Precio, Requisito, Estado) VALUES (?, ?, ?, ?, ?, ?, ?, \'pendiente\')');
        $insertar->bind_param('iisssds', $usuarioId, $espacioId, $paqueteSeleccionado, $inicioSql, $finSql, $precio, $requisitoDb);
        $insertar->execute();
        $reservaId = (int) $connection->insert_id;
        $referenciaPago = bin2hex(random_bytes(16));
        $pago = $connection->prepare('INSERT INTO pagos_demo_reserva (ID_Reserva, Referencia, Monto) VALUES (?, ?, ?)');
        $pago->bind_param('isd', $reservaId, $referenciaPago, $precio);
        $pago->execute();
        $recordatorioEn = $inicio->modify('-1 hour')->format('Y-m-d H:i:s');
        $recordatorio = $connection->prepare('INSERT INTO recordatorios_reserva (ID_Reserva, Enviar_En) VALUES (?, ?)');
        $recordatorio->bind_param('is', $reservaId, $recordatorioEn);
        $recordatorio->execute();
        $connection->commit();
        $enTransaccion = false;
        unset($_SESSION['reserva_pago_demo']);
        $_SESSION['reserva_demo_confirmada'] = true;
        try {
            require_once __DIR__ . '/PHP/notificaciones_push.php';
            $pushEnviado = enviarPushAUsuario($connection, $usuarioId, [
                'title' => 'Solicitud de espacio recibida',
                'body' => 'Pago de demostración aprobado (sin cobro real). Tu solicitud está pendiente de confirmación.',
                'url' => './reservas.php'
            ]);
            if (!$pushEnviado) {
                error_log('La reserva se registró, pero el aviso push inmediato no se pudo enviar; el recordatorio sigue programado.');
            }
        } catch (Throwable $errorPush) {
            error_log('La reserva se registró, pero falló el aviso push inmediato: ' . (string) $errorPush);
        }
        header('Location: reservas.php?reserva=demo_aprobado', true, 303);
        exit;
    } catch (Throwable $e) {
        if ($enTransaccion) {
            try {
                $connection->rollback();
            } catch (Throwable $rollbackError) {
                error_log((string) $rollbackError);
            }
        }
        if ($e instanceof InvalidArgumentException || $e instanceof DomainException) {
            if (http_response_code() < 400) {
                http_response_code($e instanceof DomainException ? 409 : 422);
            }
            $error = $e->getMessage();
        } else {
            error_log((string) $e);
            http_response_code(500);
            $error = 'No se pudo registrar la reserva. Inténtalo de nuevo más tarde.';
        }
    }
}

if (($_GET['pago_demo'] ?? '') === '1') {
    $checkout = $_SESSION['reserva_pago_demo'] ?? null;
    if (is_array($checkout) && ($checkout['usuario'] ?? null) === (int) ($_SESSION['ID_Usuario'] ?? 0)) {
        try {
            $fechaCheckout = is_string($checkout['fecha'] ?? null) ? $checkout['fecha'] : '';
            $horaCheckout = is_string($checkout['hora'] ?? null) ? $checkout['hora'] : '';
            $paqueteCheckout = is_string($checkout['paquete'] ?? null) ? $checkout['paquete'] : '';
            $requisitoCheckout = is_string($checkout['requisito'] ?? null) ? $checkout['requisito'] : '';
            $espacioCheckout = enteroPositivo($checkout['espacio'] ?? null);
            list($inicioCheckout, $finCheckout) = intervaloReserva($fechaCheckout, $horaCheckout, $paqueteCheckout, $paquetes);
            if (!in_array($requisitoCheckout, ['', 'silla_ruedas', 'zurdo'], true)) {
                throw new InvalidArgumentException('El filtro de accesibilidad no es válido.');
            }
            $consultaEspacio = $connection->prepare('SELECT Nombre FROM espacios_reserva WHERE ID_Espacio=? AND Activo=1');
            $consultaEspacio->bind_param('i', $espacioCheckout);
            $consultaEspacio->execute();
            $espacioCheckoutInfo = $consultaEspacio->get_result()->fetch_assoc();
            if (!$espacioCheckoutInfo) {
                throw new InvalidArgumentException('El espacio seleccionado ya no está disponible.');
            }
            $checkoutDemo = [
                'nombre' => $espacioCheckoutInfo['Nombre'],
                'fecha' => $fechaCheckout,
                'hora' => $horaCheckout,
                'inicio' => $inicioCheckout,
                'fin' => $finCheckout,
                'paquete' => $paqueteCheckout,
                'requisito' => $requisitoCheckout,
                'precio' => $paquetes[$paqueteCheckout]['precio']
            ];
        } catch (Throwable $e) {
            $error = $e->getMessage();
            unset($_SESSION['reserva_pago_demo']);
        }
    } else {
        $error = 'La sesión de pago de demostración venció. Selecciona de nuevo el espacio.';
        unset($_SESSION['reserva_pago_demo']);
    }
}

$fechaConsulta = $checkoutDemo ? $checkoutDemo['fecha'] : (is_string($_GET['fecha'] ?? null) ? $_GET['fecha'] : $fecha);
$horaConsulta = $checkoutDemo ? $checkoutDemo['hora'] : (is_string($_GET['hora'] ?? null) ? $_GET['hora'] : $hora);
$paqueteConsulta = $checkoutDemo ? $checkoutDemo['paquete'] : (is_string($_GET['paquete'] ?? null) ? $_GET['paquete'] : $paqueteSeleccionado);
$requisitoConsulta = $checkoutDemo ? $checkoutDemo['requisito'] : (is_string($_GET['requisito'] ?? null) ? $_GET['requisito'] : $requisito);
if (!isset($paquetes[$paqueteConsulta])) $paqueteConsulta = 'medio_dia';
if (!in_array($requisitoConsulta, ['', 'silla_ruedas', 'zurdo'], true)) $requisitoConsulta = '';

$disponibilidad = [];
$mensajeDisponibilidad = '';
$fechaConsultaObj = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaConsulta);
$horaConsultaObj = DateTimeImmutable::createFromFormat('!H:i', $horaConsulta);
if ($fechaConsultaObj && $fechaConsultaObj->format('Y-m-d') === $fechaConsulta &&
    $horaConsultaObj && $horaConsultaObj->format('H:i') === $horaConsulta) {
    try {
        list($inicioConsulta, $finConsulta) = intervaloReserva($fechaConsulta, $horaConsulta, $paqueteConsulta, $paquetes);
        $inicioSql = $inicioConsulta->format('Y-m-d H:i:s');
        $finSql = $finConsulta->format('Y-m-d H:i:s');
        $q = $connection->prepare(
            "SELECT e.ID_Espacio, e.Nombre, e.Silla_Ruedas, e.Zurdo, e.Conexiones,
                NOT EXISTS (
                    SELECT 1 FROM reservas_espacios r
                    WHERE r.ID_Espacio=e.ID_Espacio AND r.Estado <> 'cancelada'
                        AND r.Inicio < ? AND r.Fin > ?
                ) AS Disponible
            FROM espacios_reserva e
            WHERE e.Activo=1
                AND (? <> 'silla_ruedas' OR e.Silla_Ruedas=1)
                AND (? <> 'zurdo' OR e.Zurdo=1)
            ORDER BY e.ID_Espacio"
        );
        $q->bind_param('ssss', $finSql, $inicioSql, $requisitoConsulta, $requisitoConsulta);
        $q->execute();
        $disponibilidad = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (InvalidArgumentException $e) {
        $mensajeDisponibilidad = $e->getMessage();
    }
}
$connection->close();
$reservaDemoConfirmada = !empty($_SESSION['reserva_demo_confirmada']);
unset($_SESSION['reserva_demo_confirmada']);
$fechaMinima = date('Y-m-d');
$horaMaxima = 20 - $paquetes[$paqueteConsulta]['horas'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#FFFFFF">
    <title>Reserva tu espacio · Caféliz</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="IMG/cafeliz-pwa-192-v2.png">
    <link rel="stylesheet" href="CSS/reservas.css">
</head>
<body class="reservas-page" data-offline-view="true">
    <?php include 'includes/navbar.php'; ?>
    <main class="reservas-main">
        <header class="reservas-heading">
            <span class="reservas-eyebrow">TRABAJA A TU RITMO</span>
            <h1>Tu espacio, tu momento</h1>
            <p>Encuentra una mesa para estudiar o trabajar y reserva tu pase por horas.</p>
        </header>

        <?php if ($reservaDemoConfirmada): ?>
            <div class="reserva-aviso reserva-aviso-ok" role="status">
                Pago de demostración aprobado, sin cobro real. Tu solicitud quedó pendiente de confirmación y se programó un recordatorio para una hora antes.
            </div>
        <?php endif; ?>
        <?php if (($_GET['pago_demo'] ?? '') === 'cancelado'): ?>
            <div class="reserva-aviso" role="status">Pago de demostración rechazado. No se creó la reserva ni se realizó ningún cobro.</div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="reserva-aviso reserva-aviso-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($checkoutDemo): ?>
            <section class="checkout-demo" aria-labelledby="checkout-demo-titulo">
                <span class="reservas-eyebrow">PAGO DE DEMOSTRACIÓN</span>
                <h2 id="checkout-demo-titulo">Confirma tu pase</h2>
                <p><?php echo htmlspecialchars($checkoutDemo['nombre'], ENT_QUOTES, 'UTF-8'); ?> ·
                    <?php echo htmlspecialchars($checkoutDemo['inicio']->format('d/m/Y H:i'), ENT_QUOTES, 'UTF-8'); ?> a
                    <?php echo htmlspecialchars($checkoutDemo['fin']->format('H:i'), ENT_QUOTES, 'UTF-8'); ?></p>
                <strong>$<?php echo number_format($checkoutDemo['precio'], 2); ?> MXN</strong>
                <p class="checkout-demo-nota">Esta pantalla solo simula la aprobación o el rechazo. No solicites ni ingreses datos de tarjeta; no se realizará ningún cargo.</p>
                <div class="checkout-demo-acciones">
                    <form method="post" action="reservas.php">
                        <input type="hidden" name="accion" value="aprobar_pago_demo">
                        <button type="submit">Aprobar pago simulado</button>
                    </form>
                    <form method="post" action="reservas.php">
                        <input type="hidden" name="accion" value="rechazar_pago_demo">
                        <button type="submit" class="checkout-demo-rechazar">Rechazar y volver</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!$checkoutDemo): ?>
        <section class="pases-grid" aria-label="Pases de coworking">
            <?php foreach ($paquetes as $codigo => $pase): ?>
                <article class="pase-card <?php echo $paqueteConsulta === $codigo ? 'pase-activo' : ''; ?>">
                    <span><?php echo $pase['horas']; ?> HORAS</span>
                    <h2><?php echo htmlspecialchars($pase['nombre'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($pase['incluye'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <strong>$<?php echo number_format($pase['precio'], 2); ?> <small>MXN</small></strong>
                </article>
            <?php endforeach; ?>
        </section>

        <form class="busqueda-reservas" method="get" action="reservas.php">
            <label>Fecha
                <input type="date" name="fecha" min="<?php echo $fechaMinima; ?>"
                    value="<?php echo htmlspecialchars($fechaConsulta, ENT_QUOTES, 'UTF-8'); ?>" required>
            </label>
            <label>Hora de inicio
                <input type="time" name="hora" min="08:00" max="<?php echo sprintf('%02d:00', $horaMaxima); ?>"
                    step="3600" value="<?php echo htmlspecialchars($horaConsulta, ENT_QUOTES, 'UTF-8'); ?>" required>
            </label>
            <label>Pase
                <select name="paquete">
                    <?php foreach ($paquetes as $codigo => $pase): ?>
                        <option value="<?php echo $codigo; ?>" <?php echo $paqueteConsulta === $codigo ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pase['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Accesibilidad
                <select name="requisito">
                    <option value="" <?php echo $requisitoConsulta === '' ? 'selected' : ''; ?>>Todas las mesas</option>
                    <option value="silla_ruedas" <?php echo $requisitoConsulta === 'silla_ruedas' ? 'selected' : ''; ?>>Espacio para silla de ruedas</option>
                    <option value="zurdo" <?php echo $requisitoConsulta === 'zurdo' ? 'selected' : ''; ?>>Estación para persona zurda</option>
                </select>
            </label>
            <button type="submit">Ver disponibilidad <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </form>

        <section class="plano-section" aria-labelledby="plano-titulo">
            <div class="plano-heading">
                <div>
                    <span class="reservas-eyebrow">PLANO DE ESPACIOS</span>
                    <h2 id="plano-titulo">Elige tu mesa</h2>
                </div>
                <p>Plano inicial de referencia; confirma las medidas y características accesibles de tu local antes de publicarlo.</p>
            </div>
            <?php if (!$disponibilidad): ?>
                <p class="sin-espacios"><?php echo htmlspecialchars($mensajeDisponibilidad !== '' ? $mensajeDisponibilidad : 'No hay espacios para mostrar con esos datos.', ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else: ?>
                <div class="plano-mesas">
                    <?php foreach ($disponibilidad as $espacio): ?>
                        <article class="mesa-card <?php echo $espacio['Disponible'] ? 'mesa-libre' : 'mesa-ocupada'; ?>">
                            <div class="mesa-icono" aria-hidden="true"><i class="bi bi-cup-hot"></i></div>
                            <h3><?php echo htmlspecialchars($espacio['Nombre'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <ul>
                                <?php if ($espacio['Silla_Ruedas']): ?><li><i class="bi bi-universal-access" aria-hidden="true"></i> Acceso para silla de ruedas</li><?php endif; ?>
                                <?php if ($espacio['Zurdo']): ?><li><i class="bi bi-brightness-high" aria-hidden="true"></i> Estación para persona zurda</li><?php endif; ?>
                                <li><i class="bi bi-plug" aria-hidden="true"></i> <?php echo htmlspecialchars($espacio['Conexiones'], ENT_QUOTES, 'UTF-8'); ?></li>
                            </ul>
                            <?php if ($espacio['Disponible']): ?>
                                <?php if (!empty($_SESSION['ID_Usuario'])): ?>
                                    <form method="post" action="reservas.php">
                                        <input type="hidden" name="accion" value="preparar_pago_demo">
                                        <input type="hidden" name="espacio" value="<?php echo (int) $espacio['ID_Espacio']; ?>">
                                        <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($fechaConsulta, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="hora" value="<?php echo htmlspecialchars($horaConsulta, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="paquete" value="<?php echo htmlspecialchars($paqueteConsulta, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="requisito" value="<?php echo htmlspecialchars($requisitoConsulta, ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit">Continuar al pago de demostración</button>
                                    </form>
                                <?php else: ?>
                                    <a class="mesa-login" href="login.php">Inicia sesión para reservar</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="mesa-estado">No disponible</span>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        <p class="reserva-nota">El pago es una simulación académica sin cobro real. La reserva queda pendiente de confirmación y no genera credenciales Wi-Fi.</p>
    </main>
    <?php include 'includes/footer.html'; ?>
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
