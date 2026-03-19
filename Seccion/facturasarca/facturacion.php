<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../../Panel/includes/conexion.php';

// Guardar o actualizar comprobante
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_comprobante'] ?? null;
    $id_venta = $_POST['id_venta'];
    $tipo_comprobante = $_POST['tipo_comprobante'];
    $punto_venta = $_POST['punto_venta'];
    $numero_comprobante = $_POST['numero_comprobante'];
    $fecha_emision = $_POST['fecha_emision'];
    $cae = $_POST['cae'];
    $fecha_vencimiento_cae = $_POST['fecha_vencimiento_cae'];
    $estado = $_POST['estado'];
    $monto_total = $_POST['monto_total'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE Comprobante SET id_venta=?, tipo_comprobante=?, punto_venta=?, numero_comprobante=?, fecha_emision=?, cae=?, fecha_vencimiento_cae=?, estado=?, monto_total=? WHERE id_comprobante=?");
        $stmt->bind_param("isssissssdi", $id_venta, $tipo_comprobante, $punto_venta, $numero_comprobante, $fecha_emision, $cae, $fecha_vencimiento_cae, $estado, $monto_total, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO Comprobante (id_venta, tipo_comprobante, punto_venta, numero_comprobante, fecha_emision, cae, fecha_vencimiento_cae, estado, monto_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssisssd", $id_venta, $tipo_comprobante, $punto_venta, $numero_comprobante, $fecha_emision, $cae, $fecha_vencimiento_cae, $estado, $monto_total);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: comprobantes.php");
    exit;
}

// Eliminar comprobante
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM Comprobante WHERE id_comprobante = ?");
    $stmt->bind_param("i", $id_del);
    $stmt->execute();
    $stmt->close();

    header("Location: comprobantes.php");
    exit;
}

// Obtener comprobante para editar
$editar = null;
if (isset($_GET['edit'])) {
    $id_edit = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM Comprobante WHERE id_comprobante = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $res = $stmt->get_result();
    $editar = $res->fetch_assoc();
    $stmt->close();
}

// Listar comprobantes con info de venta
$result = $conn->query("SELECT c.*, v.fecha AS fecha_venta FROM Comprobante c LEFT JOIN Venta v ON c.id_venta = v.id_venta ORDER BY c.fecha_emision DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Comprobantes (Facturación ARCA)</title>
</head>
<body>

<h2><?php echo $editar ? "Editar Comprobante" : "Nuevo Comprobante"; ?></h2>
<form method="POST" action="comprobantes.php">
    <input type="hidden" name="id_comprobante" value="<?php echo $editar['id_comprobante'] ?? ''; ?>">

    <label>Venta:</label><br>
    <select name="id_venta" required>
        <option value="">Seleccione</option>
        <?php
        $ventas = $conn->query("SELECT id_venta, fecha FROM Venta ORDER BY fecha DESC");
        while ($venta = $ventas->fetch_assoc()):
            $selected = (isset($editar['id_venta']) && $editar['id_venta'] == $venta['id_venta']) ? 'selected' : '';
        ?>
            <option value="<?php echo $venta['id_venta']; ?>" <?php echo $selected; ?>>
                <?php echo "ID: ".$venta['id_venta']." - Fecha: ".$venta['fecha']; ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Tipo Comprobante:</label><br>
    <select name="tipo_comprobante" required>
        <?php
        $tipos = ['Factura A', 'Factura B', 'Ticket', 'Nota de Crédito'];
        foreach ($tipos as $tipo) {
            $selected = (isset($editar['tipo_comprobante']) && $editar['tipo_comprobante'] == $tipo) ? 'selected' : '';
            echo "<option value=\"$tipo\" $selected>$tipo</option>";
        }
        ?>
    </select><br><br>

    <label>Punto de Venta:</label><br>
    <input type="text" name="punto_venta" required value="<?php echo htmlspecialchars($editar['punto_venta'] ?? ''); ?>"><br><br>

    <label>Número Comprobante:</label><br>
    <input type="text" name="numero_comprobante" required value="<?php echo htmlspecialchars($editar['numero_comprobante'] ?? ''); ?>"><br><br>

    <label>Fecha Emisión:</label><br>
    <input type="date" name="fecha_emision" required value="<?php echo $editar['fecha_emision'] ?? date('Y-m-d'); ?>"><br><br>

    <label>CAE:</label><br>
    <input type="text" name="cae" required value="<?php echo htmlspecialchars($editar['cae'] ?? ''); ?>"><br><br>

    <label>Fecha Vencimiento CAE:</label><br>
    <input type="date" name="fecha_vencimiento_cae" required value="<?php echo $editar['fecha_vencimiento_cae'] ?? date('Y-m-d'); ?>"><br><br>

    <label>Estado:</label><br>
    <select name="estado" required>
        <?php
        $estados = ['Autorizado', 'Rechazado', 'Pendiente'];
        foreach ($estados as $estado) {
            $selected = (isset($editar['estado']) && $editar['estado'] == $estado) ? 'selected' : '';
            echo "<option value=\"$estado\" $selected>$estado</option>";
        }
        ?>
    </select><br><br>

    <label>Monto Total:</label><br>
    <input type="number" step="0.01" name="monto_total" required value="<?php echo $editar['monto_total'] ?? '0.00'; ?>"><br><br>

    <button type="submit">Guardar</button>
</form>

<hr>

<h2>Lista de Comprobantes</h2>
<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Venta (ID - Fecha)</th>
            <th>Tipo</th>
            <th>Punto de Venta</th>
            <th>Número</th>
            <th>Fecha Emisión</th>
            <th>CAE</th>
            <th>Vencimiento CAE</th>
            <th>Estado</th>
            <th>Monto Total</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id_comprobante']; ?></td>
            <td><?php echo "ID: ".$row['id_venta']." - ".$row['fecha_venta']; ?></td>
            <td><?php echo $row['tipo_comprobante']; ?></td>
            <td><?php echo htmlspecialchars($row['punto_venta']); ?></td>
            <td><?php echo htmlspecialchars($row['numero_comprobante']); ?></td>
            <td><?php echo $row['fecha_emision']; ?></td>
            <td><?php echo htmlspecialchars($row['cae']); ?></td>
            <td><?php echo $row['fecha_vencimiento_cae']; ?></td>
            <td><?php echo $row['estado']; ?></td>
            <td><?php echo number_format($row['monto_total'], 2, ',', '.'); ?></td>
            <td>
                <a href="comprobantes.php?edit=<?php echo $row['id_comprobante']; ?>">Editar</a> |
                <a href="comprobantes.php?delete=<?php echo $row['id_comprobante']; ?>" onclick="return confirm('¿Eliminar este comprobante?');">Eliminar</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>

