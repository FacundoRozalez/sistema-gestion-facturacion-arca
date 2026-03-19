<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Conexión
require __DIR__ . '/../../Panel/includes/conexion.php';

if (!$conn) {
    die("<p style='color:red'>Error: No se pudo conectar a la base de datos.</p>");
}

// Valores por defecto
$totalProductos = 0;
$stockBajo = 0;
$ventasHoy = 0;
$clientesActivos = 0;
$productosBajo = [];
$ultimasVentas = [];
$ventasMensuales = [];

// Consultas solo si hay conexión
$totalProductos = $conn->query("SELECT COUNT(*) AS total FROM Producto")->fetch_assoc()['total'] ?? 0;
$stockBajo = $conn->query("SELECT COUNT(*) AS bajo FROM Producto WHERE stock <= stock_minimo")->fetch_assoc()['bajo'] ?? 0;
$ventasHoy = $conn->query("SELECT IFNULL(SUM(total),0) AS ventas FROM Venta WHERE DATE(fecha) = CURDATE()")->fetch_assoc()['ventas'] ?? 0;
$clientesActivos = $conn->query("SELECT COUNT(DISTINCT id_cliente) AS clientes FROM Venta WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['clientes'] ?? 0;

$productosBajo = $conn->query("SELECT nombre, stock, stock_minimo FROM Producto WHERE stock <= stock_minimo");
$ultimasVentas = $conn->query("SELECT V.fecha, C.nombre, C.apellido, V.total 
                               FROM Venta V
                               JOIN Cliente C ON V.id_cliente = C.id_cliente
                               ORDER BY V.fecha DESC
                               LIMIT 10");

$result = $conn->query("SELECT DATE_FORMAT(fecha,'%Y-%m') AS mes, SUM(total) AS total 
                        FROM Venta GROUP BY mes ORDER BY mes ASC LIMIT 12");
while($row = $result->fetch_assoc()) {
    $row['total'] = (float)($row['total'] ?? 0); // <-- asegurar que sea número
    $ventasMensuales[] = $row;
}
?>

<main class="dashboard">
    <!-- Header con logos -->
    <div class="dashboard-header">
        <img src="/CristianFerreteria/Panel/image_logo/logo21.png" alt="Logo Secundario" class="logo-header">
    </div>

    <!-- Tarjetas resumen -->
    <div class="cards">
        <div class="card">
            <h3>Total Productos</h3>
            <p><?= $totalProductos ?></p>
        </div>
        <div class="card">
            <h3>Stock Bajo</h3>
            <p><?= $stockBajo ?></p>
        </div>
        <div class="card">
            <h3>Ventas Hoy</h3>
            <p>$<?= number_format($ventasHoy,2) ?></p>
        </div>
        <div class="card">
            <h3>Clientes Activos</h3>
            <p><?= $clientesActivos ?></p>
        </div>
    </div>

    <!-- Gráfico ventas mensuales -->
    <h3>Ventas Mensuales</h3>
    <canvas id="ventasMensuales" width="800" height="300" data-ventas='<?= json_encode($ventasMensuales) ?>'></canvas>

    <!-- Productos con stock bajo -->
    <h3>Productos con Stock Bajo</h3>
    <table>
        <tr><th>Producto</th><th>Stock</th><th>Stock Mínimo</th></tr>
        <?php while($p = $productosBajo->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($p['nombre']) ?></td>
            <td><?= $p['stock'] ?></td>
            <td><?= $p['stock_minimo'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- Últimas ventas -->
    <h3>Últimas Ventas</h3>
    <table>
        <tr><th>Fecha</th><th>Cliente</th><th>Total</th></tr>
        <?php while($v = $ultimasVentas->fetch_assoc()): ?>
        <tr>
            <td><?= $v['fecha'] ?></td>
            <td><?= htmlspecialchars($v['nombre'].' '.$v['apellido']) ?></td>
            <td>$<?= number_format($v['total'],2) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</main>

<script src="/CristianFerreteria/Seccion/dashboard/dashboard.js"></script>
<link rel="stylesheet" href="/CristianFerreteria/Seccion/dashboard/dashboard.css">
