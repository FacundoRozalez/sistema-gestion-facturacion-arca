<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../Panel/includes/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

// Recibimos los datos del formulario
$modo_cliente = $_POST['modo_cliente'] ?? '';
$id_cliente = $_POST['id_cliente'] ?? null;

// Productos: JSON desde registrar_venta.php
$productosJson = $_POST['productos'] ?? '[]';
$productosFrontend = json_decode($productosJson, true);

// Medios de pago (opcional)
$mediosPagoJson = $_POST['medios_pago_json'] ?? '[]';
$medios_pago = json_decode($mediosPagoJson, true);

// Tipo de venta y comprobante
$tipo_venta = $_POST['tipo_venta_hidden'] ?? 'AFIP';
$tipo_comprobante = intval($_POST['tipo_comprobante'] ?? 0);

try {
    $conn->begin_transaction();

    // 1) Guardar cliente nuevo
    if ($modo_cliente === 'nuevo') {
        $clienteData = json_decode($_POST['cliente_json'] ?? '{}', true);

        if (empty($clienteData['nombre']) || empty($clienteData['apellido'])) {
            throw new Exception("Debe completar nombre y apellido del cliente nuevo");
        }

        $stmt = $conn->prepare("INSERT INTO Cliente (nombre, apellido, dni_cuit, telefono, email, direccion, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param(
            'ssssss',
            $clienteData['nombre'],
            $clienteData['apellido'],
            $clienteData['dni'] ?? null,
            $clienteData['telefono'] ?? null,
            $clienteData['email'] ?? null,
            $clienteData['direccion'] ?? null
        );
        if (!$stmt->execute()) throw new Exception("Error al guardar cliente: " . $stmt->error);
        $id_cliente = $stmt->insert_id;
        $stmt->close();
    } elseif ($modo_cliente === 'existente') {
        if (empty($id_cliente)) throw new Exception("Debe seleccionar un cliente existente");
        $id_cliente = intval($id_cliente);
    } else {
        throw new Exception("Modo cliente inválido");
    }

    // 2) Calcular total venta
    $total_venta = 0;
    $productosParaGuardar = [];
    foreach ($productosFrontend as $p) {
        $id_producto = intval($p['IdProducto'] ?? 0);
        $cantidad = floatval($p['Cantidad'] ?? 1);
        $precio = floatval($p['PrecioFinal'] ?? 0);

        if ($id_producto <= 0 || $cantidad <= 0 || $precio <= 0) {
            throw new Exception("Producto inválido: " . json_encode($p));
        }

        $total_venta += $precio * $cantidad;

        $productosParaGuardar[] = [
            'id_producto' => $id_producto,
            'cantidad' => $cantidad,
            'precio' => $precio
        ];
    }

    if (empty($productosParaGuardar)) throw new Exception("Debe agregar al menos un producto");

    // 3) Insertar venta con tipo de venta y comprobante
    $stmt = $conn->prepare("INSERT INTO Venta (fecha, id_cliente, id_usuario, total, tipo_venta, tipo_comprobante) VALUES (NOW(), ?, ?, ?, ?, ?)");
    $stmt->bind_param('iidsi', $id_cliente, $id_usuario, $total_venta, $tipo_venta, $tipo_comprobante);
    if (!$stmt->execute()) throw new Exception("Error al guardar venta: " . $stmt->error);
    $id_venta = $stmt->insert_id;
    $stmt->close();

    // 4) Insertar detalle venta y actualizar stock
    $stmt_detalle = $conn->prepare("INSERT INTO Detalle_Venta (id_venta, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    $stmt_stock = $conn->prepare("UPDATE Producto SET stock = stock - ? WHERE id_producto = ? AND stock >= ?");
    foreach ($productosParaGuardar as $p) {
        $stmt_detalle->bind_param('iiid', $id_venta, $p['id_producto'], $p['cantidad'], $p['precio']);
        if (!$stmt_detalle->execute()) throw new Exception("Error detalle venta: " . $stmt_detalle->error);

        $stmt_stock->bind_param('iii', $p['cantidad'], $p['id_producto'], $p['cantidad']);
        if (!$stmt_stock->execute()) throw new Exception("Error actualizar stock o stock insuficiente para producto ID {$p['id_producto']}");
        if ($stmt_stock->affected_rows === 0) throw new Exception("Stock insuficiente para producto ID {$p['id_producto']}");
    }
    $stmt_detalle->close();
    $stmt_stock->close();

    // 5) Insertar pagos
    $stmt_pago = $conn->prepare("INSERT INTO Pago_Venta (id_venta, id_medio_pago, monto, referencia, fecha_pago) VALUES (?, ?, ?, ?, NOW())");
    $total_pagado = 0;
    foreach ($medios_pago as $mp) {
        $id_medio_pago = intval($mp['id'] ?? 0);
        $monto = floatval($mp['monto'] ?? 0);
        $referencia = $mp['referencia'] ?? '';
        if ($monto <= 0 || $id_medio_pago <= 0) continue;

        $stmt_pago->bind_param('iids', $id_venta, $id_medio_pago, $monto, $referencia);
        if (!$stmt_pago->execute()) throw new Exception("Error en pago: " . $stmt_pago->error);
        $total_pagado += $monto;
    }
    $stmt_pago->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'id_venta' => $id_venta,
        'total_venta' => number_format($total_venta, 2, '.', ''),
        'total_pagado' => number_format($total_pagado, 2, '.', ''),
        'tipo_venta' => $tipo_venta,
        'tipo_comprobante' => $tipo_comprobante
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
