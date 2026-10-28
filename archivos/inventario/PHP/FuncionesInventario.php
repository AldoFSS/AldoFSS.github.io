<?php
require_once __DIR__ . '/seguridad.php';
iniciarSesionSegura();
exigirPostLocal();
exigirAdministrador();
require __DIR__ . '/../BD/conexion.php';

$enTransaccion = false;
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
        $q = $connection->prepare('SELECT Stock FROM inventario_insumos WHERE ID_Insumo=? FOR UPDATE');
        $q->bind_param('i', $idInsumo);
        $q->execute();
        $fila = $q->get_result()->fetch_assoc();
        if (!$fila) throw new InvalidArgumentException('El insumo seleccionado ya no existe.');
        $antesMills = (int) round((float) $fila['Stock'] * 1000);
        $cantidadMills = (int) round((float) $cantidad * 1000);
        $nuevoMills = $tipo === 'entrada' ? $antesMills + $cantidadMills : $antesMills - $cantidadMills;
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
    } else {
        throw new InvalidArgumentException('Acción no válida.');
    }

    $connection->commit();
    $enTransaccion = false;
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
