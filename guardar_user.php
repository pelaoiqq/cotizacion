<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

$modalTitle = ''; $modalMessage = ''; $modalType = ''; $redirectUrl = "user.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = isset($_POST['user']) ? trim($_POST['user']) : '';
    $nombre_user = isset($_POST['nombre_user']) ? trim($_POST['nombre_user']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $rol_usuario = isset($_POST['rol_usuario']) ? trim($_POST['rol_usuario']) : '';
    // Nuevo campo estado (con valor por defecto)
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'Activo';

    if (empty($user) || empty($nombre_user) || empty($password) || empty($rol_usuario)) {
        $modalTitle = 'Error';
        $modalMessage = 'Todos los campos son obligatorios.';
        $modalType = 'danger';
    } else {
        // Verificar si el usuario ya existe
        $check = $conn->query("SELECT id_user FROM users WHERE nombre_user = '$user'");
        if ($check->num_rows > 0) {
            $modalTitle = 'Error';
            $modalMessage = 'El nombre de usuario ya está registrado.';
            $modalType = 'warning';
        } else {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar con estado
            $stmt = $conn->prepare("INSERT INTO users (nombre_user, pass_user, nombre_completo, rol_usuario, estado) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $user, $password_hashed, $nombre_user, $rol_usuario, $estado);

            if ($stmt->execute()) {
                $modalTitle = 'Éxito';
                $modalMessage = 'Usuario registrado exitosamente.';
                $modalType = 'success';
            } else {
                $modalTitle = 'Error';
                $modalMessage = 'Error en base de datos: ' . $conn->error;
                $modalType = 'danger';
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>
<!-- ... (El resto del HTML del modal igual que antes) ... -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <title>Respuesta</title>
</head>
<body>
    <div class="modal fade" id="messageModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-<?php echo $modalType; ?> text-white">
                    <h5 class="modal-title"><?php echo htmlspecialchars($modalTitle); ?></h5>
                </div>
                <div class="modal-body text-center">
                    <p class="fs-5"><?php echo htmlspecialchars($modalMessage); ?></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="<?php echo $redirectUrl; ?>" class="btn btn-<?php echo $modalType; ?> w-50">Aceptar</a>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($modalMessage)): ?>
    <script>
        var myModal = new bootstrap.Modal(document.getElementById('messageModal'));
        myModal.show();
    </script>
    <?php endif; ?>
</body>
</html>