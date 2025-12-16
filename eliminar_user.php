<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_user"])) {
    $id_user = $_POST["id_user"];
    
    $sql = "DELETE FROM users WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_user);

    if ($stmt->execute()) {
        $_SESSION["mensaje"] = "Usuario eliminado correctamente.";
    } else {
        $_SESSION["error"] = "Error al eliminar el Usuario.";
    }
    $stmt->close();
    $conn->close();

    header("Location: user.php");
    exit();
} else {
    header("Location: user.php");
    exit();
}
?>
