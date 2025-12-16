<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/header.php';
include 'content/connect.php';

// Obtener el id_user de la sesión
$id_user = $_SESSION['id_user'];

// Obtener los datos del usuario desde la base de datos
$sql_user = "SELECT nombre_user, nombre_completo, pass_user FROM users WHERE id_user = '$id_user'";
$result_user = $conn->query($sql_user);

if ($result_user->num_rows > 0) {
    $user = $result_user->fetch_assoc();
} else {
    echo "<script>alert('Usuario no encontrado.'); window.location.href='index.php';</script>";
    exit();
}

// Verificar si el formulario fue enviado para actualizar los datos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_user_form = $_POST['nombre_user']; 
    $nombre_completo_form = $_POST['nombre_completo']; 
    $pass_user_form = $_POST['pass_user']; 

    // Determinar si se actualiza la contraseña
    $pass_user = empty($pass_user_form) ? $user['pass_user'] : password_hash($pass_user_form, PASSWORD_DEFAULT);

    // Actualizar los datos del usuario
    $update_sql_user = "UPDATE users SET
        nombre_user = '$nombre_user_form', 
        nombre_completo = '$nombre_completo_form',
        pass_user = '$pass_user'          
        WHERE id_user = $id_user";

    if ($conn->query($update_sql_user) === TRUE) {
        echo "<script>alert('Datos actualizados correctamente.'); window.location.href='index.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<div class="container my-5">
    <h2 class="text-center">Editar Mis Datos</h2>
    <form action="" method="POST" class="mt-4">

        <div class="content">
            <h5>Datos para Acceder a Plataforma</h5>
        </div>

        <div class="form-floating row g-3">
            <!-- Campo Usuario -->
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="nombre_user" id="nombre_user" placeholder="Usuario" 
                           value="<?php echo htmlspecialchars($user['nombre_user']); ?>" required>
                    <label for="nombre_user">Usuario</label>
                </div>
            </div>
            
            <!-- Campo Nombre Completo -->
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="nombre_completo" id="nombre_completo" placeholder="Nombre Completo" 
                           value="<?php echo htmlspecialchars($user['nombre_completo']); ?>" required>
                    <label for="nombre_completo">Nombre Completo</label>
                </div>
            </div>
            
            <!-- Campo Contraseña -->
            <div class="col-md">
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" name="pass_user" id="pass_user" placeholder="Contraseña">
                    <label for="pass_user">Contraseña (dejar en blanco para no cambiar)</label>
                </div>
            </div>
        </div>

        <br>

        <div class="text-center">
            <button type="submit" class="btn btn-primary">Actualizar Datos</button>
            <a href="index.php" class="btn btn-outline-secondary d-inline-flex align-items-center">Volver</a>
        </div>

    </form>
</div>

<?php include 'content/footer.php'; ?>
