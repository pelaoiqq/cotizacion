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

// Obtener el id de la rampla de la URL 
$id_servicios = $_GET['id_servicios'];

// Obtener los datos de la rampla desde la base de datos 
$sql = "SELECT * FROM servicios WHERE id_servicios = $id_servicios";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $servicios = $result->fetch_assoc();
} else {
    echo "<p class='text-center'>Rampla no encontrado.</p>";
    exit;
}

// Verificar si el formulario fue enviado para actualizar los datos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_servicio = $_POST['nombre_servicio']; // Nuevo campo
    $valor_servicios = $_POST['valor_servicios'];


    // Actualizar los datos del camion
    $update_sql = "UPDATE servicios SET 
        nombre_servicio = '$nombre_servicio',
        valor_servicios = '$valor_servicios'

        WHERE id_servicios = $id_servicios";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "<script>alert('Servicio actualizado correctamente.'); window.location.href='servicios.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<div class="container my-5">
    <h2 class="text-center">Editar Servicios</h2>
    <form action="" method="POST" class="mt-4">

        <div class="form-floating row g-3">
            <div class="col-md">
                <div class="form-floating">
                    <input type="text" class="form-control" name="nombre_servicio" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($servicios['nombre_servicio']); ?>"required>
                    <label for="nombre_servicio">Nombre del Servicio</label>
                </div>
            </div>
            <div class="col-md">
                <div class="form-floating">
                    <input type="number" class="form-control" name="valor_servicios" id="valor_servicios" placeholder="Valor del servicio" value="<?php echo htmlspecialchars($servicios['valor_servicios']); ?>"required>
                    <label for="valor_servicios">Valor del Servicio</label>
                </div>
            </div>
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <select class="form-control" name="area_servicios" id="area_servicios" required>
                        <option value="Arriendo" <?php echo $user['area_servicios'] === 'Arriendo' ? 'selected' : ''; ?>>Arriendo</option>
                        <option value="Transporte" <?php echo $user['area_servicios'] === 'Transporte' ? 'selected' : ''; ?>>Transporte</option>
                        <option value="Servicios" <?php echo $user['area_servicios'] === 'Servicios' ? 'selected' : ''; ?>>Servicios</option>
                    </select>
                    <label for="area_servicios">Área del Servicio</label>
                </div>
            </div>
        </div>

        <br>
        <div class="text-center">
            <button type="submit" class="btn btn-primary">Actualizar Servicio</button>
            <a href="servicios.php" class="btn btn-outline-secondary d-inline-flex align-items-center">Volver</a>

        </div>
    </form>
</div>

<?php include 'content/footer.php'; ?>
