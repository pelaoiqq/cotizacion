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

// Obtener el id de la camabaja de la URL
$id_camabaja = $_GET['id_camabaja'];

// Obtener los datos de la camabaja desde la base de datos
$sql = "SELECT * FROM camabaja WHERE id_camabaja = $id_camabaja";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $camabaja = $result->fetch_assoc();
} else {
    echo "<p class='text-center'>Cama Baja no encontrado.</p>";
    exit;
}

// Verificar si el formulario fue enviado para actualizar los datos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $marca_camabaja = $_POST['marca_camabaja'];
    $patente_camabaja = $_POST['patente_camabaja'];
    $modelo_camabaja = $_POST['modelo_camabaja'];
    $ano_camabaja = $_POST['ano_camabaja'];


    // Actualizar los datos del camion
    $update_sql = "UPDATE camabaja SET 
        marca_camabaja = '$marca_camabaja',
        patente_camabaja = '$patente_camabaja',
        modelo_camabaja = '$modelo_camabaja',
        ano_camabaja = '$ano_camabaja'

        WHERE id_camabaja = $id_camabaja";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "<script>alert('Cama Baja actualizado correctamente.'); window.location.href='camabaja.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<div class="container my-5">
    <h2 class="text-center">Editar Cama Baja</h2>
    <form action="" method="POST" class="mt-4">

        <div class="form-floating row g-3">
            <div class="col-md">
                <div class="form-floating">
                    <input type="text" class="form-control" name="patente_camabaja" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($camabaja['patente_camabaja']); ?>" required>
                    <label for="patente_camabaja">Patente</label>

                </div>
            </div>
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="marca_camabaja" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($camabaja['marca_camabaja']); ?>"required>
                    <label for="marca_camabaja">Marca</label>
                </div>
            </div>
            <div class="col-md">
                <div class="form-floating">
                    <input type="text" class="form-control" name="modelo_camabaja" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($camabaja['modelo_camabaja']); ?>" required>
                    <label for="modelo_camabaja">Modelo</label>

                </div>
            </div>
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="ano_camabaja" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($camabaja['ano_camabaja']); ?>"required>
                    <label for="ano_camabaja">Año</label>
                </div>
            </div>
        </div>

        <br>
        <div class="text-center">
            <button type="submit" class="btn btn-primary">Actualizar Cama Baja</button>
            <a href="camabaja.php" class="btn btn-outline-secondary d-inline-flex align-items-center">Volver</a>
        </div>
    </form>
</div>

<?php include 'content/footer.php'; ?>
