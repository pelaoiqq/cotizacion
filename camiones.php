<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/header.php';
include 'content/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patente_camion = $_POST['patente_camion'];
    $marca_camion = $_POST['marca_camion'];
    $modelo_camion = $_POST['modelo_camion'];
    $ano_camion = $_POST['ano_camion'];
    $imagen = null;

    // Validar y procesar imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imageInfo = getimagesize($_FILES['imagen']['tmp_name']);
        
        if ($imageInfo) {
            $mimeType = $imageInfo['mime'];
            
            // Verificar formato permitido
            if (in_array($mimeType, ['image/jpeg', 'image/png'])) {
                
                // 1. Cargar la imagen original
                $sourceImage = imagecreatefromstring(file_get_contents($_FILES['imagen']['tmp_name']));
                
                // Obtener dimensiones originales
                $width_orig = imagesx($sourceImage);
                $height_orig = imagesy($sourceImage);
                
                // 2. Definir dimensiones del lienzo final
                $canvas_w = 600;
                $canvas_h = 342;
                
                // 3. Crear lienzo de 600x342
                $destinationImage = imagecreatetruecolor($canvas_w, $canvas_h);
                
                // --- CAMBIO: FONDO BLANCO ---
                $white = imagecolorallocate($destinationImage, 255, 255, 255); // RGB para Blanco
                imagefill($destinationImage, 0, 0, $white); // Rellenar el fondo con blanco

                // --- LÓGICA DE AJUSTE POR ALTO ---
                
                // Queremos que el alto sea siempre 342. Calculamos el nuevo ancho proporcional.
                $new_height = $canvas_h; // 342
                $ratio = $width_orig / $height_orig;
                $new_width = $new_height * $ratio; // Ancho proporcional

                // Calcular posición X para centrar la imagen
                $dst_x = ($canvas_w - $new_width) / 2;
                $dst_y = 0; // El alto encaja exacto

                // 4. Redimensionar y copiar sobre el lienzo
                imagecopyresampled(
                    $destinationImage, 
                    $sourceImage, 
                    intval($dst_x), intval($dst_y), // Destino X, Y (Centrado)
                    0, 0,                           // Origen X, Y
                    intval($new_width), intval($new_height), // Nuevo tamaño
                    $width_orig, $height_orig       // Tamaño original
                );

                // 5. Guardar
                ob_start();
                imagejpeg($destinationImage, null, 90);
                $imagen = ob_get_clean();

                // Liberar memoria
                imagedestroy($sourceImage);
                imagedestroy($destinationImage);

            } else {
                echo '<div class="alert alert-danger">Formato no soportado. Solo JPEG y PNG.</div>';
                $imagen = null;
            }
        } else {
            echo '<div class="alert alert-danger">Archivo no válido.</div>';
        }
    }

    // Insertar en BD
    if ($imagen) {
        $sql = "INSERT INTO camiones (patente_camion, marca_camion, modelo_camion, ano_camion, img_camion) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssis", $patente_camion, $marca_camion, $modelo_camion, $ano_camion, $imagen);

        if ($stmt->execute()) {
            echo '<div class="alert alert-success">Camión agregado exitosamente.</div>';
        } else {
            echo '<div class="alert alert-danger">Error al agregar: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}
?>

<div class="container my-5">
    <h2 class="text-center">Gestión de Camiones</h2>
    <form action="camiones.php" method="POST" enctype="multipart/form-data" class="my-4">
        <div class="form-floating row g-3">
            <div class="col-md">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="patente_camion" id="patente_camion" placeholder="Patente" required>
                    <label for="patente_camion">Patente Nº</label>
                    </div>
            </div>
            <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="marca_camion" id="marca_camion" placeholder="marca camion">
                        <label for="marca_camion">Marca Camión</label>
                    </div>
                </div>
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="modelo_camion" id="modelo_camion" placeholder="Modelo">
                    <label for="modelo_camion">Modelo Camión</label>
                </div>
            </div>     
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="ano_camion" id="ano_camion" placeholder="Año">
                    <label for="ano_camion">Año Camión</label>
                </div>
            </div>  
        </div>  
        <div class="form-floating row g-3">  
            <div class="col-md">
                <div class="form-floating">
                    <input type="file" class="form-control" name="imagen" id="imagen" accept="image/jpeg, image/png" required>
                    <label for="imagen" class="form-label">Imagen del Camión (Se ajustará al alto automáticamente)</label>
                </div>
            </div>
        </div>
        <br>
        <div class="text-center">
            <button type="submit" class="btn btn-primary">Guardar Camión</button>
        </div>
    </form>

    <h3 class="text-center mt-5">Listado de Camiones</h3>
    <?php
    $sql = "SELECT id_camiones, patente_camion, marca_camion, modelo_camion, ano_camion, img_camion FROM camiones";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        echo '<table class="table table-bordered table-striped mt-3">';
        echo '<thead class="table-primary">';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Patente</th>';
        echo '<th>Marca Camión</th>';
        echo '<th>Modelo Camión</th>';
        echo '<th>Año Camión</th>';
        echo '<th>Imagen</th>';
        echo '<th>Acción</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['id_camiones']) . '</td>';
            echo '<td>' . htmlspecialchars($row['patente_camion']) . '</td>';
            echo '<td>' . htmlspecialchars($row['marca_camion']) . '</td>';
            echo '<td>' . htmlspecialchars($row['modelo_camion']) . '</td>';
            echo '<td>' . htmlspecialchars($row['ano_camion']) . '</td>';
            // Mostrar imagen (Thumbnail pequeño) con borde para ver el fondo blanco
            echo '<td><img src="data:image/jpeg;base64,' . base64_encode($row['img_camion']) . '" alt="Imagen" style="width:120px; height:auto; border:1px solid #ccc;"></td>';
            echo '<td>';
            echo '<a href="editar_camiones.php?id_camiones=' . htmlspecialchars($row['id_camiones']) . '" class="btn btn-warning btn-sm">Editar</a>';
            echo ' ';
            echo '<button class="btn btn-danger btn-sm eliminar-btn" data-id="' . htmlspecialchars($row['id_camiones']) . '" data-patente="' . htmlspecialchars($row['patente_camion']) . '" data-bs-toggle="modal" data-bs-target="#confirmarEliminar">Eliminar</button>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p class="text-center">No hay camiones registrados.</p>';
    }
    $conn->close();
    ?>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmarEliminar" tabindex="-1" aria-labelledby="confirmarEliminarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmarEliminarLabel">Confirmar Eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="texto-confirmacion"></p>
      </div>
      <div class="modal-footer">
        <form action="eliminar_camiones.php" method="POST" id="eliminarForm">
          <input type="hidden" name="id_camiones" id="idCamionEliminar">
          <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
        </form>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancelar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const eliminarButtons = document.querySelectorAll(".eliminar-btn");
    eliminarButtons.forEach(button => {
        button.addEventListener("click", function() {
            const idCamion = this.getAttribute("data-id");
            const patenteCamion = this.getAttribute("data-patente");
            document.getElementById("idCamionEliminar").value = idCamion;
            document.getElementById("texto-confirmacion").innerText = `¿Estás seguro de que deseas eliminar el camión con patente ${patenteCamion}?`;
        });
    });
});
</script>

<?php include 'content/footer.php'; ?>