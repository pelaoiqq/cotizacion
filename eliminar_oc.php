<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_oc"])) {
    $id_oc = $_POST["id_oc"];
    
    $sql = "DELETE FROM oc WHERE id_oc = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_oc);

    if ($stmt->execute()) {
        $_SESSION["mensaje"] = "OC eliminado correctamente.";
    } else {
        $_SESSION["error"] = "Error al eliminar la OC.";
    }
    $stmt->close();
    $conn->close();

    header("Location: listado_oc.php");
    exit();
} else {
    header("Location: listado_oc.php");
    exit();
}
?>
