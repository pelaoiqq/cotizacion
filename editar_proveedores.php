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
$id_proveedor = $_GET['id_proveedor'];

// Obtener los datos del cliente desde la base de datos
$sql = "SELECT * FROM proveedor WHERE id_proveedor = $id_proveedor";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();
} else {
    echo "<p class='text-center'>Cliente no encontrado.</p>";
    exit;
}

// Verificar si el formulario fue enviado para actualizar los datos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_proveedor = $_POST['nombre_proveedor'];
    $correo_proveedor = $_POST['correo_proveedor'];
    $telefono_proveedor = $_POST['telefono_proveedor'];
    $direccion_proveedor = $_POST['direccion_proveedor'];
    $rut_proveedor = $_POST['rut_proveedor']; // Nuevo campo
    $nombre_contacto = $_POST['nombre_contacto'];
    $telefono_contacto = $_POST['telefono_contacto'];
    $email_contacto = $_POST['email_contacto'];


    // Actualizar los datos del cliente
    $update_sql = "UPDATE proveedor SET 
        nombre_proveedor = '$nombre_proveedor', 
        correo_proveedor = '$correo_proveedor', 
        telefono_proveedor = '$telefono_proveedor', 
        direccion_proveedor = '$direccion_proveedor', 
        rut_proveedor = '$rut_proveedor',
        nombre_contacto = '$nombre_contacto',
        telefono_contacto ='$telefono_contacto',
        email_contacto ='$email_contacto' 
 
        WHERE id_proveedor = $id_proveedor";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "<script>alert('Proveedor actualizado correctamente.'); window.location.href='proveedores.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<div class="container my-5">
    <h2 class="text-center">Editar Proveedor</h2>
    <form action="" method="POST" class="mt-4">

    <h5 class="mt-4">Detalles de la Empresa</h5>
        <div class="form-floating row g-3">
            <div class="col-md">
                <div class="form-floating">
                    <input type="text" class="form-control" name="rut_proveedor" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['rut_proveedor']); ?>"required>
                    <label for="rut_proveedor">Rut</label>
                </div>
            </div>
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="nombre_proveedor" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['nombre_proveedor']); ?>" required>
                    <label for="nombre_proveedor">Razón Social / Nombre Completo</label>
                </div>
            </div> 
            <div class="col-md">
                <div class="form-floating">
                    <input type="email" class="form-control" name="correo_proveedor" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['correo_proveedor']); ?>" required>
                    <label for="correo_proveedor">Correo Electrónico</label>
                </div>
            </div>
        </div>

    <div class="form-floating row g-3">

        <div class="col-md"> 
            <div class="form-floating mb-3">
                <input type="text" class="form-control" name="telefono_proveedor" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['telefono_proveedor']); ?>" required>
                <label for="telefono_proveedor">Teléfono Empresa</label>
            </div>
        </div>
        <div class="col-md">
            <div class="form-floating">
                <input type="text" class="form-control" name="direccion_proveedor" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['direccion_proveedor']); ?>" required>
                <label for="direccion_proveedor">Dirección</label>
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
                <input type="text" class="form-control" name="nombre_contacto" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['nombre_contacto']); ?>" required>
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
                <input type="text" class="form-control" name="email_contacto" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($cliente['email_contacto']); ?>" required>
                <label for="email_contacto">Correo del Contacto</label>
            </div>
        </div>
    </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary">Actualizar Proveedor</button>
            <a href="proveedores.php" class="btn btn-outline-secondary d-inline-flex align-items-center">Volver</a>

        </div>
    </form>
</div>

<?php include 'content/footer.php'; ?>
