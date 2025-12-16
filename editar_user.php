<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

// Inicializar variables para el modal
$modalTitle = '';
$modalMessage = '';
$modalType = ''; // success o danger
$redirectUrl = "user.php"; // Redirección después del modal

// Verificar si se pasó un ID de usuario válido
if (isset($_GET['id_user']) && is_numeric($_GET['id_user'])) {
    $id_user = intval($_GET['id_user']);

    // Obtener los datos del usuario desde la base de datos
    $stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        $modalTitle = 'Error';
        $modalMessage = 'Usuario no encontrado.';
        $modalType = 'danger';
    }

    $stmt->close();
} else {
    $modalTitle = 'Error';
    $modalMessage = 'ID de usuario no válido.';
    $modalType = 'danger';
}

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($modalMessage)) {
    $nombre_user = isset($_POST['nombre_user']) ? trim($_POST['nombre_user']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $rol_usuario = isset($_POST['rol_usuario']) ? trim($_POST['rol_usuario']) : '';

    // Validar campos requeridos
    if (empty($nombre_user) || empty($rol_usuario)) {
        $modalTitle = 'Error';
        $modalMessage = 'Nombre completo y rol son obligatorios.';
        $modalType = 'danger';
    } else {
        // Actualizar el usuario en la base de datos
        if (!empty($password)) {
            // Si se proporcionó una nueva contraseña, cifrarla con password_hash
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET nombre_completo = ?, pass_user = ?, rol_usuario = ? WHERE id_user = ?");
            $stmt->bind_param("sssi", $nombre_user, $password_hashed, $rol_usuario, $id_user);
        } else {
            // Si no se proporcionó una nueva contraseña, mantener la existente
            $stmt = $conn->prepare("UPDATE users SET nombre_completo = ?, rol_usuario = ? WHERE id_user = ?");
            $stmt->bind_param("ssi", $nombre_user, $rol_usuario, $id_user);
        }

        if ($stmt->execute()) {
            $modalTitle = 'Éxito';
            $modalMessage = 'Usuario actualizado exitosamente.';
            $modalType = 'primary';
        } else {
            $modalTitle = 'Error';
            $modalMessage = 'Error al actualizar el usuario: ' . $conn->error;
            $modalType = 'danger';
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="apple-touch-icon" sizes="180x180" href="img/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="img/favicon/favicon-16x16.png">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <title>Editar Usuario</title>
</head>
<body>
    <?php include 'content/header.php'; ?>

    <div class="container my-5">
        <h2 class="text-center">Editar Usuario</h2>
        <form action="editar_user.php?id_user=<?php echo $id_user; ?>" method="POST" class="mt-4">
            <div class="form-floating row g-3">
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="nombre_user" id="nombre_user" value="<?php echo htmlspecialchars($user['nombre_completo']); ?>" required>
                        <label for="nombre_user">Nombre Completo</label>
                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" name="password" id="password" placeholder="Dejar en blanco para no cambiar">
                        <label for="password">Nueva Contraseña (Opcional)</label>
                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <select class="form-control" name="rol_usuario" id="rol_usuario" required>
                            <option value="Administrador" <?php echo $user['rol_usuario'] === 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                            <option value="Editor" <?php echo $user['rol_usuario'] === 'Editor' ? 'selected' : ''; ?>>Editor</option>
                            <option value="Visualizador" <?php echo $user['rol_usuario'] === 'Visualizador' ? 'selected' : ''; ?>>Visualizador</option>
                        </select>
                        <label for="rol_usuario">Rol</label>
                    </div>
                </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                        <a href="user.php" class="btn btn-outline-secondary d-inline-flex align-items-center">Volver</a>

                    </div>
            </div>
        </form>
    </div>

    <?php include 'content/footer.php'; ?>

    <!-- Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-<?php echo $modalType; ?> text-white">
                    <h5 class="modal-title" id="modalTitle"><?php echo htmlspecialchars($modalTitle); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo htmlspecialchars($modalMessage); ?>
                </div>
                <div class="modal-footer">
                    <a href="<?php echo $redirectUrl; ?>" class="btn btn-<?php echo $modalType; ?>">Aceptar</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mostrar el modal automáticamente si hay un mensaje -->
    <?php if (!empty($modalMessage)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            messageModal.show();
        });
    </script>
    <?php endif; ?>

</body>
</html>
