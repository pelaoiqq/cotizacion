<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<?php include 'content/header.php'; ?>
<div class="container my-5">
    <h2 class="text-center mb-4">Editar Orden de Compra</h2>

    <?php 
        include 'content/connect.php';

        // Obtener la orden_id desde el parámetro GET
        if (!isset($_GET['id_oc'])) {
            die("Orden de compra no especificada.");
        }
        $orden_id = $_GET['id_oc'];

        // Obtener detalles de la orden de compra
        $queryOrden = "SELECT * FROM oc WHERE id_oc = ?";
        $stmt = $conn->prepare($queryOrden);
        $stmt->bind_param("i", $orden_id);
        $stmt->execute();
        $resultOrden = $stmt->get_result();
        $orden = $resultOrden->fetch_assoc();

        // Obtener los ítems asociados a la orden
        $queryDetalles = "SELECT * FROM oc_detalle WHERE orden_id = ?";
        $stmtDetalles = $conn->prepare($queryDetalles);
        $stmtDetalles->bind_param("i", $orden_id);
        $stmtDetalles->execute();
        $resultDetalles = $stmtDetalles->get_result();

        // Obtener el nombre del proveedor
        $sqlProveedor = "SELECT nombre_proveedor FROM proveedor WHERE id_proveedor = " . $orden['id_proveedor'];
        $resultProveedor = $conn->query($sqlProveedor);
        $proveedor = $resultProveedor->fetch_assoc();

        // Obtener forma de pago actual
        $forma_pago_actual = $orden['forma_pago'];
    ?>

    <div class="container my-5">
        <form id="editarOCForm" action="guardar_edicion_oc.php" method="POST">
            <input type="hidden" name="orden_id" value="<?php echo $orden_id; ?>">

            <div class="form-floating row g-3">
                <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="proveedor_nombre" value="<?php echo $proveedor['nombre_proveedor']; ?>" readonly>
                        <label for="proveedor_nombre">Nombre del Proveedor</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <h5>Editar Ítems Existentes</h5>
                <ul class="list-group" id="editableServiceList">
                    <?php
                    if ($resultDetalles->num_rows > 0) {
                        while ($detalle = $resultDetalles->fetch_assoc()) {
                            echo "
                            <li class='list-group-item'>
                                <div class='row g-2 align-items-center'>
                                    <div class='col-md'>
                                        <div class='form-floating'>
                                            <input type='number' class='form-control' name='cantidades_existentes[]' value='{$detalle['cantidad']}' required>
                                            <label>Cantidad</label>
                                        </div>
                                    </div>
                                    <div class='col-md'>
                                        <div class='form-floating'>
                                            <textarea name='descripciones_existentes[]' class='form-control' required>{$detalle['descripcion']}</textarea>
                                            <label>Descripción</label>
                                        </div>
                                    </div>
                                    <div class='col-md'>
                                        <div class='form-floating'>
                                            <input type='number' class='form-control' name='precios_existentes[]' value='{$detalle['precio']}' required>
                                            <label>Precio</label>
                                        </div>
                                    </div>
                                    <input type='hidden' name='ids_existentes[]' value='{$detalle['id_oc_detalle']}'>
                                    <div class='col-md-auto'>
                                        <button type='button' class='btn btn-danger btn-sm remove-item-btn' data-id='{$detalle['id_oc_detalle']}'>Eliminar</button>
                                    </div>
                                </div>
                            </li>
                            ";
                        }
                    } else {
                        echo "<li class='list-group-item text-muted'>No se han agregado servicios aún.</li>";
                    }
                    ?>
                </ul>
            </div>

            <div class="mt-4">
                <h5>Agregar Nuevos Ítems</h5>
                <ul class="list-group" id="newServiceList"></ul>
                <button type="button" id="addServiceBtn" class="btn btn-primary mt-2">Agregar Ítem</button>
            </div>
            <br><br>

            <!-- Forma de pago  -->
            <h5>Condiciones de Pago</h5>
            <div class="col-md">
                <div class="form-floating">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="forma_pago" id="contado" value="Contado" 
                            <?php echo ($forma_pago_actual == 'Contado') ? 'checked' : ''; ?> required>
                        <label class="form-check-label" for="contado">Contado</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="forma_pago" id="30_dias" value="Crédito"
                            <?php echo ($forma_pago_actual == 'Crédito') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="30_dias">Crédito</label>
                    </div>
                </div>
            </div>

            <input type="hidden" name="eliminar_ids" id="eliminar_ids">
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="listado_oc.php" class="btn btn-outline-secondary d-inline-flex align-items-center">Volver</a>
            </div>
        </form>
    </div>


    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const newServiceList = document.getElementById('newServiceList');
        const addServiceBtn = document.getElementById('addServiceBtn');
        const eliminarIdsInput = document.getElementById('eliminar_ids');

        // Agregar nuevos ítems dinámicamente
        addServiceBtn.addEventListener('click', () => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.innerHTML = `
                <div class='row g-2 align-items-center'>
                    <div class='col-md'>
                        <div class='form-floating'>
                            <input type='number' class='form-control' name='cantidades_nuevas[]' required>
                            <label>Cantidad</label>
                        </div>
                    </div>
                    <div class='col-md'>
                        <div class='form-floating'>
                            <textarea name='descripciones_nuevas[]' class='form-control' required></textarea>
                            <label>Descripción</label>
                        </div>
                    </div>
                    <div class='col-md'>
                        <div class='form-floating'>
                            <input type='number' class='form-control' name='precios_nuevos[]' required>
                            <label>Precio</label>
                        </div>
                    </div>
                    <div class='col-md-auto'>
                        <button type='button' class='btn btn-danger btn-sm remove-new-item-btn'>Eliminar</button>
                    </div>
                </div>`;
            newServiceList.appendChild(li);

            li.querySelector('.remove-new-item-btn').addEventListener('click', () => {
                li.remove();
            });
        });

        // 🛠️ Manejo de eliminación de ítems existentes
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;

                // Confirmación opcional
                if (!confirm('¿Deseas eliminar este ítem de forma permanente?')) return;

                // Ocultar el ítem del DOM
                const li = btn.closest('li');
                li.remove();

                // Agregar el ID al campo oculto
                const currentIds = eliminarIdsInput.value ? eliminarIdsInput.value.split(',') : [];
                currentIds.push(id);
                eliminarIdsInput.value = currentIds.join(',');
            });
        });
    });
</script>

</div>

<?php include 'content/footer.php'; ?>
