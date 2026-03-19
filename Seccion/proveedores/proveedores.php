<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../../Panel/includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_proveedor'] ?? null;
    $razon_social = $_POST['razon_social'];
    $cuit = $_POST['cuit'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $direccion = $_POST['direccion'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE Proveedor SET razon_social=?, cuit=?, telefono=?, email=?, direccion=? WHERE id_proveedor=?");
        $stmt->bind_param("sssssi", $razon_social, $cuit, $telefono, $email, $direccion, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO Proveedor (razon_social, cuit, telefono, email, direccion) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $razon_social, $cuit, $telefono, $email, $direccion);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: proveedores.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM Proveedor WHERE id_proveedor = ?");
    $stmt->bind_param("i", $id_del);
    $stmt->execute();
    $stmt->close();

    header("Location: proveedores.php");
    exit;
}

$editar = null;
if (isset($_GET['edit'])) {
    $id_edit = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM Proveedor WHERE id_proveedor = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $res = $stmt->get_result();
    $editar = $res->fetch_assoc();
    $stmt->close();
}

$result = $conn->query("SELECT * FROM Proveedor ORDER BY fecha_registro DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Proveedores</title></head>
<body>
<h2><?php echo $editar ? "Editar Proveedor" : "Nuevo Proveedor"; ?></h2>
<form method="POST">
    <input type="hidden" name="id_proveedor" value="<?php echo $editar['id_proveedor'] ?? ''; ?>">
    Razón Social:<br>
    <input type="text" name="razon_social" required value="<?php echo $editar['razon_social'] ?? ''; ?>"><br><br>
    CUIT:<br>
    <input type="text" name="cuit" value="<?php echo $editar['cuit'] ?? ''; ?>"><br><br>
    Teléfono:<br>
    <input type="text" name="telefono" value="<?php echo $editar['telefono'] ?? ''; ?>"><br><br>
    Email:<br>
    <input type="email" name="email" value="<?php echo $editar['email'] ?? ''; ?>"><br><br>
    Dirección:<br>
    <textarea name="direccion"><?php echo $editar['direccion'] ?? ''; ?></textarea><br><br>
    <button type="submit">Guardar</button>
</form>

<hr>

<h2>Lista de Proveedores</h2>
<table border="1" cellpadding="6" cellspacing="0">
    <thead><tr><th>ID</th><th>Razón Social</th><th>CUIT</th><th>Teléfono</th><th>Email</th><th>Dirección</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id_proveedor']; ?></td>
            <td><?php echo htmlspecialchars($row['razon_social']); ?></td>
            <td><?php echo htmlspecialchars($row['cuit']); ?></td>
            <td><?php echo htmlspecialchars($row['telefono']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['direccion']); ?></td>
            <td>
                <a href="proveedores.php?edit=<?php echo $row['id_proveedor']; ?>">Editar</a> |
                <a href="proveedores.php?delete=<?php echo $row['id_proveedor']; ?>" onclick="return confirm('Eliminar proveedor?');">Eliminar</a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</body>
</html>
