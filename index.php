<?php
// ------------------ INCLUDES ------------------
include "Panel/includes/header.php";
include "Panel/includes/sidebar.php";

// ------------------ SECCIÓN ACTIVA ------------------
$seccion = $_GET['seccion'] ?? 'dashboard';

// Lista blanca de secciones permitidas
$secciones_permitidas = [
    'dashboard' => 'Seccion/dashboard/dashboard.php',
    'ventas' => 'Seccion/ventas/registrar_venta.php',
    'clientes' => 'Seccion/clientes/clientes.php',
    'productos' => 'Seccion/productos/productos.php',
    'compras' => 'Seccion/compras/registrar_compras.php',
    'proveedores' => 'Seccion/proveedores/proveedores.php',
    'usuarios' => 'Seccion/usuarios/usuarios.php',
    'mediospago' => 'Seccion/mediospago/mediospago.php',
    'caja' => 'Seccion/caja/caja.php',
    'facturacion' => 'Seccion/facturasarca/facturacion.php',
    'inventario' => 'Seccion/inventario/inventario.php',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel de Control</title>
<style>
/* ----------------- RESET BÁSICO ----------------- */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}

/* ----------------- LAYOUT PRINCIPAL ----------------- */
body {
    display: flex;
    min-height: 100vh;
    background-color: #f4f6f8;
    color: #333;
}

/* ----------------- SIDEBAR FIJA ----------------- */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 200px;
    height: 100vh;
    background: #2c3e50;
    color: white;
    padding: 20px 0;
    overflow-y: auto;
    z-index: 1000;
}

.sidebar ul {
    list-style: none;
    padding: 0;
}

.sidebar ul li a {
    color: white;
    text-decoration: none;
    display: block;
    padding: 10px 20px;
    transition: background-color 0.3s;
}

.sidebar ul li a:hover,
.sidebar ul li a.active {
    background-color: #34495e;
}

/* ----------------- CONTENIDO PRINCIPAL ----------------- */
main {
    margin-left: 200px;   /* espacio para la sidebar */
    padding: 20px;
    flex-grow: 1;
    background: #ecf0f1;
    min-height: 100vh;
    overflow-y: auto;
}

/* ----------------- BOTONES ----------------- */
button {
    background-color: #2a7ae2;
    color: #fff;
    border: none;
    padding: 6px 12px;
    margin: 5px 0;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

button:hover {
    background-color: #1f5bbf;
}

/* ----------------- TABLAS ----------------- */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #f0f0f0;
}
</style>
</head>
<body>

<!-- ----------------- SIDEBAR ----------------- -->
<?php include "Panel/includes/sidebar.php"; ?>

<!-- ----------------- CONTENIDO DINÁMICO ----------------- -->
<main>
<?php
if(array_key_exists($seccion, $secciones_permitidas)){
    include $secciones_permitidas[$seccion];
} else {
    echo "<h2>Sección no encontrada</h2>";
}
?>
</main>

<?php include "Panel/includes/footer.php"; ?>
</body>
</html>
