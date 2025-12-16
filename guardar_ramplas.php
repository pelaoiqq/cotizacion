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
?>
<?php
include 'content/connect.php';

// Inicializar variables para mensajes
$modalTitle = "";
$modalMessage = "";
$redirectUrl = "ramplas.php"; // Redirige a la página de gestión de rampla

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir los datos del formulario
    $marca_rampla = $_POST['marca_rampla'];
    $patente_rampla = $_POST['patente_rampla'];
    $modelo_rampla = $_POST['modelo_rampla'];
    $ano_rampla = $_POST['ano_rampla'];

    // Validar datos (opcional)
    if (empty($marca_rampla) || empty($patente_rampla)) {
        $modalTitle = "Error";
        $modalMessage = "Por favor, completa todos los campos obligatorios.";
    } else {
        // Preparar consulta SQL
        $sql = "INSERT INTO rampla 
                (marca_rampla, patente_rampla, modelo_rampla, ano_rampla)
                VALUES 
                ('$marca_rampla', '$patente_rampla', '$modelo_rampla', '$ano_rampla')";

        // Ejecutar la consulta
        if ($conn->query($sql) === TRUE) {
            $modalTitle = "Éxito";
            $modalMessage = "Rampla guardado exitosamente.";
        } else {
            $modalTitle = "Error";
            $modalMessage = "Error al guardar la rampla: " . $conn->error;
        }

        // Cerrar conexión
        $conn->close();
    }
} else {
    $modalTitle = "Error";
    $modalMessage = "Acceso no autorizado.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardar Rampla</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <!-- Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><?php echo $modalTitle; ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo $modalMessage; ?>
                </div>
                <div class="modal-footer">
                    <a href="<?php echo $redirectUrl; ?>" class="btn btn-primary">Cerrar</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para mostrar el modal automáticamente -->
    <script>
        $(document).ready(function() {
            $('#messageModal').modal('show');
        });
    </script>
</body>
</html>
