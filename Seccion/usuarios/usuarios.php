<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../../Panel/includes/conexion.php';

// Guardar o actualizar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_usuario'] ?? null;
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $usuario = $_POST['usuario'];
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'];

    if ($id) {
        if (!empty($password)) {
            // Actualizar con nueva contraseña (hasheada)
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE Usuario SET nombre=?, apellido=?, usuario=?, contraseña=?, rol=? WHERE id_usuario=?");
            $stmt->bind_param("sssssi", $nombre, $apellido, $usuario, $pass_hash, $rol, $id);
        } else {
            // Actualizar sin cambiar contraseña
            $stmt = $conn->prepare("UPDATE Usuario SET nombre=?, apellido=?, usuario=?, rol=? WHERE id_usuario=?");
            $stmt->bind_param("ssssi", $nombre, $apellido, $usuario, $rol, $id);
        }
        $stmt->execute();
        $stmt->close();
    } else {
        // Insertar nuevo usuario con contraseña hasheada
        $pass_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO Usuario (nombre, apellido, usuario, contraseña, rol) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nombre, $apellido, $usuario, $pass_hash, $rol);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: usuarios.php");
    exit;
}

// Eliminar usuario
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM Usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_del);
    $stmt->execute();
    $stmt->close();

    header("Location: usuarios.php");
    exit;
}

// Obtener usuario para editar
$editar = null;
if (isset($_GET['edit'])) {
    $id_edit = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT id_usuario, nombre, apellido, usuario, rol FROM Usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $res = $stmt->get_result();
    $editar = $res->fetch_assoc();
    $stmt->close();
}

// Listar usuarios
$result = $conn->query("SELECT id_usuario, nombre, apellido, usuario, rol FROM Usuario ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
</head>
<body>

<h2><?php echo $editar ? "Editar Usuario" : "Nuevo Usuario"; ?></h2>
<form method="POST" action="usuarios.php">
    <input type="hidden" name="id_usuario" value="<?php echo $editar['id_usuario'] ?? ''; ?>">

    <label>Nombre:</label><br>
    <input type="text" name="nombre" required value="<?php echo htmlspecialchars($editar['nombre'] ?? ''); ?>"><br><br>

    <label>Apellido:</label><br>
    <input type="text" name="apellido" required value="<?php echo htmlspecialchars($editar['apellido'] ?? ''); ?>"><br><br>

    <label>Usuario (login):</label><br>
    <input type="text" name="usuario" required value="<?php echo htmlspecialchars($editar['usuario'] ?? ''); ?>"><br><br>

    <label>Contraseña: <?php if($editar) echo "(Dejar vacío para no cambiar)"; ?></label><br>
    <input type="password" name="password" <?php echo $editar ? '' : 'required'; ?> ><br><br>

    <label>Rol:</label><br>
    <select name="rol" required>
        <option value="">Seleccione</option>
        <option value="Administrador" <?php echo (isset($editar['rol']) && $editar['rol'] == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
        <option value="Vendedor" <?php echo (isset($editar['rol']) && $editar['rol'] == 'Vendedor') ? 'selected' : ''; ?>>Vendedor</option>
        <option value="Cajero" <?php echo (isset($editar['rol']) && $editar['rol'] == 'Cajero') ? 'selected' : ''; ?>>Cajero</option>
    </select><br><br>

    <button type="submit">Guardar</button>
</form>

<hr>

<h2>Lista de Usuarios</h2>
<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id_usuario']; ?></td>
            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
            <td><?php echo htmlspecialchars($row['apellido']); ?></td>
            <td><?php echo htmlspecialchars($row['usuario']); ?></td>
            <td><?php echo $row['rol']; ?></td>
            <td>
                <a href="usuarios.php?edit=<?php echo $row['id_usuario']; ?>">Editar</a> |
                <a href="usuarios.php?delete=<?php echo $row['id_usuario']; ?>" onclick="return confirm('¿Eliminar este usuario?');">Eliminar</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>

