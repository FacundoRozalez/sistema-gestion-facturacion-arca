<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../../Panel/includes/conexion.php';



$result = $conn->query("SELECT * FROM Medio_Pago ORDER BY nombre");
$medios_pago = [];
while ($row = $result->fetch_assoc()) {
    $medios_pago[] = $row;
}
?>

<script>
const mediosPagoData = <?= json_encode($medios_pago, JSON_UNESCAPED_UNICODE) ?>;
</script>
