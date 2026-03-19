<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../../Panel/includes/conexion.php';

header('Content-Type: application/json');

// Obtener datos limpios
$nombre = trim($_POST['nombre'] ?? '');
$precio = floatval($_POST['precio'] ?? 0);
$cantidad = intval($_POST['cantidad'] ?? 1);

// Validaciones
if (empty($nombre) || $precio <= 0 || $cantidad < 1) {
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // 1. Insertar producto
    $stmt = $conn->prepare("INSERT INTO Producto (nombre, precio_venta, stock) VALUES (?, ?, ?)");
    $stmt->bind_param("sdi", $nombre, $precio, $cantidad);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar producto: " . $stmt->error);
    }
    
    $id_producto = $stmt->insert_id;
    $stmt->close();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'id_producto' => $id_producto,
        'nombre' => $nombre,
        'precio_venta' => $precio,
        'stock' => $cantidad
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}