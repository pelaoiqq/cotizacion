<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_edp"])) {
    $id_edp = $_POST["id_edp"];
    
    $sql = "DELETE FROM edp WHERE id_edp = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_edp);

    if ($stmt->execute()) {
        $_SESSION["mensaje"] = "EDP eliminado correctamente.";
    } else {
        $_SESSION["error"] = "Error al eliminar el EDP.";
    }
    $stmt->close();
    $conn->close();

    header("Location: listado_edp.php");
    exit();
} else {
    header("Location: listado_edp.php");
    exit();
}
?>
