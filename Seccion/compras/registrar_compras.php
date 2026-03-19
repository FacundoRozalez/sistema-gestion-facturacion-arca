<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../../Panel/includes/conexion.php';

// Manejo de POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/procesar.php';
    exit;
}

// Obtener datos
$proveedores = $conn->query("SELECT * FROM Proveedor ORDER BY razon_social")->fetch_all(MYSQLI_ASSOC);
$productos = $conn->query("SELECT * FROM Producto ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$compras = $conn->query("
    SELECT c.*, p.razon_social AS proveedor, u.nombre AS usuario_nombre
    FROM Compra c
    LEFT JOIN Proveedor p ON c.id_proveedor = p.id_proveedor
    LEFT JOIN Usuario u ON c.id_usuario = u.id_usuario
    ORDER BY c.fecha DESC
")->fetch_all(MYSQLI_ASSOC);

// Obtener detalles de todas las compras
$detalles = $conn->query("
    SELECT dc.*, p.nombre AS producto_nombre 
    FROM Detalle_Compra dc
    JOIN Producto p ON dc.id_producto = p.id_producto
")->fetch_all(MYSQLI_ASSOC);

// Agrupar detalles por compra
$detallesPorCompra = [];
foreach ($detalles as $det) {
    $detallesPorCompra[$det['id_compra']][] = $det;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Compra</title>
    <link rel="stylesheet" href="/CristianFerreteria/Seccion/compras/assets/css/estilos.css">
</head>
<body>
<h2>Registrar Compra</h2>

<form method="post" id="formCompra">
    <label>Fecha de compra:
        <input type="date" name="fecha_compra" value="<?= date('Y-m-d') ?>" required>
    </label>
    <br><br>
    <label>N° Factura/Remito:
        <input type="text" name="numero_factura" placeholder="Opcional">
    </label>
    <br><br>

    <!-- Módulo Proveedor -->
    <?php include __DIR__ . '/includes/proveedor.php'; ?>

    <!-- Módulo Producto -->
    <?php include __DIR__ . '/includes/producto.php'; ?>
<br><br>
    <label>Observaciones:
        <br><textarea name="observaciones" rows="3" placeholder="Opcional"></textarea>
    </label>
    <br><br>

    <button type="submit">Registrar Compra</button>
    <button type="reset">Limpiar Formulario</button>
</form>

<div id="totalCompra" style="font-weight:bold; margin-top:10px;">Total: $0.00</div>
<hr>

<!-- Módulo Listado -->
<?php include __DIR__ . '/includes/listado.php'; ?>

<!-- Variables JS globales -->
<script>
    window.productos = <?= json_encode($productos) ?>;
    window.compras = <?= json_encode($compras) ?>;
    window.detallesCompras = <?= json_encode($detallesPorCompra) ?>;
</script>



<!-- JS -->
<script src="/CristianFerreteria/Seccion/compras/assets/js/proveedor.js"></script>
<script src="/CristianFerreteria/Seccion/compras/assets/js/producto.js"></script>
<script src="/CristianFerreteria/Seccion/compras/assets/js/total.js"></script>
<script src="/CristianFerreteria/Seccion/compras/assets/js/listado.js"></script>
<script src="/CristianFerreteria/Seccion/compras/assets/js/compras.js"></script>

<!-- VALIDACIÓN DE FORMULARIO -->
<script>
document.getElementById('formCompra').addEventListener('submit', function(e) {
    e.preventDefault(); // Evita envío automático

    // --- Validar proveedor ---
    let modoProveedor = document.querySelector('input[name="modo_proveedor"]:checked').value;
    let idProveedor = document.querySelector('select[name="id_proveedor"]').value;
    let nuevoNombre = document.querySelector('input[name="nuevo_nombre"]').value;
    let idProveedorAutocomplete = document.querySelector('#id_proveedor_autocomplete').value;

    if (modoProveedor === 'existente' && idProveedor === '') {
        alert('Debe seleccionar un proveedor existente o crear uno nuevo.');
        return;
    }
    if (modoProveedor === 'nuevo' && nuevoNombre.trim() === '' && idProveedorAutocomplete === '') {
        alert('Debe completar el nombre del nuevo proveedor o usar un CUIT ya registrado.');
        return;
    }

    // --- Validar productos ---
    let productosInputs = document.querySelectorAll('input[name="productos[]"]');
    let cantidadesInputs = document.querySelectorAll('input[name="cantidades[]"]');
    let hayProducto = false;

    for (let i = 0; i < productosInputs.length; i++) {
        if (productosInputs[i].value.trim() !== '' && cantidadesInputs[i].value > 0) {
            hayProducto = true;
            break;
        }
    }

    if (!hayProducto) {
        alert('Debe agregar al menos un producto con cantidad.');
        return;
    }

    // Todo correcto, enviar formulario
    this.submit();
});
</script>

</body>
</html>
