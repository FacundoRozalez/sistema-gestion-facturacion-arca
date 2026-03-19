<?php
// Conexión a la base de datos MySQL usando mysqli
$servername = "localhost";
$username = "root";
$password = "";
$database = "materiales_l_y_m";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
