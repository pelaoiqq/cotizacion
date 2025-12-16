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
$redirectUrl = "clientes.php"; // Redirige a la página de gestión de clientes 

// Función para validar RUT chileno
function validarRut($rut) {
    $rut = preg_replace('/[^0-9kK]/', '', $rut); // Eliminar caracteres no válidos
    if (strlen($rut) < 2) return false;

    $dv = strtoupper(substr($rut, -1)); // Extraer dígito verificador
    $numero = substr($rut, 0, -1);

    $suma = 0;
    $multiplo = 2;
    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $suma += $numero[$i] * $multiplo;
        $multiplo = ($multiplo == 7) ? 2 : $multiplo + 1;
    }
    $resto = $suma % 11;
    $dvCalculado = 11 - $resto;

    if ($dvCalculado == 11) $dvCalculado = '0';
    if ($dvCalculado == 10) $dvCalculado = 'K';

    return $dv == $dvCalculado;
}

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rut_cliente = $_POST['rut_cliente'];
    $nombre_cliente = isset($_POST['nombre_cliente']) ? strtoupper($_POST['nombre_cliente']) : '';
    $email_cliente = isset($_POST['email_cliente']) ? strtoupper($_POST['email_cliente']) : '';
    $telefono_cliente = $_POST['telefono_cliente'];
    $direccion_cliente = isset($_POST['direccion_cliente']) ? strtoupper($_POST['direccion_cliente']) : '';
    $nombre_contacto = isset($_POST['nombre_contacto']) ? strtoupper($_POST['nombre_contacto']) : '';
    $email_contacto = isset($_POST['email_contacto']) ? strtoupper($_POST['email_contacto']) : '';
    $telefono_contacto = $_POST['telefono_contacto'];


    // Validar datos (opcional)
    if (empty($rut_cliente) || empty($nombre_cliente)) {
        $modalTitle = "Error";
        $modalMessage = "Por favor, completa todos los campos obligatorios.";
    } else {
        // Preparar consulta SQL
        $sql = "INSERT INTO clientes 
                (rut_cliente, nombre_cliente, email_cliente, telefono_cliente, direccion_cliente, nombre_contacto, email_contacto, telefono_contacto)
                VALUES 
                ('$rut_cliente', '$nombre_cliente', '$email_cliente', '$telefono_cliente', '$direccion_cliente', '$nombre_contacto', '$email_contacto', '$telefono_contacto')";

        // Ejecutar la consulta
        if ($conn->query($sql) === TRUE) {
            $modalTitle = "Éxito";
            $modalMessage = "Cliente guardado exitosamente.";
        } else {
            $modalTitle = "Error";
            $modalMessage = "Error al guardar el cliente: " . $conn->error;
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
    <title>Guardar Cliente</title>
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
