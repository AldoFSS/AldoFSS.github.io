<?php
require_once __DIR__ . '/seguridad.php';
iniciarSesionSegura();
exigirPostLocal();
exigirAdministrador();
require __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/PronosticoInventario.php';

function numeroPronosticoFormulario(string $clave, string $patron, float $minimo, float $maximo): string
{
    $valor = $_POST[$clave] ?? null;
    if (!is_string($valor) || !preg_match($patron, $valor) ||
        (float) $valor < $minimo || (float) $valor > $maximo) {
        throw new InvalidArgumentException('Revisa el valor del campo ' . $clave . '.');
    }
    return $valor;
}

function coordenadaPronosticoFormulario(string $clave, float $minimo, float $maximo): string
{
    return numeroPronosticoFormulario($clave, '/^-?\d{1,3}(?:\.\d{1,6})?$/D', $minimo, $maximo);
}

function fechaHoraEventoFormulario(string $clave, DateTimeZone $zonaHoraria): DateTimeImmutable
{
    $valor = $_POST[$clave] ?? null;
    if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $valor)) {
        throw new InvalidArgumentException('Indica una fecha y hora válidas para el evento.');
    }
    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $valor, $zonaHoraria);
    $errores = DateTimeImmutable::getLastErrors();
    if (!$fecha || ($errores && ($errores['warning_count'] || $errores['error_count'])) ||
        $fecha->format('Y-m-d\TH:i') !== $valor) {
        throw new InvalidArgumentException('La fecha y hora del evento no son válidas.');
    }
    return $fecha;
}

$enTransaccion = false;
$notificarInventarioBajo = false;
try {
    $accion = $_POST['accion'] ?? '';
    $connection->begin_transaction();
    $enTransaccion = true;

    if ($accion === 'crear_insumo') {
        $nombre = textoFormulario('nombre', 100);
        $unidad = textoFormulario('unidad', 20);
        if (!preg_match('/^[\p{L}\p{N} ._-]+$/u', $unidad)) {
            throw new InvalidArgumentException('La unidad solo puede contener letras y espacios.');
        }
        $stock = $_POST['stock'] ?? '';
        $minimo = $_POST['minimo'] ?? '';
        if (!is_string($stock) || !preg_match('/^\d{1,9}(?:\.\d{1,3})?$/D', $stock) ||
            !is_string($minimo) || !preg_match('/^\d{1,9}(?:\.\d{1,3})?$/D', $minimo)) {
            throw new InvalidArgumentException('El stock y el mínimo deben ser cantidades positivas con hasta tres decimales.');
        }
        $caducidad = $_POST['caducidad'] ?? '';
        $apertura = $_POST['apertura'] ?? '';
        foreach (['caducidad' => $caducidad, 'apertura' => $apertura] as $campo => $fecha) {
            if ($fecha !== '' && (!is_string($fecha) ||
                !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $fecha) ||
                DateTimeImmutable::createFromFormat('!Y-m-d', $fecha)->format('Y-m-d') !== $fecha)) {
                throw new InvalidArgumentException('La fecha de ' . $campo . ' no es válida.');
            }
        }
        $caducidadDb = $caducidad === '' ? null : $caducidad;
        $aperturaDb = $apertura === '' ? null : $apertura;
        $q = $connection->prepare(
            'INSERT INTO inventario_insumos (Nombre, Unidad, Stock, Stock_Minimo, Caducidad, Fecha_Apertura)
            VALUES (?, ?, ?, ?, ?, ?)'
        );
        $q->bind_param('ssssss', $nombre, $unidad, $stock, $minimo, $caducidadDb, $aperturaDb);
        $q->execute();
        $idInsumo = $connection->insert_id;
        $tipo = 'inicial';
        $cero = '0.000';
        $nota = 'Registro inicial de inventario';
        $movimiento = $connection->prepare(
            'INSERT INTO movimientos_inventario (ID_Insumo, Tipo, Cantidad, Stock_Anterior, Stock_Nuevo, Nota)
            VALUES (?, ?, ?, ?, ?, ?)'
        );
        $movimiento->bind_param('isssss', $idInsumo, $tipo, $stock, $cero, $stock, $nota);
        $movimiento->execute();
    } elseif ($accion === 'movimiento') {
        $idInsumo = enteroPositivo($_POST['id_insumo'] ?? null);
        $tipo = $_POST['tipo'] ?? '';
        if (!in_array($tipo, ['entrada', 'merma'], true)) {
            throw new InvalidArgumentException('Tipo de movimiento no válido.');
        }
        $cantidad = $_POST['cantidad'] ?? '';
        if (!is_string($cantidad) || !preg_match('/^\d{1,9}(?:\.\d{1,3})?$/D', $cantidad) ||
            (float) $cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero y tener hasta tres decimales.');
        }
        $nota = $_POST['nota'] ?? '';
        if (!is_string($nota) || mb_strlen(trim($nota)) > 255) {
            throw new InvalidArgumentException('La nota no puede superar 255 caracteres.');
        }
        $q = $connection->prepare('SELECT Stock, Stock_Minimo FROM inventario_insumos WHERE ID_Insumo=? FOR UPDATE');
        $q->bind_param('i', $idInsumo);
        $q->execute();
        $fila = $q->get_result()->fetch_assoc();
        if (!$fila) throw new InvalidArgumentException('El insumo seleccionado ya no existe.');
        $antesMills = (int) round((float) $fila['Stock'] * 1000);
        $minimoMills = (int) round((float) $fila['Stock_Minimo'] * 1000);
        $cantidadMills = (int) round((float) $cantidad * 1000);
        $nuevoMills = $tipo === 'entrada' ? $antesMills + $cantidadMills : $antesMills - $cantidadMills;
        $notificarInventarioBajo = $tipo === 'merma' &&
            $antesMills > $minimoMills && $nuevoMills <= $minimoMills;
        $stockNuevo = number_format($nuevoMills / 1000, 3, '.', '');
        $stockAnterior = number_format($antesMills / 1000, 3, '.', '');
        $actualizar = $connection->prepare('UPDATE inventario_insumos SET Stock=? WHERE ID_Insumo=?');
        $actualizar->bind_param('si', $stockNuevo, $idInsumo);
        $actualizar->execute();
        $cantidadRegistro = number_format($cantidadMills / 1000, 3, '.', '');
        $movimiento = $connection->prepare(
            'INSERT INTO movimientos_inventario (ID_Insumo, Tipo, Cantidad, Stock_Anterior, Stock_Nuevo, Nota)
            VALUES (?, ?, ?, ?, ?, ?)'
        );
        $nota = trim($nota);
        $movimiento->bind_param('isssss', $idInsumo, $tipo, $cantidadRegistro, $stockAnterior, $stockNuevo, $nota);
        $movimiento->execute();
    } elseif ($accion === 'receta') {
        $productoId = enteroPositivo($_POST['id_producto'] ?? null);
        $idInsumo = enteroPositivo($_POST['id_insumo'] ?? null);
        $cantidad = $_POST['cantidad'] ?? '';
        if (!is_string($cantidad) || !preg_match('/^\d{1,9}(?:\.\d{1,3})?$/D', $cantidad)) {
            throw new InvalidArgumentException('La cantidad por producto debe ser mayor que cero o cero para quitar la receta.');
        }
        $producto = $connection->prepare('SELECT id FROM menuproductos WHERE id=?');
        $producto->bind_param('i', $productoId);
        $producto->execute();
        if (!$producto->get_result()->num_rows) throw new InvalidArgumentException('El producto seleccionado no existe.');
        $insumo = $connection->prepare('SELECT ID_Insumo FROM inventario_insumos WHERE ID_Insumo=?');
        $insumo->bind_param('i', $idInsumo);
        $insumo->execute();
        if (!$insumo->get_result()->num_rows) throw new InvalidArgumentException('El insumo seleccionado no existe.');
        if ((float) $cantidad === 0.0) {
            $q = $connection->prepare('DELETE FROM recetas_insumos WHERE ID_Producto=? AND ID_Insumo=?');
            $q->bind_param('ii', $productoId, $idInsumo);
        } else {
            $q = $connection->prepare(
                'INSERT INTO recetas_insumos (ID_Producto, ID_Insumo, Cantidad) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE Cantidad=VALUES(Cantidad)'
            );
            $q->bind_param('iis', $productoId, $idInsumo, $cantidad);
        }
        $q->execute();
    } elseif ($accion === 'configurar_pronostico') {
        $latitud = coordenadaPronosticoFormulario('latitud', -90, 90);
        $longitud = coordenadaPronosticoFormulario('longitud', -180, 180);
        $umbralFria = numeroPronosticoFormulario('umbral_fria', '/^-?\d{1,2}(?:\.\d{1,2})?$/D', -30, 60);
        $ajusteFria = numeroPronosticoFormulario('ajuste_fria', '/^\d{1,3}(?:\.\d{1,2})?$/D', 0, 100);
        $umbralCaliente = numeroPronosticoFormulario('umbral_caliente', '/^-?\d{1,2}(?:\.\d{1,2})?$/D', -30, 60);
        $ajusteCaliente = numeroPronosticoFormulario('ajuste_caliente', '/^\d{1,3}(?:\.\d{1,2})?$/D', 0, 100);
        $q = $connection->prepare(
            'UPDATE inventario_pronostico_config SET Latitud=?, Longitud=?,
                Umbral_Bebida_Fria=?, Ajuste_Bebida_Fria=?,
                Umbral_Bebida_Caliente=?, Ajuste_Bebida_Caliente=?
            WHERE ID_Config=1'
        );
        $q->bind_param('dddddd', $latitud, $longitud, $umbralFria, $ajusteFria, $umbralCaliente, $ajusteCaliente);
        $q->execute();
        $connection->query('DELETE FROM inventario_pronostico_clima WHERE ID_Config=1');
    } elseif ($accion === 'sensibilidad_clima') {
        $productoId = enteroPositivo($_POST['id_producto'] ?? null);
        $sensibilidad = $_POST['sensibilidad'] ?? '';
        if (!is_string($sensibilidad) ||
            !in_array($sensibilidad, ['neutra', 'bebida_fria', 'bebida_caliente'], true)) {
            throw new InvalidArgumentException('La sensibilidad climática seleccionada no es válida.');
        }
        $q = $connection->prepare(
            'INSERT INTO inventario_sensibilidad_clima (ID_Producto, Sensibilidad)
            VALUES (?, ?) ON DUPLICATE KEY UPDATE Sensibilidad=VALUES(Sensibilidad)'
        );
        $q->bind_param('is', $productoId, $sensibilidad);
        $q->execute();
    } elseif ($accion === 'crear_evento_local') {
        $nombreEvento = textoFormulario('nombre_evento', 120);
        $descripcionEvento = $_POST['descripcion_evento'] ?? '';
        if (!is_string($descripcionEvento) || mb_strlen(trim($descripcionEvento)) > 255) {
            throw new InvalidArgumentException('La descripción del evento no puede superar 255 caracteres.');
        }
        $zonaHorariaEvento = new DateTimeZone('America/Mexico_City');
        $inicioEvento = fechaHoraEventoFormulario('inicio_evento', $zonaHorariaEvento);
        $finEvento = fechaHoraEventoFormulario('fin_evento', $zonaHorariaEvento);
        if ($finEvento <= new DateTimeImmutable('now', $zonaHorariaEvento) ||
            $finEvento <= $inicioEvento) {
            throw new InvalidArgumentException('El fin del evento debe ser posterior al inicio y no haber pasado.');
        }
        $latitudEvento = coordenadaPronosticoFormulario('latitud_evento', -90, 90);
        $longitudEvento = coordenadaPronosticoFormulario('longitud_evento', -180, 180);
        $impactoEvento = numeroPronosticoFormulario('impacto_evento', '/^\d{1,3}(?:\.\d{1,2})?$/D', 0, 200);
        $inicioDb = $inicioEvento->format('Y-m-d H:i:s');
        $finDb = $finEvento->format('Y-m-d H:i:s');
        $descripcionEvento = trim($descripcionEvento);
        $q = $connection->prepare(
            'INSERT INTO inventario_eventos_locales
                (Nombre, Descripcion, Inicio, Fin, Latitud, Longitud, Impacto_Porcentaje)
            VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $q->bind_param(
            'sssssss',
            $nombreEvento,
            $descripcionEvento,
            $inicioDb,
            $finDb,
            $latitudEvento,
            $longitudEvento,
            $impactoEvento
        );
        $q->execute();
    } elseif ($accion === 'alternar_evento_local') {
        $idEvento = enteroPositivo($_POST['id_evento'] ?? null);
        $q = $connection->prepare(
            'UPDATE inventario_eventos_locales SET Activo=IF(Activo=1, 0, 1) WHERE ID_Evento=?'
        );
        $q->bind_param('i', $idEvento);
        $q->execute();
        if ($q->affected_rows !== 1) {
            throw new InvalidArgumentException('El evento seleccionado no existe o no cambió de estado.');
        }
    } else {
        throw new InvalidArgumentException('Acción no válida.');
    }

    if ($notificarInventarioBajo) {
        require_once __DIR__ . '/notificaciones_push.php';
        $notificacionInventario = [
            'title' => 'Revisar nivel de inventario',
            'body' => 'Un insumo alcanzó su nivel mínimo. Abre el panel para revisarlo.',
            'url' => './listInventario.php'
        ];
    }
    $connection->commit();
    $enTransaccion = false;
    if ($notificarInventarioBajo) {
        enviarPushAAdministradores($connection, $notificacionInventario);
    }
    header('Location: ../listInventario.php?resultado=ok', true, 303);
    exit;
} catch (Throwable $e) {
    if ($enTransaccion) $connection->rollback();
    error_log((string) $e);
    http_response_code($e instanceof InvalidArgumentException ? 422 : 409);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e instanceof InvalidArgumentException ? $e->getMessage()
        : 'No se pudo actualizar el inventario. Verifica que el insumo no esté duplicado y vuelve a intentar.';
}
