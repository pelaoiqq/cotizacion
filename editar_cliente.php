<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<?php
include 'content/header.php';
include 'content/connect.php';


// Obtener el id del cliente de la URL
$id_cliente = $_GET['id_cliente'];

// Obtener los datos del cliente desde la base de datos
$sql = "SELECT * FROM clientes WHERE id_cliente = $id_cliente";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();
} else {
    echo "<p class='text-center'>Cliente no encontrado.</p>";
    exit;
}

// Verificar si el formulario fue enviado para actualizar los datos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_cliente = $_POST['nombre_cliente'];
    $email_cliente = $_POST['email_cliente'];
    $telefono_cliente = $_POST['telefono_cliente'];
    $direccion_cliente = $_POST['direccion_cliente'];
    $rut_cliente = $_POST['rut_cliente']; // Nuevo campo
    $nombre_contacto = $_POST['nombre_contacto'];
    $telefono_contacto = $_POST['telefono_contacto'];
    $email_contacto = $_POST['email_contacto'];


    // Actualizar los datos del cliente
    $update_sql = "UPDATE clientes SET 
        nombre_cliente = '$nombre_cliente', 
        email_cliente = '$email_cliente', 
        telefono_cliente = '$telefono_cliente', 
        direccion_cliente = '$direccion_cliente', 
        rut_cliente = '$rut_cliente',
        nombre_contacto = '$nombre_contacto',
        telefono_contacto ='$telefono_contacto',
        email_contacto ='$email_contacto' 
 
        WHERE id_cliente = $id_cliente";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "<script>alert('Cliente actualizado correctamente.'); window.location.href='clientes.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<div class="container my-5">
    <h2 class="text-center">Editar Cliente</h2>
    <form action="" method="POST" class="mt-4">

    <h5 class="mt-4">Detalles de la Empresa</h5>
        <div class="form-floating row g-3">
            <div class="col-md">
                <div class="form-floating">
                    <input type="text" class="form-control" name="rut_cliente" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['rut_cliente']); ?>"required>
                    <label for="rut_cliente">Rut</label>
                </div>
            </div>
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="nombre_cliente" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['nombre_cliente']); ?>" style="text-transform: uppercase;" required>
                    <label for="nombre_cliente">Razón Social / Nombre Completo</label>
                </div>
            </div> 
            <div class="col-md">
                <div class="form-floating">
                    <input type="email" class="form-control" name="email_cliente" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['email_cliente']); ?>" style="text-transform: uppercase;" required>
                    <label for="email_cliente">Correo Electrónico</label>
                </div>
            </div>
        </div>

    <div class="form-floating row g-3">

        <div class="col-md"> 
            <div class="form-floating mb-3">
                <input type="text" class="form-control" name="telefono_cliente" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['telefono_cliente']); ?>" required>
                <label for="telefono_cliente">Teléfono Empresa</label>
            </div>
        </div>
        <div class="col-md">
            <div class="form-floating">
                <input type="text" class="form-control" name="direccion_cliente" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['direccion_cliente']); ?>" style="text-transform: uppercase;" required>
                <label for="direccion_cliente">Dirección</label>
            </div>
        </div>
        <div class="col-md">
            <div class="form-floating">
            </div>
        </div>
    </div>

    <h5 class="mt-4">Detalles del Contacto</h5>
    <div class="form-floating row g-3">
        <div class="col-md"> 
            <div class="form-floating mb-3">
                <input type="text" class="form-control" name="nombre_contacto" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['nombre_contacto']); ?>" style="text-transform: uppercase;" required>
                <label for="nombre_contacto">Nombre del Contacto</label>
            </div>
        </div>
        <div class="col-md">
            <div class="form-floating">
                <input type="text" class="form-control" name="telefono_contacto" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['telefono_contacto']); ?>" required>
                <label for="telefono_contacto">Teléfono del Contacto</label>
            </div>
        </div>
        <div class="col-md">
            <div class="form-floating">
                <input type="text" class="form-control" name="email_contacto" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['email_contacto']); ?>" style="text-transform: uppercase;" required>
                <label for="email_contacto">Correo del Contacto</label>
            </div>
        </div>
    </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary">Actualizar Cliente</button>
            <a href="clientes.php" class="btn btn-outline-secondary d-inline-flex align-items-center">Volver</a>

        </div>
    </form>
</div>

<?php include 'content/footer.php'; ?>
