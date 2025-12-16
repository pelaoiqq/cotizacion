<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_camabaja"])) {
    $id_camion = $_POST["id_camabaja"];
    
    $sql = "DELETE FROM camabaja WHERE id_camabaja = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_camion);

    if ($stmt->execute()) {
        $_SESSION["mensaje"] = "Cama baja eliminado correctamente.";
    } else {
        $_SESSION["error"] = "Error al eliminar el cama baja.";
    }
    $stmt->close();
    $conn->close();

    header("Location: camabaja.php");
    exit();
} else {
    header("Location: camabaja.php");
    exit();
}
?>
