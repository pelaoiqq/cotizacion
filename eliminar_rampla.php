<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_rampla"])) {
    $id_rampla = $_POST["id_rampla"];
    
    $sql = "DELETE FROM rampla WHERE id_rampla = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_rampla);

    if ($stmt->execute()) {
        $_SESSION["mensaje"] = "Rampla eliminada correctamente.";
    } else {
        $_SESSION["error"] = "Error al eliminar el rampla.";
    }
    $stmt->close();
    $conn->close();

    header("Location: ramplas.php");
    exit();
} else {
    header("Location: ramplas.php");
    exit();
}
?>
