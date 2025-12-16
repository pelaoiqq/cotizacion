<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/header.php';
include 'content/connect.php';

// Obtener el id del camión de la URL
$id_camiones = isset($_GET['id_camiones']) ? $_GET['id_camiones'] : null;

if (!$id_camiones) {
    echo "<p class='text-center text-danger'>ID de camión no válido.</p>";
    exit;
}

// Obtener los datos actuales del camión
$sql = "SELECT * FROM camiones WHERE id_camiones = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $id_camiones);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $camion = $result->fetch_assoc();
    } else {
        echo "<p class='text-center text-danger'>Camión no encontrado.</p>";
        exit;
    }
    $stmt->close();
} else {
    echo "<p class='text-center text-danger'>Error en la consulta.</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patente_camion = isset($_POST['patente_camion']) ? $_POST['patente_camion'] : '';
    $marca_camion = isset($_POST['marca_camion']) ? $_POST['marca_camion'] : '';
    $modelo_camion = isset($_POST['modelo_camion']) ? $_POST['modelo_camion'] : '';
    $ano_camion = isset($_POST['ano_camion']) ? $_POST['ano_camion'] : '';
    $imagen = $camion['img_camion']; // Mantener la imagen actual por defecto

    // Si se sube una nueva imagen, procesarla
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imageInfo = getimagesize($_FILES['imagen']['tmp_name']);
        if ($imageInfo) {
            $mimeType = $imageInfo['mime'];
            if ($mimeType == 'image/jpeg' || $mimeType == 'image/png') {
                $tempImage = imagecreatefromstring(file_get_contents($_FILES['imagen']['tmp_name']));
                ob_start();
                imagejpeg($tempImage, null, 85);
                $imagen = ob_get_clean();
                imagedestroy($tempImage);
            } else {
                echo '<div class="alert alert-danger">Formato de imagen no soportado. Solo se permiten JPEG y PNG.</div>';
                $imagen = null;
            }
        } else {
            echo '<div class="alert alert-danger">El archivo subido no es una imagen válida.</div>';
        }
    }

    if ($imagen !== null) {
        $sql = "UPDATE camiones SET patente_camion = ?, marca_camion = ?, modelo_camion = ?, ano_camion = ?, img_camion = ? WHERE id_camiones = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssssi", $patente_camion, $marca_camion, $modelo_camion, $ano_camion, $imagen, $id_camiones);
        }
    } else {
        $sql = "UPDATE camiones SET patente_camion = ?, marca_camion = ?, modelo_camion = ?, ano_camion = ? WHERE id_camiones = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssi", $patente_camion, $marca_camion, $modelo_camion, $ano_camion, $id_camiones);
        }
    }

    if (isset($stmt) && $stmt->execute()) {
        echo "<script>alert('Camión actualizado correctamente.'); window.location.href='camiones.php';</script>";
    } else {
        echo "<div class='alert alert-danger'>Error al actualizar el camión: " . $conn->error . "</div>";
    }

    if (isset($stmt)) {
        $stmt->close();
    }
}
?>


<div class="container my-5">
    <h2 class="text-center">Editar Camión</h2>
    <form action="" method="POST" enctype="multipart/form-data" class="mt-4">
        <div class="form-floating row g-3">
            <div class="col-md">
                <div class="form-floating">
                    <input type="text" class="form-control" name="patente_camion" value="<?php echo htmlspecialchars($camion['patente_camion']); ?>" required>
                    <label for="patente_camion">Patente Camión</label>
                </div>
            </div>
            <div class="col-md">
                <div class="form-floating">
                    <input type="text" class="form-control" name="marca_camion" value="<?php echo htmlspecialchars($camion['marca_camion']); ?>" required>
                    <label for="marca_camion">Marca Camión</label>
                </div>
            </div>
            <div class="col-md">
                <div class="form-floating">
                    <input type="text" class="form-control" name="modelo_camion" value="<?php echo htmlspecialchars($camion['modelo_camion']); ?>" required>
                    <label for="modelo_camion">Modelo Camión</label>
                </div>
            </div>
            <div class="col-md">
                <div class="form-floating">
                    <input type="text" class="form-control" name="ano_camion" value="<?php echo htmlspecialchars($camion['ano_camion']); ?>" required>
                    <label for="ano_camion">Año Camión</label>
                </div>
            </div>
            <div class="col-md">
                <label for="imagen" class="form-label">Imagen Actual</label>
                <div>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($camion['img_camion']); ?>" alt="Imagen Camión" style="width:150px; height:auto;">
                </div>
                <label for="imagen" class="form-label mt-2">Subir Nueva Imagen (Opcional)</label>
                <input type="file" class="form-control" name="imagen" accept="image/jpeg, image/png">
            </div>
        </div>
        <br>
        <div class="text-center">
            <button type="submit" class="btn btn-primary">Actualizar Camión</button>
            <a href="camiones.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include 'content/footer.php'; ?>
