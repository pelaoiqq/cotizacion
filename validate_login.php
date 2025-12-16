<?php
session_start();
include 'content/connect.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capturar y sanitizar entradas
    $nombre_user = trim($_POST['nombre_user']);
    $pass_user = $_POST['pass_user'];

    // --- CAMBIO 1: Agregamos 'estado' a la consulta ---
    $query = "SELECT id_user, pass_user, nombre_completo, rol_usuario, estado FROM users WHERE nombre_user = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $nombre_user);
    $stmt->execute();
    $stmt->store_result();

    // Variables de control
    $login_exitoso = false;
    $cuenta_suspendida = false;

    if ($stmt->num_rows > 0) {
        // --- CAMBIO 2: Recibimos el 'estado' ---
        $stmt->bind_result($id_user, $hash_password, $nombre_completo, $rol_usuario, $estado);
        $stmt->fetch();

        // --- CAMBIO 3: Verificamos el estado ANTES o DURANTE la validación ---
        if ($estado === 'Suspendido') {
            $cuenta_suspendida = true;
        } else {
            // Solo verificamos contraseña si NO está suspendido
            if (password_verify($pass_user, $hash_password)) {
                $login_exitoso = true;
            }
        }
    }

    $stmt->close();

    // Lógica de redirección según el resultado
    if ($cuenta_suspendida) {
        // Redirigir con error=2 (Cuenta suspendida)
        header("Location: login.php?error=2");
        exit();
    } 
    elseif ($login_exitoso) {
        // --- Login Exitoso ---
        session_regenerate_id(true);

        // Registro del log
        $log_query = "INSERT INTO login_log (id_user) VALUES (?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("i", $id_user);
        $log_stmt->execute();
        $log_stmt->close();

        // Datos de sesión
        $_SESSION['loggedin'] = true;
        $_SESSION['id_user'] = $id_user;
        $_SESSION['nombre_user'] = $nombre_user;
        $_SESSION['nombre_completo'] = $nombre_completo;
        $_SESSION['rol_usuario'] = $rol_usuario;

        header("Location: dashboard.php");
        exit();
    } 
    else {
        // Redirigir con error=1 (Datos incorrectos)
        header("Location: login.php?error=1");
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}
?>