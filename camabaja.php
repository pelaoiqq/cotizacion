<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<?php include 'content/header.php'; ?>
<div class="container my-5">
    <h2 class="text-center">Gestión de Camas Bajas</h2>
    
    <!-- Formulario para agregar camabajas -->
    <form action="guardar_camabaja.php" method="POST" class="mt-4">
        
            <h5 class="mt-4">Datos de la Cama Baja</h5>
            <div class="form-floating row g-3">
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="patente_camabaja" id="floatingInput" placeholder="ABCD67">
                        <label for="patente_camabaja">Patente</label>
                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="marca_camabaja" id="floatingInput" placeholder="marca cama baja">
                    <label for="marca_camabaja">Marca</label>
                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="modelo_camabaja" id="floatingInput" placeholder="marca cama baja">
                        <label for="modelo_camabaja">Modelo</label>
                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" name="ano_camabaja" id="floatingInput" placeholder="2000">
                        <label for="ano_camabaja">Año</label>
                    </div>
                </div>
            </div>

            <br>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Guardar Cama Baja</button>
                </div>
    </form>

    <!-- Listado de Camas Bajas -->
    <h3 class="text-center mt-5">Listado de Camas Bajas</h3>
    <?php
    include 'content/connect.php';
    $sql = "SELECT * FROM camabaja";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo '<table class="table table-bordered table-striped mt-3">'; 
        echo '<thead class="table-primary">';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Patente</th>';
        echo '<th>Marca</th>'; 
        echo '<th>Modelo</th>'; 
        echo '<th>Año</th>'; 
        echo '<th>Acción</th>'; // Columna para el botón de editar
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['id_camabaja'] . '</td>';
            echo '<td>' . $row['patente_camabaja'] . '</td>';
            echo '<td>' . $row['marca_camabaja'] . '</td>';
            echo '<td>' . $row['modelo_camabaja'] . '</td>';
            echo '<td>' . $row['ano_camabaja'] . '</td>';


            // Botón de editar 
            echo '<td><a href="editar_camabaja.php?id_camabaja=' . $row['id_camabaja'] . '" class="btn btn-warning">Editar</a>
            <button class="btn btn-danger eliminar-btn" data-id="' . htmlspecialchars($row['id_camabaja']) . '" data-patente="' . htmlspecialchars($row['patente_camion']) . '" data-bs-toggle="modal" data-bs-target="#confirmarEliminar">Eliminar</button>';

            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p class="text-center mt-4">No hay camas bajas registradas.</p>';
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
        <form action="eliminar_camabaja.php" method="POST" id="eliminarForm">
          <input type="hidden" name="id_camabaja" id="idCamabajaEliminar">
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
            const idCamabaja = this.getAttribute("data-id");
            const patenteCamabaja = this.getAttribute("data-patente");
            document.getElementById("idCamabajaEliminar").value = idCamabaja;
            document.getElementById("texto-confirmacion").innerText = `¿Estás seguro de que deseas eliminar el camión con patente ${patenteCamabaja}?`;
        });
    });
});
</script>

<?php include 'content/footer.php'; ?>
