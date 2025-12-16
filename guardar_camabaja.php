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
$redirectUrl = "camabaja.php"; // Redirige a la página de gestión de camabaja

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir los datos del formulario
    $marca_camabaja = $_POST['marca_camabaja'];
    $patente_camabaja = $_POST['patente_camabaja'];
    $modelo_camabaja = $_POST['modelo_camabaja'];
    $ano_camabaja = $_POST['ano_camabaja'];

    // Validar datos (opcional)
    if (empty($marca_camabaja) || empty($patente_camabaja)) {
        $modalTitle = "Error";
        $modalMessage = "Por favor, completa todos los campos obligatorios.";
    } else {
        // Preparar consulta SQL
        $sql = "INSERT INTO camabaja 
                (marca_camabaja, patente_camabaja, modelo_camabaja, ano_camabaja)
                VALUES 
                ('$marca_camabaja', '$patente_camabaja', '$modelo_camabaja', '$ano_camabaja')";

        // Ejecutar la consulta
        if ($conn->query($sql) === TRUE) {
            $modalTitle = "Éxito";
            $modalMessage = "Cama Baja guardado exitosamente.";
        } else {
            $modalTitle = "Error";
            $modalMessage = "Error al guardar la cama baja: " . $conn->error;
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
    <title>Guardar Cama Baja</title>
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
