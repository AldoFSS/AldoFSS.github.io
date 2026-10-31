<?php
require_once __DIR__ . '/seguridad.php';
iniciarSesionSegura();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respuestaVerde($estado, array $respuesta)
{
    http_response_code($estado);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && ($_GET['accion'] ?? '') === 'comunidad') {
    try {
        require __DIR__ . '/../BD/conexion.php';
        $resultado = $connection->query(
            'SELECT COUNT(*) AS vasos, COALESCE(SUM(CO2_g), 0) AS co2,
                COALESCE(SUM(Residuos_g), 0) AS residuos, COALESCE(SUM(Agua_ml), 0) AS agua
            FROM eco_eventos'
        )->fetch_assoc();
        $connection->close();
        header('X-PWA-Cache-View: 1');
        echo json_encode([
            'success' => true,
            'vasos' => (int) $resultado['vasos'],
            'co2' => (int) $resultado['co2'],
            'residuos' => (int) $resultado['residuos'],
            'agua' => (int) $resultado['agua']
        ], JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        error_log((string) $e);
        respuestaVerde(500, ['success' => false, 'message' => 'No se pudo consultar el marcador comunitario.']);
    }
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && ($_GET['accion'] ?? '') === 'personal') {
    if (empty($_SESSION['ID_Usuario'])) {
        respuestaVerde(401, ['success' => false, 'message' => 'Inicia sesión para consultar tu billetera verde.']);
    }
    try {
        require __DIR__ . '/../BD/conexion.php';
        $usuario = (int) $_SESSION['ID_Usuario'];
        $q = $connection->prepare(
            'SELECT COUNT(*) AS vasos, COALESCE(SUM(CO2_g), 0) AS co2,
                COALESCE(SUM(Residuos_g), 0) AS residuos, COALESCE(SUM(Agua_ml), 0) AS agua
            FROM eco_eventos WHERE ID_Usuario=?'
        );
        $q->bind_param('i', $usuario);
        $q->execute();
        $impacto = $q->get_result()->fetch_assoc();
        $q = $connection->prepare('SELECT Codigo, Creado FROM eco_cupones WHERE ID_Usuario=? AND Canjeado IS NULL ORDER BY Creado');
        $q->bind_param('i', $usuario);
        $q->execute();
        $cupones = [];
        $resultado = $q->get_result();
        while ($cupon = $resultado->fetch_assoc()) {
            $cupones[] = ['codigo' => 'CAFELIZ-CUPON:' . $cupon['Codigo'], 'creado' => $cupon['Creado']];
        }
        $connection->close();
        $vasos = (int) $impacto['vasos'];
        header('X-PWA-Cache-View: 1');
        echo json_encode([
            'success' => true,
            'vasos' => $vasos,
            'co2' => (int) $impacto['co2'],
            'residuos' => (int) $impacto['residuos'],
            'agua' => (int) $impacto['agua'],
            'puntos' => $vasos * 10,
            'insignias' => intdiv($vasos, 8),
            'progreso' => $vasos % 8,
            'cupones' => $cupones
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        error_log((string) $e);
        respuestaVerde(500, ['success' => false, 'message' => 'No se pudo consultar tu billetera verde.']);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit;
}
exigirPostLocal();

$connection = null;
$transaccion = false;
try {
    if (empty($_SESSION['ID_Usuario'])) {
        respuestaVerde(401, ['success' => false, 'message' => 'Inicia sesión para participar en la huella verde.']);
    }
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
        throw new InvalidArgumentException('Se requiere una solicitud JSON.');
    }
    $raw = file_get_contents('php://input', false, null, 0, 2049);
    if (strlen($raw) > 2048) throw new InvalidArgumentException('Solicitud demasiado grande.');
    $data = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
    if (!is_array($data)) throw new InvalidArgumentException('El cuerpo JSON debe ser un objeto.');
    $accion = $data['accion'] ?? '';
    require __DIR__ . '/../BD/conexion.php';

    if ($accion === 'emitir_qr') {
        $ahora = time();
        $solicitudes = $_SESSION['eco_qr_emitidos'] ?? [];
        if (!is_array($solicitudes)) $solicitudes = [];
        $solicitudes = array_values(array_filter($solicitudes, function ($marca) use ($ahora) {
            return is_int($marca) && $marca > $ahora - 600;
        }));
        if (count($solicitudes) >= 20) {
            respuestaVerde(429, ['success' => false, 'message' => 'Espera un momento antes de generar otro código.']);
        }
        $solicitudes[] = $ahora;
        $_SESSION['eco_qr_emitidos'] = $solicitudes;

        $usuario = (int) $_SESSION['ID_Usuario'];
        $connection->begin_transaction();
        $transaccion = true;
        $invalidar = $connection->prepare('UPDATE eco_qr_tokens SET Usado=CURRENT_TIMESTAMP WHERE ID_Usuario=? AND Usado IS NULL');
        $invalidar->bind_param('i', $usuario);
        $invalidar->execute();
        $connection->query('DELETE FROM eco_qr_tokens WHERE Expira < CURRENT_TIMESTAMP - INTERVAL 1 DAY OR (Usado IS NOT NULL AND Usado < CURRENT_TIMESTAMP - INTERVAL 1 DAY)');
        $codigo = bin2hex(random_bytes(24));
        $huella = hash('sha256', $codigo);
        $expira = date('Y-m-d H:i:s', $ahora + 300);
        $insertar = $connection->prepare('INSERT INTO eco_qr_tokens (ID_Usuario, Huella, Expira) VALUES (?, ?, ?)');
        $insertar->bind_param('iss', $usuario, $huella, $expira);
        $insertar->execute();
        $connection->commit();
        $transaccion = false;
        respuestaVerde(200, [
            'success' => true,
            'qr' => 'CAFELIZ-VERDE:' . strtoupper($codigo),
            'expira' => $expira,
            'vigenciaSegundos' => 300
        ]);
    }

    if ($accion === 'registrar_termo') {
        exigirPersonalCafeteria();
        $qr = $data['qr'] ?? '';
        if (!is_string($qr) || !preg_match('/^CAFELIZ-VERDE:([A-Fa-f0-9]{48})$/D', $qr, $partes)) {
            throw new InvalidArgumentException('El QR de fidelidad no es válido.');
        }
        $huella = hash('sha256', strtolower($partes[1]));
        $connection->begin_transaction();
        $transaccion = true;
        $q = $connection->prepare(
            'SELECT ID_QR, ID_Usuario FROM eco_qr_tokens
            WHERE Huella=? AND Expira > CURRENT_TIMESTAMP AND Usado IS NULL FOR UPDATE'
        );
        $q->bind_param('s', $huella);
        $q->execute();
        $token = $q->get_result()->fetch_assoc();
        if (!$token) {
            throw new DomainException('El QR expiró o ya fue utilizado. Solicita un código nuevo al cliente.');
        }
        $usuario = (int) $token['ID_Usuario'];
        $bloquearUsuario = $connection->prepare('SELECT id FROM usuarios WHERE id=? FOR UPDATE');
        $bloquearUsuario->bind_param('i', $usuario);
        $bloquearUsuario->execute();
        if (!$bloquearUsuario->get_result()->num_rows) {
            throw new DomainException('No se encontró la cuenta asociada al QR.');
        }
        $usar = $connection->prepare('UPDATE eco_qr_tokens SET Usado=CURRENT_TIMESTAMP WHERE ID_QR=? AND Usado IS NULL');
        $idQr = (int) $token['ID_QR'];
        $usar->bind_param('i', $idQr);
        $usar->execute();
        if ($usar->affected_rows !== 1) {
            throw new DomainException('El QR ya fue registrado.');
        }
        $evento = $connection->prepare('INSERT INTO eco_eventos (ID_Usuario, Huella_QR) VALUES (?, ?)');
        $evento->bind_param('is', $usuario, $huella);
        $evento->execute();

        $totalQuery = $connection->prepare('SELECT COUNT(*) AS total FROM eco_eventos WHERE ID_Usuario=?');
        $totalQuery->bind_param('i', $usuario);
        $totalQuery->execute();
        $total = (int) $totalQuery->get_result()->fetch_assoc()['total'];
        $cuponCreado = null;
        if ($total % 8 === 0) {
            $cupon = strtoupper(bin2hex(random_bytes(16)));
            $crearCupon = $connection->prepare('INSERT INTO eco_cupones (ID_Usuario, Codigo) VALUES (?, ?)');
            $crearCupon->bind_param('is', $usuario, $cupon);
            $crearCupon->execute();
            $cuponCreado = $cupon;
        }
        $connection->commit();
        $transaccion = false;
        if ($cuponCreado !== null) {
            require_once __DIR__ . '/notificaciones_push.php';
            enviarPushAUsuario($connection, $usuario, [
                'title' => '¡Recompensa verde disponible!',
                'body' => 'Acumulaste ocho vasos reutilizables. Revisa tu cupón de bebida gratis.',
                'url' => './verde.php'
            ]);
        }
        respuestaVerde(200, [
            'success' => true,
            'vasos' => $total,
            'puntos' => $total * 10,
            'insignias' => $total % 8,
            'cupon' => $cuponCreado
        ]);
    }

    if ($accion === 'canjear_cupon') {
        exigirPersonalCafeteria();
        $codigo = $data['codigo'] ?? '';
        if (!is_string($codigo) || !preg_match('/^CAFELIZ-CUPON:([A-Fa-f0-9]{32})$/D', $codigo, $partes)) {
            throw new InvalidArgumentException('El QR del cupón no es válido.');
        }
        $codigoCupon = strtoupper($partes[1]);
        $connection->begin_transaction();
        $transaccion = true;
        $q = $connection->prepare('SELECT ID_Cupon, Canjeado FROM eco_cupones WHERE Codigo=? FOR UPDATE');
        $q->bind_param('s', $codigoCupon);
        $q->execute();
        $cupon = $q->get_result()->fetch_assoc();
        if (!$cupon) throw new DomainException('Cupón no reconocido.');
        if ($cupon['Canjeado'] !== null) throw new DomainException('Este cupón ya fue canjeado.');
        $idCupon = (int) $cupon['ID_Cupon'];
        $q = $connection->prepare('UPDATE eco_cupones SET Canjeado=CURRENT_TIMESTAMP WHERE ID_Cupon=? AND Canjeado IS NULL');
        $q->bind_param('i', $idCupon);
        $q->execute();
        if ($q->affected_rows !== 1) throw new DomainException('Este cupón ya fue canjeado.');
        $connection->commit();
        $transaccion = false;
        respuestaVerde(200, ['success' => true, 'message' => 'Cupón validado. Entrega una bebida gratis.']);
    }

    throw new InvalidArgumentException('Acción no válida.');
} catch (InvalidArgumentException $e) {
    if ($transaccion && $connection instanceof mysqli) $connection->rollback();
    respuestaVerde(422, ['success' => false, 'message' => $e->getMessage()]);
} catch (DomainException $e) {
    if ($transaccion && $connection instanceof mysqli) $connection->rollback();
    respuestaVerde(409, ['success' => false, 'message' => $e->getMessage()]);
} catch (JsonException $e) {
    if ($transaccion && $connection instanceof mysqli) $connection->rollback();
    respuestaVerde(422, ['success' => false, 'message' => 'No se pudo leer el código enviado.']);
} catch (Throwable $e) {
    if ($transaccion && $connection instanceof mysqli) $connection->rollback();
    error_log((string) $e);
    respuestaVerde(500, ['success' => false, 'message' => 'No se pudo registrar la acción ecológica. Inténtalo de nuevo.']);
}
