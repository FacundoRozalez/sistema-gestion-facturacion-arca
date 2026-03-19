<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../../Panel/includes/conexion.php';

// Guardar o actualizar medio_pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_medio_pago'] ?? null;
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $requiere_datos = $_POST['requiere_datos'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE Medio_Pago SET nombre=?, tipo=?, requiere_datos=? WHERE id_medio_pago=?");
        $stmt->bind_param("sssi", $nombre, $tipo, $requiere_datos, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO Medio_Pago (nombre, tipo, requiere_datos) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $tipo, $requiere_datos);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: medio_pago.php");
    exit;
}

// Eliminar medio_pago
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM Medio_Pago WHERE id_medio_pago = ?");
    $stmt->bind_param("i", $id_del);
    $stmt->execute();
    $stmt->close();

    header("Location: medio_pago.php");
    exit;
}

// Obtener medio_pago para editar
$editar = null;
if (isset($_GET['edit'])) {
    $id_edit = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM Medio_Pago WHERE id_medio_pago = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $res = $stmt->get_result();
    $editar = $res->fetch_assoc();
    $stmt->close();
}

// Listar medios de pago
$result = $conn->query("SELECT * FROM Medio_Pago ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Medios de Pago</title>
</head>
<body>

<h2><?php echo $editar ? "Editar Medio de Pago" : "Nuevo Medio de Pago"; ?></h2>
<form method="POST" action="medio_pago.php">
    <input type="hidden" name="id_medio_pago" value="<?php echo $editar['id_medio_pago'] ?? ''; ?>">

    <label>Nombre:</label><br>
    <input type="text" name="nombre" required value="<?php echo htmlspecialchars($editar['nombre'] ?? ''); ?>"><br><br>

    <label>Tipo:</label><br>
    <select name="tipo" required>
        <option value="">Seleccione</option>
        <option value="Contado" <?php echo (isset($editar['tipo']) && $editar['tipo'] == 'Contado') ? 'selected' : ''; ?>>Contado</option>
        <option value="Crédito" <?php echo (isset($editar['tipo']) && $editar['tipo'] == 'Crédito') ? 'selected' : ''; ?>>Crédito</option>
    </select><br><br>

    <label>¿Requiere datos adicionales?</label><br>
    <select name="requiere_datos" required>
        <option value="">Seleccione</option>
        <option value="Sí" <?php echo (isset($editar['requiere_datos']) && $editar['requiere_datos'] == 'Sí') ? 'selected' : ''; ?>>Sí</option>
        <option value="No" <?php echo (isset($editar['requiere_datos']) && $editar['requiere_datos'] == 'No') ? 'selected' : ''; ?>>No</option>
    </select><br><br>

    <button type="submit">Guardar</button>
</form>

<hr>

<h2>Lista de Medios de Pago</h2>
<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Requiere Datos</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id_medio_pago']; ?></td>
            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
            <td><?php echo $row['tipo']; ?></td>
            <td><?php echo $row['requiere_datos']; ?></td>
            <td>
                <a href="medio_pago.php?edit=<?php echo $row['id_medio_pago']; ?>">Editar</a> |
                <a href="medio_pago.php?delete=<?php echo $row['id_medio_pago']; ?>" onclick="return confirm('¿Eliminar este medio de pago?');">Eliminar</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
