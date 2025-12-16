<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/header.php';
include 'content/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_servicio = $_POST['nombre_servicio'];
    $valor_servicios = $_POST['valor_servicios'];
    $area_servicios = $_POST['area_servicios'];
    $imagen = null;

    // Validar y procesar imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imageInfo = getimagesize($_FILES['imagen']['tmp_name']);
        if ($imageInfo) {
            $mimeType = $imageInfo['mime'];
            if (in_array($mimeType, ['image/jpeg', 'image/png'])) {
                // Convertir a JPEG si es necesario
                $tempImage = imagecreatefromstring(file_get_contents($_FILES['imagen']['tmp_name']));
                if ($mimeType !== 'image/jpeg') {
                    ob_start();
                    imagejpeg($tempImage, null, 85);
                    $imagen = ob_get_clean();
                } else {
                    $imagen = file_get_contents($_FILES['imagen']['tmp_name']);
                }
                imagedestroy($tempImage);
            } else {
                echo '<div class="alert alert-danger">Formato de imagen no soportado. Solo se permiten JPEG y PNG.</div>';
                $imagen = null;
            }
        } else {
            echo '<div class="alert alert-danger">El archivo subido no es una imagen válida.</div>';
        }
    }

    // Insertar en la base de datos si hay imagen válida
    if ($imagen) {
        $sql = "INSERT INTO servicios (nombre_servicio, valor_servicios, area_servicios, imagen_servicios) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siss", $nombre_servicio, $valor_servicios, $area_servicios, $imagen);

        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Servicio agregado exitosamente.</div>';
        } else {
            echo '<div class="alert alert-danger">Error al agregar servicio: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}
?>

<div class="container my-5">
    <h2 class="text-center">Gestión de Servicios</h2>
    <form action="servicios.php" method="POST" enctype="multipart/form-data" class="my-4">
        <div class="form-floating row g-3">
            <div class="col-md">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="nombre_servicio" id="nombre_servicio" placeholder="Nombre del servicio" required>
                    <label for="nombre_servicio">Nombre del Servicio</label>
                </div>
            </div>
            <div class="col-md">
                <div class="form-floating">
                    <input type="number" class="form-control" name="valor_servicios" id="valor_servicios" placeholder="Valor del servicio" required>
                    <label for="valor_servicios">Valor del Servicio</label>
                </div>
            </div>
            <div class="col-md">
                <div class="form-floating">
                    <select class="form-select" name="area_servicios" id="area_servicios" required>
                        <option selected disabled>Seleccione un Área</option>
                        <option value="Arriendo">Arriendo</option>
                        <option value="Transporte">Transporte</option>
                        <option value="Servicios">Servicios</option>
                    </select>
                    <label for="area_servicios">Área del Servicio</label>
                </div>
            </div>
            <div class="col-md">
                <div class="form-floating">
                    <input type="file" class="form-control" name="imagen" id="imagen" accept="image/jpeg, image/png" required>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Agregar Servicio</button>
    </form>

    <h3 class="mt-5">Lista de Servicios</h3>
    <?php
    $sql = "SELECT id_servicios, nombre_servicio, valor_servicios, area_servicios, imagen_servicios FROM servicios";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        echo '<table class="table table-bordered table-striped mt-3">';
        echo '<thead class="table-primary">';
        echo '<tr>';
        echo '<th>Nombre del Servicio</th>';
        echo '<th>Valor del Servicio</th>';
        echo '<th>Área del Servicio</th>';
        echo '<th>Imagen</th>';
        echo '<th>Acción</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['nombre_servicio']) . '</td>';
            echo '<td>' . htmlspecialchars($row['valor_servicios']) . '</td>';
            echo '<td>' . htmlspecialchars($row['area_servicios']) . '</td>';
            echo '<td><img src="data:image/jpeg;base64,' . base64_encode($row['imagen_servicios']) . '" alt="Imagen" style="width:100px; height:auto;"></td>';
            echo '<td>';
            echo '<a href="editar_servicios.php?id_servicios=' . htmlspecialchars($row['id_servicios']) . '" class="btn btn-warning btn-sm">Editar</a>';
            echo '<form action="eliminar_servicio.php" method="POST" style="display:inline-block;">';
            echo '<input type="hidden" name="id_servicios" value="' . htmlspecialchars($row['id_servicios']) . '">';
            echo '<button type="submit" class="btn btn-danger btn-sm">Eliminar</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p class="text-center">No hay servicios registrados.</p>';
    }
    $conn->close();
    ?>
</div>

<?php include 'content/footer.php'; ?>
