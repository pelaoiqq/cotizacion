<?php
session_start();
if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = isset($_POST['id_user']) ? intval($_POST['id_user']) : 0;
    $nuevo_estado = isset($_POST['nuevo_estado']) ? $_POST['nuevo_estado'] : '';

    if ($id_user > 0 && in_array($nuevo_estado, ['Activo', 'Suspendido'])) {
        
        // Evitar que el admin se suspenda a sí mismo por error
        if ($id_user == $_SESSION['id_user']) {
            header("Location: user.php?msg=No puedes suspender tu propia cuenta.");
            exit();
        }

        $stmt = $conn->prepare("UPDATE users SET estado = ? WHERE id_user = ?");
        $stmt->bind_param("si", $nuevo_estado, $id_user);
        
        if ($stmt->execute()) {
            header("Location: user.php?msg=Estado actualizado a $nuevo_estado.");
        } else {
            header("Location: user.php?msg=Error al actualizar.");
        }
        $stmt->close();
    }
}
$conn->close();
?>