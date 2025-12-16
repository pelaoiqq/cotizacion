<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<?php include 'content/header.php'; ?>
<div class="container my-5">
    <h2 class="text-center">Gestión de Ramplas</h2>
    
    <!-- Formulario para agregar Ramplas -->
    <form action="guardar_ramplas.php" method="POST" class="mt-4">
        
            <h5 class="mt-4">Datos de la Rampla</h5>
            <div class="form-floating row g-3">
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="patente_rampla" id="floatingInput" placeholder="ABCD67">
                        <label for="patente_rampla">Patente</label>

                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="marca_rampla" id="floatingInput" placeholder="marca rampla">
                        <label for="marca_rampla">Marca Rampla</label>
                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="modelo_rampla" id="floatingInput" placeholder="marca rampla">
                        <label for="modelo_rampla">Modelo Rampla</label>
                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="ano_rampla" id="floatingInput" placeholder="marca rampla">
                        <label for="ano_rampla">Año Rampla</label>
                    </div>
                </div>
            </div>

            <br>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Guardar Rampla</button>
                </div>
    </form>

    <!-- Listado de ramplas -->
    <h3 class="text-center mt-5">Listado de Ramplas</h3>
    <?php
    include 'content/connect.php';
    $sql = "SELECT * FROM rampla";
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
        echo '<th>Acción</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['id_rampla'] . '</td>';
            echo '<td>' . $row['patente_rampla'] . '</td>';
            echo '<td>' . $row['marca_rampla'] . '</td>';
            echo '<td>' . $row['modelo_rampla'] . '</td>';
            echo '<td>' . $row['ano_rampla'] . '</td>';

            // Botón de editar 
            echo '<td><a href="editar_ramplas.php?id_rampla=' . $row['id_rampla'] . '" class="btn btn-warning">Editar</a>
            <button class="btn btn-danger eliminar-btn" data-id="' . htmlspecialchars($row['id_rampla']) . '" data-patente="' . htmlspecialchars($row['patente_rampla']) . '" data-bs-toggle="modal" data-bs-target="#confirmarEliminar">Eliminar</button>';

            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p class="text-center mt-4">No hay ramplas registradas.</p>';
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
        <form action="eliminar_rampla.php" method="POST" id="eliminarForm">
          <input type="hidden" name="id_rampla" id="idRamplaEliminar">
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
            const idRampla = this.getAttribute("data-id");
            const patenteRampla = this.getAttribute("data-patente");
            document.getElementById("idRamplaEliminar").value = idRampla;
            document.getElementById("texto-confirmacion").innerText = `¿Estás seguro de que deseas eliminar el camión con patente ${patenteRampla}?`;
        });
    });
});
</script>
<?php include 'content/footer.php'; ?>
