<?php
session_start();
if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Administrador') {
    header("Location: index.php"); 
    exit();
}
include 'content/header.php'; 
?>

<div class="container my-5">
    <h2 class="text-center">Gestión de Usuarios</h2>

    <!-- Formulario para agregar usuarios -->
    <form action="guardar_user.php" method="POST" class="mt-4">
        <h5 class="mt-4">Detalles del Nuevo Usuario</h5>
        <div class="form-floating row g-3"> 
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="user" id="user" placeholder="Usuario" required>
                    <label for="user">Usuario</label>
                </div>
            </div>
            <div class="col-md"> 
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="nombre_user" id="nombre_user" placeholder="Nombre Completo" required>
                    <label for="nombre_user">Nombre Completo</label>
                </div>
            </div>
            <div class="col-md">
                <div class="form-floating">
                    <input type="password" class="form-control" name="password" id="password" placeholder="Contraseña" required>
                    <label for="password">Contraseña</label>
                </div>
            </div>
            <div class="col-md">
                <div class="form-floating">
                    <select class="form-select" name="rol_usuario" id="rol_usuario" required>
                        <option value="Administrador">Administrador</option>
                        <option value="Editor">Editor</option>
                    </select>
                    <label for="rol_usuario">Rol</label>
                </div>
            </div>
            <!-- Nuevo campo de Estado -->
            <div class="col-md">
                <div class="form-floating">
                    <select class="form-select" name="estado" id="estado" required>
                        <option value="Activo" selected>Activo</option>
                        <option value="Suspendido">Suspendido</option>
                    </select>
                    <label for="estado">Estado Inicial</label>
                </div>
            </div>
        </div>
        <br>
        <div class="text-center">
            <button type="submit" class="btn btn-primary">Guardar Usuario</button>
        </div>
    </form>

    <br>
    <!-- Listado de usuarios -->
    <h3 class="text-center mt-5">Lista de Usuarios</h3>
    
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success text-center"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <?php
    include 'content/connect.php';
    $sql = "SELECT 
            u.id_user,
            u.nombre_user,
            u.nombre_completo,
            u.rol_usuario,
            u.estado,
            (SELECT MAX(login_time) FROM login_log WHERE id_user = u.id_user) AS ultimo_acceso
        FROM users u ORDER BY u.id_user DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo '<table class="table table-bordered table-striped mt-3 align-middle">'; 
        echo '<thead class="table-primary text-center">';
        echo '<tr><th>Nº</th><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Estado</th><th>Último Acceso</th><th>Acciones</th></tr>';
        echo '</thead><tbody>';

        $contador = 1;
        while ($row = $result->fetch_assoc()) {
            // Definir color y texto del botón según estado
            $btnClass = ($row['estado'] === 'Activo') ? 'btn-success' : 'btn-secondary';
            $btnIcon  = ($row['estado'] === 'Activo') ? 'bi-check-circle-fill' : 'bi-slash-circle';
            $nuevoEstado = ($row['estado'] === 'Activo') ? 'Suspendido' : 'Activo';
            $badgeClass = ($row['estado'] === 'Activo') ? 'bg-success' : 'bg-secondary';

            echo '<tr>';
            echo '<td class="text-center">' . $contador++ . '</td>';
            echo '<td>' . htmlspecialchars($row['nombre_user']) . '</td>';
            echo '<td>' . htmlspecialchars($row['nombre_completo']) . '</td>';
            echo '<td class="text-center">' . htmlspecialchars($row['rol_usuario']) . '</td>';
            
            // Columna Estado con Badge
            echo '<td class="text-center"><span class="badge ' . $badgeClass . '">' . $row['estado'] . '</span></td>';
            
            echo '<td class="text-center">' . ($row['ultimo_acceso'] ? date('d-m-Y H:i', strtotime($row['ultimo_acceso'])) : 'Nunca') . '</td>';
            
            echo '<td class="text-center">
                    <div class="btn-group" role="group">
                        <!-- Botón Editar -->
                        <a href="editar_user.php?id_user=' . $row['id_user'] . '" class="btn btn-warning btn-sm" title="Editar"><i class="bi bi-pencil-square"></i></a>
                        
                        <!-- Botón Cambiar Estado (Activar/Suspender) -->
                        <form action="cambiar_estado_user.php" method="POST" style="display:inline;">
                            <input type="hidden" name="id_user" value="' . $row['id_user'] . '">
                            <input type="hidden" name="nuevo_estado" value="' . $nuevoEstado . '">
                            <button type="submit" class="btn ' . $btnClass . ' btn-sm" title="' . ($row['estado'] === 'Activo' ? 'Suspender' : 'Activar') . '">
                                <i class="bi ' . $btnIcon . '"></i>
                            </button>
                        </form>

                        <!-- Botón Eliminar -->
                        <button class="btn btn-danger btn-sm eliminar-btn" data-id="' . $row['id_user'] . '" data-nombre="' . htmlspecialchars($row['nombre_completo']) . '" data-bs-toggle="modal" data-bs-target="#confirmarEliminar" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </div>
                </td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p class="text-center mt-4">No hay usuarios registrados.</p>';
    }
    $conn->close();
    ?>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="confirmarEliminar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirmar Eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="texto-confirmacion"></p>
      </div>
      <div class="modal-footer">
        <form action="eliminar_user.php" method="POST">
          <input type="hidden" name="id_user" id="idUserEliminar">
          <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
        </form>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const eliminarButtons = document.querySelectorAll(".eliminar-btn");
    eliminarButtons.forEach(button => {
        button.addEventListener("click", function() {
            const idUser = this.getAttribute("data-id");
            const nombre = this.getAttribute("data-nombre");
            document.getElementById("idUserEliminar").value = idUser;
            document.getElementById("texto-confirmacion").innerText = `¿Estás seguro de eliminar a ${nombre}?`;
        });
    });
});
</script>
<?php include 'content/footer.php'; ?>