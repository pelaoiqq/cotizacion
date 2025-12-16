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
$id_rampla = $_GET['id_rampla'];

// Obtener los datos de la rampla desde la base de datos
$sql = "SELECT * FROM rampla WHERE id_rampla = $id_rampla";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $rampla = $result->fetch_assoc();
} else {
    echo "<p class='text-center'>Rampla no encontrado.</p>";
    exit;
}

// Verificar si el formulario fue enviado para actualizar los datos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $marca_rampla = $_POST['marca_rampla']; // Nuevo campo
    $patente_rampla = $_POST['patente_rampla'];
    $modelo_rampla = $_POST['modelo_rampla'];
    $ano_rampla = $_POST['ano_rampla'];
    

    // Actualizar los datos del camion
    $update_sql = "UPDATE rampla SET 
        marca_rampla = '$marca_rampla',
        patente_rampla = '$patente_rampla',
        modelo_rampla = '$modelo_rampla',
        ano_rampla = '$ano_rampla'

        WHERE id_rampla = $id_rampla";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "<script>alert('Rampla actualizado correctamente.'); window.location.href='ramplas.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<div class="container my-5">
    <h2 class="text-center">Editar Rampla</h2>
    <form action="" method="POST" class="mt-4">

        <div class="form-floating row g-3">
            <div class="col-md">
                <div class="form-floating">
                    <input type="text" class="form-control" name="patente_rampla" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($rampla['patente_rampla']); ?>" required>
                    <label for="patente_rampla">Patente</label>

                </div>
            </div>
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="marca_rampla" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($rampla['marca_rampla']); ?>"required>
                    <label for="marca_rampla">Marca</label>
                </div>
            </div>
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="modelo_rampla" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($rampla['modelo_rampla']); ?>"required>
                    <label for="modelo_rampla">Modelo</label>
                </div>
            </div>
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="ano_rampla" id="floatingInput" placeholder="name@example.com" value="<?php echo htmlspecialchars($rampla['ano_rampla']); ?>"required>
                    <label for="ano_rampla">Año</label>
                </div>
            </div>
        </div>

        <br>
        <div class="text-center">
            <button type="submit" class="btn btn-primary">Actualizar Rampla</button>
            <a href="ramplas.php" class="btn btn-outline-secondary d-inline-flex align-items-center">Volver</a>
        </div>
    </form>
</div>

<?php include 'content/footer.php'; ?>
