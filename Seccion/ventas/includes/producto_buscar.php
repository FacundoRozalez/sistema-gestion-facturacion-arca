<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require '../../../Panel/includes/conexion.php';
header('Content-Type: application/json');

// Recibir parámetros
$buscar = $_GET['buscar'] ?? '';
$buscar = $conn->real_escape_string($buscar);
$agregar = isset($_GET['agregar']) ? true : false;

// Si el término es muy corto, devolver array vacío
if(strlen($buscar) < 2){
    echo json_encode([]);
    exit;
}

// Consulta SQL: selecciona precio_compra solo si vamos a agregar
$sql = "SELECT id_producto, codigo_barras, nombre, descripcion, marca, stock, imagen";
if($agregar){
    $sql .= ", precio_compra";
}
$sql .= " FROM Producto 
         WHERE REPLACE(LOWER(nombre), ' ', '') = REPLACE(LOWER('$buscar'), ' ', '')
            OR nombre LIKE '%$buscar%' 
            OR codigo_barras LIKE '%$buscar%' 
            OR descripcion LIKE '%$buscar%'
            OR marca LIKE '%$buscar%' 
            OR u_medida LIKE '%$buscar%' 
            OR id_categoria LIKE '%$buscar%'";

$result = $conn->query($sql);

// Crear array de productos
$productos = [];
while($row = $result->fetch_assoc()) {
    $prod = [
        'id_producto' => $row['id_producto'],
        'codigo_barras' => $row['codigo_barras'],
        'nombre' => $row['nombre'],
        'descripcion' => $row['descripcion'],
        'marca' => $row['marca'],
        'stock' => (int)$row['stock'],
        'imagen' => $row['imagen'] ?? ''
    ];

    // Incluir precio_compra solo si se va a agregar
    if($agregar){
        $prod['precio_compra'] = (float)$row['precio_compra'];
    }

    $productos[] = $prod;
}

// Devolver JSON
echo json_encode($productos);
