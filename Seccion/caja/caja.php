<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../../Panel/includes/conexion.php';

// ------------------ GUARDAR O ACTUALIZAR ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_caja'] ?? null;
    $fecha_apertura = $_POST['fecha_apertura'];
    $fecha_cierre = $_POST['fecha_cierre'] ?: null;
    $saldo_inicial = $_POST['saldo_inicial'];
    $ingresos = $_POST['ingresos'];
    $egresos = $_POST['egresos'];
    $saldo_final = $_POST['saldo_final'];
    $id_usuario = $_POST['id_usuario'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE Caja_Diaria SET fecha_apertura=?, fecha_cierre=?, saldo_inicial=?, ingresos=?, egresos=?, saldo_final=?, id_usuario=? WHERE id_caja=?");
        $stmt->bind_param("ssdddsii", $fecha_apertura, $fecha_cierre, $saldo_inicial, $ingresos, $egresos, $saldo_final, $id_usuario, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO Caja_Diaria (fecha_apertura, fecha_cierre, saldo_inicial, ingresos, egresos, saldo_final, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdddsi", $fecha_apertura, $fecha_cierre, $saldo_inicial, $ingresos, $egresos, $saldo_final, $id_usuario);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: caja_diaria.php");
    exit;
}

// ------------------ ELIMINAR ------------------
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM Caja_Diaria WHERE id_caja = ?");
    $stmt->bind_param("i", $id_del);
    $stmt->execute();
    $stmt->close();
    header("Location: caja_diaria.php");
    exit;
}

// ------------------ EDITAR ------------------
$editar = null;
if (isset($_GET['edit'])) {
    $id_edit = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM Caja_Diaria WHERE id_caja = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $res = $stmt->get_result();
    $editar = $res->fetch_assoc();
    $stmt->close();
}

// ------------------ LISTAR ------------------
$result = $conn->query("SELECT c.*, u.nombre AS usuario_nombre FROM Caja_Diaria c LEFT JOIN Usuario u ON c.id_usuario = u.id_usuario ORDER BY c.fecha_apertura DESC");
$usuarios = $conn->query("SELECT id_usuario, nombre FROM Usuario ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Caja Diaria</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    margin: 0;
    padding: 20px;
}

h2 { color: #2a7ae2; margin-bottom: 15px; }

form {
    background: #fff;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    max-width: 600px;
}

form input, form select {
    width: 100%;
    padding: 8px;
    margin: 5px 0 15px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
}

button {
    background-color: #2a7ae2;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    margin-right: 5px;
}

button:hover { background-color: #1f5bbf; }

table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 5px;
    overflow: hidden;
}

th, td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background: #f0f0f0;
}

td button {
    padding: 5px 10px;
    font-size: 12px;
}

button.editar { background-color: #007bff; }
button.editar:hover { background-color: #005bb5; }

button.eliminar { background-color: #e74c3c; }
button.eliminar:hover { background-color: #c0392b; }
</style>
</head>
<body>

<h2><?= $editar ? "Editar Caja Diaria" : "Nueva Caja Diaria"; ?></h2>
<form method="POST" action="caja_diaria.php">
    <input type="hidden" name="id_caja" value="<?= $editar['id_caja'] ?? ''; ?>">

    <label>Fecha Apertura:</label>
    <input type="date" name="fecha_apertura" required value="<?= $editar['fecha_apertura'] ?? date('Y-m-d'); ?>">

    <label>Fecha Cierre:</label>
    <input type="date" name="fecha_cierre" value="<?= $editar['fecha_cierre'] ?? ''; ?>">

    <label>Saldo Inicial:</label>
    <input type="number" step="0.01" name="saldo_inicial" required value="<?= $editar['saldo_inicial'] ?? '0.00'; ?>">

    <label>Ingresos:</label>
    <input type="number" step="0.01" name="ingresos" required value="<?= $editar['ingresos'] ?? '0.00'; ?>">

    <label>Egresos:</label>
    <input type="number" step="0.01" name="egresos" required value="<?= $editar['egresos'] ?? '0.00'; ?>">

    <label>Saldo Final:</label>
    <input type="number" step="0.01" name="saldo_final" required value="<?= $editar['saldo_final'] ?? '0.00'; ?>">

    <label>Usuario:</label>
    <select name="id_usuario" required>
        <option value="">Seleccione</option>
        <?php while($usr = $usuarios->fetch_assoc()):
            $selected = (isset($editar['id_usuario']) && $editar['id_usuario'] == $usr['id_usuario']) ? 'selected' : '';
        ?>
        <option value="<?= $usr['id_usuario']; ?>" <?= $selected; ?>><?= htmlspecialchars($usr['nombre']); ?></option>
        <?php endwhile; ?>
    </select>

    <button type="submit"><?= $editar ? "Guardar Cambios" : "Guardar"; ?></button>
</form>

<h2>Lista de Caja Diaria</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Fecha Apertura</th>
            <th>Fecha Cierre</th>
            <th>Saldo Inicial</th>
            <th>Ingresos</th>
            <th>Egresos</th>
            <th>Saldo Final</th>
            <th>Usuario</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id_caja']; ?></td>
            <td><?= $row['fecha_apertura']; ?></td>
            <td><?= $row['fecha_cierre'] ?: '-'; ?></td>
            <td><?= number_format($row['saldo_inicial'], 2, ',', '.'); ?></td>
            <td><?= number_format($row['ingresos'], 2, ',', '.'); ?></td>
            <td><?= number_format($row['egresos'], 2, ',', '.'); ?></td>
            <td><?= number_format($row['saldo_final'], 2, ',', '.'); ?></td>
            <td><?= htmlspecialchars($row['usuario_nombre']); ?></td>
            <td>
                <form style="display:inline;" method="GET" action="caja_diaria.php">
                    <input type="hidden" name="edit" value="<?= $row['id_caja']; ?>">
                    <button type="submit" class="editar">Editar</button>
                </form>
                <form style="display:inline;" method="GET" action="caja_diaria.php" onsubmit="return confirm('¿Eliminar este registro?');">
                    <input type="hidden" name="delete" value="<?= $row['id_caja']; ?>">
                    <button type="submit" class="eliminar">Eliminar</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
