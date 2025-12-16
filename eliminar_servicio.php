<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<?php
include 'content/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_servicios = $_POST['id_servicios'];

    // Eliminar el servicio
    $sql_servicio = "DELETE FROM servicios WHERE id_servicios = ?";
    $stmt = $conn->prepare($sql_servicio);
    $stmt->bind_param("i", $id_servicios);

    if ($stmt->execute()) {
        $modal_message = "Servicio eliminado exitosamente.";
        $success = true;
    } else {
        $modal_message = "Error al eliminar el servicio: " . $conn->error;
        $success = false;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Servicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Resultado de la operación</h5>
                </div>
                <div class="modal-body">
                    <?php echo $modal_message; ?>
                </div>
                <div class="modal-footer">
                    <a href="servicios.php" class="btn btn-primary">Aceptar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar el modal automáticamente al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            var confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            confirmationModal.show();
        });
    </script>
</body>
</html>
