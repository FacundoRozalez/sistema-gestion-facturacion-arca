<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../../Panel/includes/conexion.php';

// Guardar o actualizar movimiento de inventario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_movimiento'] ?? null;
    $fecha = $_POST['fecha'];
    $tipo = $_POST['tipo'];
    $id_producto = $_POST['id_producto'];
    $cantidad = $_POST['cantidad'];
    $motivo = $_POST['motivo'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE Inventario_Movimientos SET fecha=?, tipo=?, id_producto=?, cantidad=?, motivo=? WHERE id_movimiento=?");
        $stmt->bind_param("sssisi", $fecha, $tipo, $id_producto, $cantidad, $motivo, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO Inventario_Movimientos (fecha, tipo, id_producto, cantidad, motivo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisi", $fecha, $tipo, $id_producto, $cantidad, $motivo);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: inventario_movimientos.php");
    exit;
}

// Eliminar movimiento
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM Inventario_Movimientos WHERE id_movimiento = ?");
    $stmt->bind_param("i", $id_del);
    $stmt->execute();
    $stmt->close();

    header("Location: inventario_movimientos.php");
    exit;
}

// Obtener movimiento para editar
$editar = null;
if (isset($_GET['edit'])) {
    $id_edit = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM Inventario_Movimientos WHERE id_movimiento = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $res = $stmt->get_result();
    $editar = $res->fetch_assoc();
    $stmt->close();
}

// Listar movimientos
$result = $conn->query("SELECT m.*, p.nombre AS producto_nombre FROM Inventario_Movimientos m LEFT JOIN Producto p ON m.id_producto = p.id_producto ORDER BY m.fecha DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Movimientos de Inventario</title>
</head>
<body>

<h2><?php echo $editar ? "Editar Movimiento" : "Nuevo Movimiento"; ?></h2>
<form method="POST" action="inventario_movimientos.php">
    <input type="hidden" name="id_movimiento" value="<?php echo $editar['id_movimiento'] ?? ''; ?>">

    <label>Fecha:</label><br>
    <input type="date" name="fecha" required value="<?php echo $editar['fecha'] ?? date('Y-m-d'); ?>"><br><br>

    <label>Tipo:</label><br>
    <select name="tipo" required>
        <?php
        $tipos = ['Entrada', 'Salida', 'Ajuste'];
        foreach ($tipos as $tipo_option) {
            $selected = (isset($editar['tipo']) && $editar['tipo'] == $tipo_option) ? 'selected' : '';
            echo "<option value=\"$tipo_option\" $selected>$tipo_option</option>";
        }
        ?>
    </select><br><br>

    <label>Producto:</label><br>
    <select name="id_producto" required>
        <option value="">Seleccione</option>
        <?php
        $productos = $conn->query("SELECT id_producto, nombre FROM Producto ORDER BY nombre");
        while ($prod = $productos->fetch_assoc()):
            $selected = (isset($editar['id_producto']) && $editar['id_producto'] == $prod['id_producto']) ? 'selected' : '';
        ?>
            <option value="<?php echo $prod['id_producto']; ?>" <?php echo $selected; ?>>
                <?php echo htmlspecialchars($prod['nombre']); ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Cantidad:</label><br>
    <input type="number" step="1" name="cantidad" required value="<?php echo $editar['cantidad'] ?? '1'; ?>"><br><br>

    <label>Motivo:</label><br>
    <textarea name="motivo" required><?php echo htmlspecialchars($editar['motivo'] ?? ''); ?></textarea><br><br>

    <button type="submit">Guardar</button>
</form>

<hr>

<h2>Lista de Movimientos de Inventario</h2>
<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Motivo</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id_movimiento']; ?></td>
            <td><?php echo $row['fecha']; ?></td>
            <td><?php echo $row['tipo']; ?></td>
            <td><?php echo htmlspecialchars($row['producto_nombre']); ?></td>
            <td><?php echo $row['cantidad']; ?></td>
            <td><?php echo htmlspecialchars($row['motivo']); ?></td>
            <td>
                <a href="inventario_movimientos.php?edit=<?php echo $row['id_movimiento']; ?>">Editar</a> |
                <a href="inventario_movimientos.php?delete=<?php echo $row['id_movimiento']; ?>" onclick="return confirm('¿Eliminar este movimiento?');">Eliminar</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>

