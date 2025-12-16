<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_camiones"])) {
    $id_camion = $_POST["id_camiones"];
    
    $sql = "DELETE FROM camiones WHERE id_camiones = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_camion);

    if ($stmt->execute()) {
        $_SESSION["mensaje"] = "Camión eliminado correctamente.";
    } else {
        $_SESSION["error"] = "Error al eliminar el camión.";
    }
    $stmt->close();
    $conn->close();

    header("Location: camiones.php");
    exit();
} else {
    header("Location: camiones.php");
    exit();
}
?>
