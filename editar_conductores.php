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

// Obtener el id del conductor de la URL
$id_conductor = $_GET['id_conductor'];

// Obtener los datos del conductor desde la base de datos
$sql = "SELECT * FROM conductores WHERE id_conductor = $id_conductor";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $conductor = $result->fetch_assoc();
} else {
    echo "<p class='text-center'>conductor no encontrado.</p>";
    exit;
}

// Verificar si el formulario fue enviado para actualizar los datos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rut_conductor = $_POST['rut_conductor']; // Nuevo campo
    $nombre_conductor = $_POST['nombre_conductor'];
    $telefono_conductor = $_POST['telefono_conductor'];


    // Actualizar los datos del conductor
    $update_sql = "UPDATE conductores SET 
        rut_conductor = '$rut_conductor',
        nombre_conductor = '$nombre_conductor', 
        telefono_conductor = '$telefono_conductor'

        WHERE id_conductor = $id_conductor";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "<script>alert('Conductor actualizado correctamente.'); window.location.href='conductores.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<div class="container my-5">
    <h2 class="text-center">Editar Conductor</h2>
    <form action="" method="POST" class="mt-4">

        <div class="form-floating row g-3">
            <div class="col-md">
                <div class="form-floating">
                    <input type="text" class="form-control" name="rut_conductor" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($conductor['rut_conductor']); ?>"required readonly>
                    <label for="rut_conductor">Rut</label>
                </div>
            </div>
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="nombre_conductor" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($conductor['nombre_conductor']); ?>" required>
                    <label for="nombre_conductor">Nombre Completo</label>
                </div>
            </div> 
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="telefono_conductor" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($conductor['telefono_conductor']); ?>" required>
                    <label for="telefono_conductor">Teléfono Empresa</label>
                </div>
            </div>
        </div>

        <br>
        <div class="text-center">
            <button type="submit" class="btn btn-primary">Actualizar Conductor</button>
            <a href="conductores.php" class="btn btn-outline-secondary d-inline-flex align-items-center">Volver</a>
        </div>
    </form>
</div>

<?php include 'content/footer.php'; ?>
