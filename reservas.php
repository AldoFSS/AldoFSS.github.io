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
        <title>Reserva tu espacio · Caféliz</title>
        <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="node_modules/bootstrap-icons/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="CSS/estilos.css?v=2">
        <link rel="stylesheet" href="CSS/admin.css?v=6">
        <link rel="stylesheet" href="CSS/reservas.css?v=14">
        <link rel="stylesheet" href="CSS/pwa.css">
    </head>
    <body class="reservas-page" data-offline-page="true">
        <nav class="offline-module-nav" aria-label="Navegación">
            <a href="./index.php">CAFELIZ</a>
            <span>Modo sin conexión</span>
        </nav>
        <main class="reservas-main">
            <header class="reservas-heading">
                <div class="reservas-intro">
                    <span class="reservas-eyebrow">COWORKING EN CAFÉLIZ</span>
                    <h1>Un buen café.<br>Un espacio para tus ideas.</h1>
                    <p>Elige cuánto tiempo necesitas, consulta qué mesas están libres y prepara tu próxima sesión de estudio o trabajo.</p>
                    <div class="reservas-beneficios" aria-label="Detalles del espacio">
                        <span><i class="bi bi-clock" aria-hidden="true"></i> Pases de 3 o 6 horas</span>
                        <span><i class="bi bi-calendar-check" aria-hidden="true"></i> Disponibilidad por horario</span>
                    </div>
                </div>
                <figure class="reservas-hero-photo">
                    <img src="IMG/f5.webp" alt="Bebida de Caféliz servida en una taza">
                    <figcaption>
                        <span><i class="bi bi-cup-hot" aria-hidden="true"></i> Un espacio para tus ideas</span>
                        <strong>Café y algo rico para acompañar tu sesión.</strong>
                    </figcaption>
                </figure>
            </header>
            <p class="pwa-offline-banner" role="status">Estás viendo la página de reservas sin conexión. No se consulta ni confirma la disponibilidad; cualquier horario guardado puede estar desactualizado.</p>
            <nav class="reserva-pasos" aria-label="Pasos para reservar">
                <span class="paso-activo" aria-current="step"><b>1</b> Elige tu pase</span>
                <span><b>2</b> Busca un horario</span>
                <span><b>3</b> Escoge tu mesa</span>
            </nav>
            <section class="reserva-configuracion">
                <fieldset class="pases-grid">
                    <legend><span class="reservas-eyebrow">PASO 1</span><strong>¿Cuánto tiempo necesitas?</strong></legend>
                    <label class="pase-card">
                        <span class="pase-duracion"><i class="bi bi-clock" aria-hidden="true"></i> 3 HORAS</span>
                        <strong class="pase-nombre">Coworking medio día</strong>
                        <span class="pase-incluye">3 horas, una bebida de especialidad y repostería.</span>
                        <span class="pase-precio">$220.00 <small>MXN</small></span>
                    </label>
                    <label class="pase-card">
                        <span class="pase-duracion"><i class="bi bi-clock" aria-hidden="true"></i> 6 HORAS</span>
                        <strong class="pase-nombre">Coworking día completo</strong>
                        <span class="pase-incluye">6 horas, refill de café del día, un alimento salado y 10 impresiones.</span>
                        <span class="pase-precio">$405.00 <small>MXN</small></span>
                    </label>
                </fieldset>
                <section class="busqueda-reservas" aria-labelledby="horario-titulo">
                    <div class="horario-intro">
                        <span class="reservas-eyebrow">PASO 2</span>
                        <h2 id="horario-titulo">Encuentra el mejor momento</h2>
                        <p>La fecha y preferencias se pueden guardar en este dispositivo; la disponibilidad no.</p>
                    </div>
                    <section class="offline-module" id="offline-reservas" data-offline-local="true" aria-labelledby="offline-reservas-title">
                        <h2 id="offline-reservas-title">Borrador de reserva sin conexión</h2>
                        <p>Guarda tus preferencias. Este borrador no aparta una mesa ni confirma disponibilidad.</p>
                        <form id="reserva-borrador">
                            <label>Fecha<input name="fecha" type="date" required></label>
                            <label>Hora de inicio<input name="hora" type="time" min="08:00" max="17:00" step="3600" value="09:00" required></label>
                            <label>Pase<select name="paquete"><option value="medio_dia">Coworking medio día (3 horas)</option><option value="dia_completo">Coworking día completo (6 horas)</option></select></label>
                            <label>Accesibilidad<select name="requisito"><option value="">Todas las mesas</option><option value="silla_ruedas">Espacio para silla de ruedas</option><option value="zurdo">Estación para persona zurda</option></select></label>
                            <button type="submit">Guardar borrador en este dispositivo</button>
                            <button type="button" class="secondary" id="borrar-reserva-borrador">Borrar borrador</button>
                        </form>
                        <output id="estado-reserva-borrador" role="status" aria-live="polite"></output>
                        <a id="continuar-reserva-borrador" href="reservas.php" hidden>Revisar disponibilidad al reconectar</a>
                    </section>
                </section>
                <fieldset class="horarios-disponibles" id="horarios-disponibles">
                    <legend>Inicios disponibles</legend>
                    <p class="horarios-ayuda">Los horarios libres se consultan en línea. No hay disponibilidad confirmada en esta copia.</p>
                    <div class="horarios-opciones" aria-hidden="true">
                        <?php foreach (['09:00', '11:00', '13:00', '15:00'] as $horaOffline): ?>
                            <button type="button" class="horario-opcion" disabled>
                                <span><?php echo $horaOffline; ?></span><small>Requiere conexión</small>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            </section>
            <section class="plano-section" id="espacios-disponibles" aria-labelledby="plano-titulo">
                <div class="plano-heading">
                    <div>
                        <span class="reservas-eyebrow">PASO 3 · ESPACIOS</span>
                        <h2 id="plano-titulo">Elige dónde instalarte</h2>
                    </div>
                    <p>Explora la distribución conceptual. La disponibilidad requiere conexión.</p>
                </div>
                <p class="sin-espacios">Conéctate para consultar los espacios y confirmar cuál está libre.</p>
                <div class="plano-cafeteria">
                    <div class="plano-ventanales" aria-hidden="true"><span><i class="bi bi-sun"></i> Ventanales</span></div>
                    <div class="plano-zonas-servicio" aria-hidden="true">
                        <div class="plano-zona zona-preparacion"><i class="bi bi-egg-fried"></i><span>Preparación</span></div>
                        <div class="plano-zona zona-caja"><i class="bi bi-cup-hot"></i><span>Barra · Caja</span></div>
                        <div class="plano-zona zona-sanitario"><i class="bi bi-universal-access"></i><span>Sanitario accesible</span></div>
                    </div>
                    <div class="plano-mesas" aria-label="Plano conceptual; disponibilidad no consultada">
                        <div class="plano-area-label"><span>Salón de coworking</span><span>Conéctate para consultar</span></div>
                        <p class="plano-aclaracion">El estado de cada espacio no se guarda offline.</p>
                    </div>
                    <div class="plano-entrada" aria-hidden="true"><i class="bi bi-door-open"></i> Entrada</div>
                </div>
            </section>
            <p class="reserva-nota"><i class="bi bi-info-circle" aria-hidden="true"></i> El borrador permanece en este dispositivo. No se crea ninguna reserva ni se envía información al servidor sin conexión.</p>
        </main>
        <script src="JS/offline-experience.js" defer></script>
    </body>
    </html>
    <?php
    exit;
}

require_once __DIR__ . '/PHP/seguridad.php';
iniciarSesionSegura();
if (empty($_SESSION['ID_Usuario']) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    header('Cache-Control: private, no-cache, must-revalidate');
    if (empty($_GET)) {
        header('X-PWA-Cacheable: public');
    }
} else {
    header('Cache-Control: no-store');
}
require __DIR__ . '/BD/conexion.php';
require_once __DIR__ . '/PHP/zona_horaria_app.php';
configurarZonaHorariaAplicacion($connection);

$paquetes = [
    'medio_dia' => ['nombre' => 'Coworking medio día', 'horas' => 3, 'precio' => 220.00,
        'incluye' => '3 horas, una bebida de especialidad y repostería.'],
    'dia_completo' => ['nombre' => 'Coworking día completo', 'horas' => 6, 'precio' => 405.00,
        'incluye' => '6 horas, refill de café del día, un alimento salado y 10 impresiones.'],
];
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
        require_once __DIR__ . '/PHP/notificaciones_push.php';
        $connection->commit();
        $enTransaccion = false;
        unset($_SESSION['reserva_pago_demo']);
        $_SESSION['reserva_demo_confirmada'] = true;
        try {
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
$horariosDisponibles = [];
$mensajeDisponibilidad = '';
$fechaConsultaObj = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaConsulta);
$horaConsultaObj = DateTimeImmutable::createFromFormat('!H:i', $horaConsulta);
if ($fechaConsultaObj && $fechaConsultaObj->format('Y-m-d') === $fechaConsulta &&
    $horaConsultaObj && $horaConsultaObj->format('H:i') === $horaConsulta) {
    try {
        $horariosSql = [];
        for ($horaInicio = 8; $horaInicio <= 20 - $paquetes[$paqueteConsulta]['horas']; $horaInicio++) {
            $horaOpcion = sprintf('%02d:00', $horaInicio);
            try {
                list($inicioOpcion, $finOpcion) = intervaloReserva($fechaConsulta, $horaOpcion, $paqueteConsulta, $paquetes);
                $inicioOpcionSql = $inicioOpcion->format('Y-m-d H:i:s');
                $finOpcionSql = $finOpcion->format('Y-m-d H:i:s');
                $horariosSql[] = "SELECT '{$horaOpcion}' AS Hora, '{$inicioOpcionSql}' AS Inicio, '{$finOpcionSql}' AS Fin";
            } catch (InvalidArgumentException $e) {
                continue;
            }
        }
        if ($horariosSql) {
            $horariosQuery = $connection->prepare(
                "SELECT horario.Hora,
                    (SELECT COUNT(*) FROM espacios_reserva e
                    WHERE e.Activo=1
                        AND (? <> 'silla_ruedas' OR e.Silla_Ruedas=1)
                        AND (? <> 'zurdo' OR e.Zurdo=1)
                        AND NOT EXISTS (
                            SELECT 1 FROM reservas_espacios r
                            WHERE r.ID_Espacio=e.ID_Espacio AND r.Estado <> 'cancelada'
                                AND r.Inicio < horario.Fin AND r.Fin > horario.Inicio
                        )) AS Disponibles
                FROM (" . implode(' UNION ALL ', $horariosSql) . ") horario
                ORDER BY horario.Hora"
            );
            $horariosQuery->bind_param('ss', $requisitoConsulta, $requisitoConsulta);
            $horariosQuery->execute();
            $horariosDisponibles = $horariosQuery->get_result()->fetch_all(MYSQLI_ASSOC);
        }

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
$busquedaRealizada = (isset($_GET['fecha'], $_GET['hora'], $_GET['paquete']) || empty($_GET))
    && (bool) $disponibilidad && !$checkoutDemo;
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
    <link rel="stylesheet" href="CSS/reservas.css?v=14">
</head>
<body class="reservas-page" data-offline-view="true">
    <?php include 'includes/navbar.php'; ?>
    <main class="reservas-main">
        <section class="offline-module" id="offline-reservas" data-offline-local="true" hidden aria-labelledby="offline-reservas-title">
            <h2 id="offline-reservas-title">Borrador de reserva sin conexión</h2>
            <p>Guarda tus preferencias en este dispositivo. La disponibilidad se comprobará al reconectar; este borrador no aparta una mesa.</p>
            <form id="reserva-borrador">
                <label>Fecha<input name="fecha" type="date" required></label>
                <label>Hora de inicio<input name="hora" type="time" min="08:00" max="17:00" step="3600" value="09:00" required></label>
                <label>Pase<select name="paquete"><option value="medio_dia">Coworking medio día (3 horas)</option><option value="dia_completo">Coworking día completo (6 horas)</option></select></label>
                <label>Accesibilidad<select name="requisito"><option value="">Todas las mesas</option><option value="silla_ruedas">Espacio para silla de ruedas</option><option value="zurdo">Estación para persona zurda</option></select></label>
                <button type="submit">Guardar borrador en este dispositivo</button>
                <button type="button" class="secondary" id="borrar-reserva-borrador">Borrar borrador</button>
            </form>
            <output id="estado-reserva-borrador" role="status" aria-live="polite"></output>
            <a id="continuar-reserva-borrador" href="reservas.php" hidden>Revisar disponibilidad al reconectar</a>
        </section>
        <header class="reservas-heading">
            <div class="reservas-intro">
                <span class="reservas-eyebrow">COWORKING EN CAFÉLIZ</span>
                <h1>Un buen café.<br>Un espacio para tus ideas.</h1>
                <p>Elige cuánto tiempo necesitas, consulta qué mesas están libres y prepara tu próxima sesión de estudio o trabajo.</p>
                <div class="reservas-beneficios" aria-label="Detalles del espacio">
                    <span><i class="bi bi-clock" aria-hidden="true"></i> Pases de 3 o 6 horas</span>
                    <span><i class="bi bi-calendar-check" aria-hidden="true"></i> Disponibilidad por horario</span>
                </div>
            </div>
            <figure class="reservas-hero-photo">
                <img src="IMG/f5.webp" alt="Bebida de Caféliz servida en una taza">
                <figcaption>
                    <span><i class="bi bi-cup-hot" aria-hidden="true"></i> Un espacio para tus ideas</span>
                    <strong>Café y algo rico para acompañar tu sesión.</strong>
                </figcaption>
            </figure>
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
        <nav class="reserva-pasos" aria-label="Pasos para reservar">
            <span class="<?php echo $busquedaRealizada ? 'paso-completo' : 'paso-activo'; ?>" <?php echo !$busquedaRealizada ? 'aria-current="step"' : ''; ?>><b><?php echo $busquedaRealizada ? '<i class="bi bi-check-lg" aria-hidden="true"></i>' : '1'; ?></b> Elige tu pase</span>
            <span class="<?php echo $busquedaRealizada ? 'paso-completo' : ''; ?>"><b><?php echo $busquedaRealizada ? '<i class="bi bi-check-lg" aria-hidden="true"></i>' : '2'; ?></b> Busca un horario</span>
            <span class="<?php echo $busquedaRealizada ? 'paso-activo' : ''; ?>" <?php echo $busquedaRealizada ? 'aria-current="step"' : ''; ?>><b>3</b> Escoge tu mesa</span>
        </nav>

        <form class="reserva-configuracion" id="formulario-disponibilidad" method="get" action="reservas.php"
            data-results-date="<?php echo htmlspecialchars($fechaConsulta, ENT_QUOTES, 'UTF-8'); ?>"
            data-results-package="<?php echo htmlspecialchars($paqueteConsulta, ENT_QUOTES, 'UTF-8'); ?>"
            data-results-access="<?php echo htmlspecialchars($requisitoConsulta, ENT_QUOTES, 'UTF-8'); ?>"
            data-results-hour="<?php echo htmlspecialchars($horaConsulta, ENT_QUOTES, 'UTF-8'); ?>">
            <fieldset class="pases-grid">
                <legend><span class="reservas-eyebrow">PASO 1</span><strong>¿Cuánto tiempo necesitas?</strong></legend>
                <?php foreach ($paquetes as $codigo => $pase): ?>
                    <label class="pase-card <?php echo $paqueteConsulta === $codigo ? 'pase-activo' : ''; ?>">
                        <input type="radio" name="paquete" value="<?php echo htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?>"
                            data-hours="<?php echo (int) $pase['horas']; ?>"
                            data-price="<?php echo htmlspecialchars(number_format($pase['precio'], 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo $paqueteConsulta === $codigo ? 'checked' : ''; ?> required>
                        <span class="pase-duracion"><i class="bi bi-clock" aria-hidden="true"></i> <?php echo $pase['horas']; ?> HORAS</span>
                        <span class="pase-seleccion" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                        <strong class="pase-nombre"><?php echo htmlspecialchars($pase['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span class="pase-incluye"><?php echo htmlspecialchars($pase['incluye'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="pase-precio">$<?php echo number_format($pase['precio'], 2); ?> <small>MXN</small></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <section class="busqueda-reservas" aria-labelledby="horario-titulo">
                <div class="horario-intro">
                    <span class="reservas-eyebrow">PASO 2</span>
                    <h2 id="horario-titulo">Encuentra el mejor momento</h2>
                    <p>Elige un inicio en punto. Tu pase debe terminar a más tardar a las 20:00.</p>
                </div>
                <label>Fecha
                    <input type="date" name="fecha" min="<?php echo $fechaMinima; ?>"
                        value="<?php echo htmlspecialchars($fechaConsulta, ENT_QUOTES, 'UTF-8'); ?>" required>
                    <small id="fecha-dia-semana" aria-live="polite"></small>
                </label>
                <label>Hora de inicio
                    <input type="time" name="hora" min="08:00" max="<?php echo sprintf('%02d:00', $horaMaxima); ?>"
                        step="3600" value="<?php echo htmlspecialchars($horaConsulta, ENT_QUOTES, 'UTF-8'); ?>" required>
                    <small id="limite-horario" aria-live="polite">Último inicio para este pase: <?php echo sprintf('%02d:00', $horaMaxima); ?>.</small>
                </label>
                <label class="accesibilidad-label">Necesidades de acceso
                    <select name="requisito">
                        <option value="" <?php echo $requisitoConsulta === '' ? 'selected' : ''; ?>>Ver todas las mesas</option>
                        <option value="silla_ruedas" <?php echo $requisitoConsulta === 'silla_ruedas' ? 'selected' : ''; ?>>Acceso para silla de ruedas</option>
                        <option value="zurdo" <?php echo $requisitoConsulta === 'zurdo' ? 'selected' : ''; ?>>Estación para persona zurda</option>
                    </select>
                </label>
                <button type="submit">Buscar mesas disponibles <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            </section>
            <fieldset class="horarios-disponibles" id="horarios-disponibles"
                data-results-date="<?php echo htmlspecialchars($fechaConsulta, ENT_QUOTES, 'UTF-8'); ?>"
                data-results-package="<?php echo htmlspecialchars($paqueteConsulta, ENT_QUOTES, 'UTF-8'); ?>"
                data-results-access="<?php echo htmlspecialchars($requisitoConsulta, ENT_QUOTES, 'UTF-8'); ?>"
                data-results-hour="<?php echo htmlspecialchars($horaConsulta, ENT_QUOTES, 'UTF-8'); ?>">
                <legend>Inicios disponibles para <?php echo $paquetes[$paqueteConsulta]['horas']; ?> horas</legend>
                <p class="horarios-ayuda" id="horarios-ayuda" aria-live="polite">
                    <?php echo $busquedaRealizada ? 'Selecciona una hora para consultar las mesas libres en ese horario.' : 'Horarios calculados según la disponibilidad actual. Puedes ajustar fecha, pase o acceso y volver a buscar.'; ?>
                </p>
                <?php if ($horariosDisponibles): ?>
                    <div class="horarios-opciones">
                        <?php foreach ($horariosDisponibles as $opcionHorario): ?>
                            <?php $horarioLibre = (int) $opcionHorario['Disponibles'] > 0; ?>
                            <button type="button" class="horario-opcion <?php echo $horaConsulta === $opcionHorario['Hora'] ? 'is-selected' : ''; ?>"
                                data-hora="<?php echo htmlspecialchars($opcionHorario['Hora'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-available="<?php echo $horarioLibre ? 'true' : 'false'; ?>"
                                <?php echo !$horarioLibre ? 'disabled title="No hay espacios libres para este horario."' : ''; ?>
                                aria-pressed="<?php echo $horaConsulta === $opcionHorario['Hora'] ? 'true' : 'false'; ?>">
                                <span><?php echo htmlspecialchars($opcionHorario['Hora'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <small><?php echo $horarioLibre ? ((int) $opcionHorario['Disponibles'] === 1 ? '1 espacio' : (int) $opcionHorario['Disponibles'] . ' espacios') : 'Sin espacios'; ?></small>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="horarios-vacios">No hay horarios futuros disponibles para este día y pase. Prueba otra fecha.</p>
                <?php endif; ?>
                <div class="horarios-leyenda" aria-label="Leyenda de horarios">
                    <span><i class="horario-punto-libre" aria-hidden="true"></i> Con espacios</span>
                    <span><i class="horario-punto-lleno" aria-hidden="true"></i> Sin espacios</span>
                </div>
            </fieldset>
        </form>

        <section class="plano-section" id="espacios-disponibles" aria-labelledby="plano-titulo">
            <div class="plano-heading">
                <div>
                    <span class="reservas-eyebrow">PASO 3 · ESPACIOS</span>
                    <h2 id="plano-titulo">Elige dónde instalarte</h2>
                </div>
                <p>Explora una distribución conceptual de la cafetería. Los accesos y la disponibilidad corresponden a cada espacio.</p>
            </div>
            <p class="espacios-seleccionados">
                <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($fechaConsultaObj && $fechaConsultaObj->format('Y-m-d') === $fechaConsulta ? $fechaConsultaObj->format('d/m/Y') : $fechaConsulta, ENT_QUOTES, 'UTF-8'); ?>
                    · <?php echo htmlspecialchars($horaConsulta, ENT_QUOTES, 'UTF-8'); ?> a <?php echo htmlspecialchars(sprintf('%02d:00', (int) substr($horaConsulta, 0, 2) + $paquetes[$paqueteConsulta]['horas']), ENT_QUOTES, 'UTF-8'); ?>
                    · <?php echo htmlspecialchars($paquetes[$paqueteConsulta]['nombre'], ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="#formulario-disponibilidad">Cambiar búsqueda</a>
            </p>
            <?php if (!$disponibilidad): ?>
                <p class="sin-espacios"><?php echo htmlspecialchars($mensajeDisponibilidad !== '' ? $mensajeDisponibilidad : 'No hay espacios para mostrar con esos datos.', ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else: ?>
                <div class="leyenda-mesas" aria-label="Estados de las mesas">
                    <span><i class="leyenda-disponible" aria-hidden="true"></i> Libre para tu horario</span>
                    <span><i class="leyenda-ocupada" aria-hidden="true"></i> Reservada</span>
                    <span class="leyenda-accesible"><i aria-hidden="true"></i> Acceso para silla de ruedas</span>
                    <?php $espaciosLibres = count(array_filter($disponibilidad, static function ($espacio) { return (int) $espacio['Disponible'] === 1; })); ?>
                    <strong><?php echo $espaciosLibres; ?> de <?php echo count($disponibilidad); ?> disponibles</strong>
                </div>
                <div class="plano-cafeteria">
                    <div class="plano-ventanales" aria-hidden="true">
                        <span><i class="bi bi-sun"></i> Ventanales</span>
                    </div>
                    <div class="plano-zonas-servicio" aria-hidden="true">
                        <div class="plano-zona zona-preparacion"><i class="bi bi-egg-fried"></i><span>Preparación</span></div>
                        <div class="plano-zona zona-caja"><i class="bi bi-cup-hot"></i><span>Barra · Caja</span></div>
                        <div class="plano-zona zona-sanitario"><i class="bi bi-universal-access"></i><span>Sanitario accesible</span></div>
                    </div>
                    <div class="plano-mesas" role="group" aria-label="Plano conceptual; selecciona una mesa verde disponible">
                        <div class="plano-area-label"><span>Salón de coworking</span><span><?php echo count($disponibilidad); ?> mesas</span></div>
                        <?php foreach (array_chunk($disponibilidad, 4) as $indiceFila => $filaEspacios): ?>
                            <div class="plano-fila">
                                <?php foreach ($filaEspacios as $indiceMesaFila => $espacio): ?>
                                    <?php
                                    $espacioAccesible = (int) $espacio['Silla_Ruedas'] === 1;
                                    $indiceMesa = ($indiceFila * 4) + $indiceMesaFila;
                                    $mesaRedonda = in_array($indiceMesa, [2, 5, 8, 10], true);
                                    $nombreEspacioTexto = $espacio['Nombre'];
                                    $nombreEspacio = htmlspecialchars($nombreEspacioTexto, ENT_QUOTES, 'UTF-8');
                                    $detalleEspacio = [];
                                    if ($espacioAccesible) $detalleEspacio[] = 'Acceso para silla de ruedas';
                                    if ((int) $espacio['Zurdo'] === 1) $detalleEspacio[] = 'Estación para persona zurda';
                                    $detalleEspacio[] = $espacio['Conexiones'];
                                    $etiquetaEspacio = htmlspecialchars($nombreEspacioTexto . ' · ' . implode(' · ', $detalleEspacio), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <article class="plano-estacion <?php echo $espacio['Disponible'] ? 'estacion-libre' : 'estacion-reservada'; ?> <?php echo $espacioAccesible ? 'estacion-accesible' : ''; ?> <?php echo $mesaRedonda ? 'mesa-redonda' : ''; ?>">
                                        <span class="plano-silla silla-norte" aria-hidden="true"></span>
                                        <span class="plano-silla silla-sur" aria-hidden="true"></span>
                                        <span class="plano-silla silla-este" aria-hidden="true"></span>
                                        <?php if ($espacioAccesible): ?>
                                            <span class="plano-acceso-silla" aria-label="Acceso para silla de ruedas"><i class="bi bi-universal-access" aria-hidden="true"></i></span>
                                        <?php else: ?>
                                            <span class="plano-silla silla-oeste" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <?php if ($espacio['Disponible'] && !empty($_SESSION['ID_Usuario'])): ?>
                                            <button type="button" class="mesa-plano-control seleccionar-mesa"
                                                data-espacio-id="<?php echo (int) $espacio['ID_Espacio']; ?>"
                                                data-espacio-nombre="<?php echo $nombreEspacio; ?>"
                                                aria-pressed="false" aria-label="Elegir <?php echo $etiquetaEspacio; ?>">
                                                <span class="plano-mesa-nombre"><?php echo $nombreEspacio; ?></span>
                                                <span class="plano-mesa-estado"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>Libre</span>
                                            </button>
                                        <?php elseif ($espacio['Disponible']): ?>
                                            <a class="mesa-plano-control mesa-plano-enlace" href="login.php"
                                                aria-label="Inicia sesión para reservar <?php echo $etiquetaEspacio; ?>">
                                                <span class="plano-mesa-nombre"><?php echo $nombreEspacio; ?></span>
                                                <span class="plano-mesa-estado"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>Libre</span>
                                            </a>
                                        <?php else: ?>
                                            <div class="mesa-plano-control mesa-plano-no-disponible" aria-label="<?php echo $etiquetaEspacio; ?> · Reservada" aria-disabled="true">
                                                <span class="plano-mesa-nombre"><?php echo $nombreEspacio; ?></span>
                                                <span class="plano-mesa-estado"><i class="bi bi-lock-fill" aria-hidden="true"></i>Reservada</span>
                                            </div>
                                        <?php endif; ?>
                                        <span class="plano-estacion-detalles" aria-hidden="true">
                                            <?php if ($espacioAccesible): ?><i class="bi bi-universal-access"></i><?php endif; ?>
                                            <?php if ((int) $espacio['Zurdo'] === 1): ?><i class="bi bi-brightness-high"></i><?php endif; ?>
                                            <i class="bi bi-plug"></i>
                                        </span>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($indiceFila === 1 && count($disponibilidad) > 4): ?>
                                <div class="plano-pasillo" aria-hidden="true"><span><i class="bi bi-arrow-left-right"></i> Pasillo de circulación</span></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="plano-entrada" aria-hidden="true"><i class="bi bi-door-open"></i> Entrada</div>
                </div>
                <p class="plano-aclaracion"><i class="bi bi-info-circle" aria-hidden="true"></i> Plano ilustrativo de una cafetería ficticia; no representa la distribución real de un local. El icono universal identifica las mesas con acceso para silla de ruedas.</p>
            <?php endif; ?>
            <?php if (!empty($_SESSION['ID_Usuario'])): ?>
                <form class="reserva-resumen" id="resumen-reserva" method="post" action="reservas.php"
                    data-price="<?php echo htmlspecialchars(number_format($paquetes[$paqueteConsulta]['precio'], 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"
                    data-package="<?php echo htmlspecialchars($paquetes[$paqueteConsulta]['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-start="<?php echo htmlspecialchars($horaConsulta, ENT_QUOTES, 'UTF-8'); ?>"
                    data-hours="<?php echo (int) $paquetes[$paqueteConsulta]['horas']; ?>">
                    <div class="resumen-titulo">
                        <span class="reservas-eyebrow">TU RESERVA</span>
                        <strong id="resumen-mesa">Selecciona una mesa para continuar</strong>
                    </div>
                    <div class="resumen-detalle">
                        <span><small>Pase</small><strong id="resumen-paquete"><?php echo htmlspecialchars($paquetes[$paqueteConsulta]['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
                        <span><small>Fecha y hora</small><strong id="resumen-fecha"><?php echo htmlspecialchars($fechaConsulta, ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($horaConsulta, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                        <span><small>Total</small><strong id="resumen-precio">$<?php echo number_format($paquetes[$paqueteConsulta]['precio'], 2); ?> MXN</strong></span>
                    </div>
                    <input type="hidden" name="accion" value="preparar_pago_demo">
                    <input type="hidden" name="espacio" id="resumen-espacio" value="">
                    <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($fechaConsulta, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="hora" value="<?php echo htmlspecialchars($horaConsulta, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="paquete" value="<?php echo htmlspecialchars($paqueteConsulta, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="requisito" value="<?php echo htmlspecialchars($requisitoConsulta, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" id="continuar-reserva" disabled>Continuar al pago simulado <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                </form>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        <p class="reserva-nota"><i class="bi bi-info-circle" aria-hidden="true"></i> El pago es una simulación académica sin cobro real. Tu solicitud quedará pendiente de confirmación; esta reserva no genera credenciales Wi-Fi.</p>
    </main>
    <?php include 'includes/footer.html'; ?>
    <script src="JS/reservas.js?v=4" defer></script>
    <script src="JS/offline-experience.js" defer></script>
</body>
</html>
