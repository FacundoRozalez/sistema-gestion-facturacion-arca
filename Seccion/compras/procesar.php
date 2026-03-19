<?php
require __DIR__ . '/../../Panel/includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ========================
    // VALIDACIÓN PROVEEDOR
    // ========================
    if (!empty($_POST['id_proveedor_autocomplete'])) {
        $id_proveedor = $_POST['id_proveedor_autocomplete'];
    } elseif (!empty($_POST['id_proveedor'])) {
        $id_proveedor = $_POST['id_proveedor'];
    } elseif (!empty($_POST['nuevo_nombre'])) {
        $stmt = $conn->prepare("SELECT id_proveedor FROM Proveedor WHERE cuit = ?");
        $stmt->bind_param("s", $_POST['nuevo_cuit']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id_proveedor);
            $stmt->fetch();
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO Proveedor (razon_social, cuit, telefono, email, direccion, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt_insert->bind_param(
                "sssss",
                $_POST['nuevo_nombre'],
                $_POST['nuevo_cuit'],
                $_POST['nuevo_telefono'],
                $_POST['nuevo_email'],
                $_POST['nuevo_direccion']
            );
            $stmt_insert->execute();
            $id_proveedor = $conn->insert_id;
            $stmt_insert->close();
        }
        $stmt->close();
    } else {
        die("Debe seleccionar o ingresar un proveedor válido.");
    }

    // ========================
    // DATOS COMPRA
    // ========================
    $fecha = $_POST['fecha_compra'] ?? date('Y-m-d');
    $numero_factura = $_POST['numero_factura'] ?? null;
    $observaciones = $_POST['observaciones'] ?? null;
    $id_usuario = 1; // reemplazar con $_SESSION['id_usuario']

    // ========================
    // VALIDAR PRODUCTOS
    // ========================
    if (empty($_POST['productos'])) {
        echo "<script>alert('Debe agregar al menos un producto.'); history.back();</script>";
        exit;
    }

    // ========================
    // INSERTAR COMPRA
    // ========================
    $total = 0;
    $stmt = $conn->prepare("INSERT INTO Compra (fecha, id_proveedor, id_usuario, total, numero_factura, observaciones) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siidss", $fecha, $id_proveedor, $id_usuario, $total, $numero_factura, $observaciones);
    $stmt->execute();
    $id_compra = $conn->insert_id;
    $stmt->close();

    // ========================
    // INSERTAR DETALLES CON LOTE
    // ========================
    $stmt = $conn->prepare("INSERT INTO Detalle_Compra (id_compra, id_producto, cantidad, precio_unitario, lote) VALUES (?, ?, ?, ?, ?)");
    $upd = $conn->prepare("UPDATE Producto SET stock = stock + ? WHERE id_producto = ?");

    for ($i = 0; $i < count($_POST['productos']); $i++) {
        if ($_POST['productos'][$i] == '') continue;

        $id_producto = $_POST['productos'][$i];
        $cantidad = $_POST['cantidades'][$i];
        $precio_unitario = $_POST['precios'][$i];
        $lote = $_POST['lotes'][$i] ?? '';

        $stmt->bind_param("iiids", $id_compra, $id_producto, $cantidad, $precio_unitario, $lote);
        $stmt->execute();

        $upd->bind_param("ii", $cantidad, $id_producto);
        $upd->execute();

        $total += $cantidad * $precio_unitario;
    }

    $stmt->close();
    $upd->close();

    // ========================
    // ACTUALIZAR TOTAL
    // ========================
    $stmt = $conn->prepare("UPDATE Compra SET total = ? WHERE id_compra = ?");
    $stmt->bind_param("di", $total, $id_compra);
    $stmt->execute();
    $stmt->close();

    header("Location: /CristianFerreteria/index.php?seccion=compras&success=1");
    exit;
}
?>
