<?php
// Conexión simple a la base de datos
require '../../../Panel/includes/conexion.php';

// Respuesta en formato JSON
header('Content-Type: application/json');

// Obtener el término de búsqueda
$buscar = $_GET['buscar'] ?? '';

// Consulta SQL básica
$sql = "SELECT id_producto, nombre, precio_compra 
        FROM Producto 
        WHERE nombre LIKE '%$buscar%' 
        LIMIT 10";

$resultado = $conn->query($sql);
$productos = $resultado->fetch_all(MYSQLI_ASSOC);

// Devolver resultados
echo json_encode($productos);
?>