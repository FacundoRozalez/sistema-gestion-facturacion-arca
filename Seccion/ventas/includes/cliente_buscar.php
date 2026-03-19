<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require '../../../Panel/includes/conexion.php';
header('Content-Type: application/json');

$buscar = $_GET['buscar'] ?? '';
$buscar = $conn->real_escape_string($buscar);

if(strlen($buscar) < 2){
    echo json_encode([]);
    exit;
}

$sql = "SELECT id_cliente, nombre, apellido, dni_cuit, telefono, email, direccion 
        FROM Cliente 
        WHERE nombre LIKE '%$buscar%' 
           OR apellido LIKE '%$buscar%' 
           OR dni_cuit LIKE '%$buscar%' 
           OR telefono LIKE '%$buscar%' 
           OR email LIKE '%$buscar%' 
        ORDER BY nombre LIMIT 20";

$result = $conn->query($sql);
$clientes = [];
while($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}

echo json_encode($clientes);
