<?php
require __DIR__ . '/../../Panel/includes/conexion.php';

// Recibir JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id_compra'])) {
    echo json_encode(['success' => false, 'message' => 'ID de compra no proporcionado']);
    exit;
}

$id_compra = (int)$data['id_compra'];

// 1️⃣ Obtener los productos de la compra para restar stock
$result = $conn->query("SELECT id_producto, cantidad FROM Detalle_Compra WHERE id_compra = $id_compra");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $id_producto = (int)$row['id_producto'];
        $cantidad = (float)$row['cantidad'];

        // Restar stock
        $conn->query("UPDATE Producto SET stock = stock - $cantidad WHERE id_producto = $id_producto");
    }
}

// 2️⃣ Eliminar detalles de la compra
$conn->query("DELETE FROM Detalle_Compra WHERE id_compra = $id_compra");

// 3️⃣ Eliminar la compra
if ($conn->query("DELETE FROM Compra WHERE id_compra = $id_compra")) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>
