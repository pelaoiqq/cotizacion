<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<?php include 'content/header.php'; ?>
<div class="container my-5">
    <h2 class="text-center mb-4">Generar Orden de Compra</h2>

    <?php 
    include 'content/connect.php';

    // Obtener los clientes de la base de datos
    $sqlProveedores = "SELECT id_proveedor, nombre_proveedor, correo_proveedor FROM proveedor";
    $resultProveedores = $conn->query($sqlProveedores);

   /* // Obtener los servicios de la base de datos filtrados por área_servicio = "Servicio"
    $sqlServicios = "SELECT id_servicio, nombre_servicio, valor_servicio FROM servicios WHERE area_servicio = 'Servicios'";
    $resultServicios = $conn->query($sqlServicios);*/
    ?>

<form id="cotizacionForm" method="POST" action="guardar_oc.php" class="needs-validation" novalidate>
    <!-- Campo para seleccionar cliente -->
    <div class="form-floating row g-3">
        <div class="col-md">
            <div class="form-floating">
                <select class="form-select" name="id_proveedor" id="proveedor_id" required>
                    <option value="">Seleccione un Proveedor</option>
                    <?php
                    if ($resultProveedores->num_rows > 0) {
                        while ($proveedor = $resultProveedores->fetch_assoc()) {
                            echo "<option value='" . $proveedor['id_proveedor'] . "' data-email='" . $proveedor['correo_proveedor'] . "'>" . $proveedor['nombre_proveedor'] . "</option>";
                        }
                    } else {
                        echo "<option value=''>No hay proveedores disponibles</option>";
                    }
                    ?>
                </select>
                <label for="proveedor_id">Nombre del Proveedor</label>
            </div>
        </div>

        <div class="col-md">
            <div class="form-floating">
                <input type="email" class="form-control" name="correo_proveedor" id="proveedor_email" placeholder="name@example.com" required>
                <label for="proveedor_email">Correo Electrónico</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="cotizacion" name="cotizacion" placeholder="Cotización" require>
                <label for="cotizacion" class="form-label">Cotización Nº</label>
            </div>
        </div>        
    </div>

        <!-- Área para agregar múltiples servicios -->
        <h5 class="mt-4">Agregar Items</h5>
        <div class="form-floating row g-3">
            <div class="col-md">
                <div class="form-floating mb-3">
                    <input type="number" class="form-control" id="cantidad" placeholder="Cantidad">
                    <label for="cantidad">Cantidad</label>
                </div>
            </div>

            <div class="col-md">
                <div class="form-floating mb-3">
                    <textarea name="descripciones[]" id="descripcion" placeholder="Descripción" class="form-control" rows="3" style="text-transform: uppercase;"></textarea>
                    <label for="descripcion">Descripción</label> 
                </div>
            </div>

            <div class="col-md">
                <div class="form-floating mb-3">
                    <input type="number" class="form-control" id="precio" placeholder="precio">
                    <label for="precio">Precio</label>
                </div>
            </div>
            <div class="col-md">
                <button type="button" id="addServiceBtn" class="btn btn-success">Agregar</button>
            </div>
        </div>

        <div class="mt-4">
            <h5>Items Agregados</h5>
            <ul class="list-group" id="serviceList">
                <li class="list-group-item text-muted">No se han agregado servicios aún.</li>
            </ul>
        </div>
        <br>
    
                        <!-- formas de pago -->
            <h5>Condiciones de Pago</h5>
            <!--<div class="form-floating row g-3">-->
            <div class="col-md">
                <div class="form-floating">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="forma_pago" id="contado" value="Contado" checked required>
                        <label class="form-check-label" for="contado">Contado</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="forma_pago" id="30_dias" value="Crédito">
                        <label class="form-check-label" for="30_dias">Crédito</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botón para enviar el formulario -->
        <div class="text-center">
            <button type="submit" class="btn btn-primary mt-4">Guardar Orden de Compra</button>
        </div>
    </form>
</div>
<br><br>
<?php include 'content/footer.php'; ?>
<?php $conn->close(); ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const addServiceBtn = document.getElementById('addServiceBtn');
        const serviceList = document.getElementById('serviceList');
        const clienteSelect = document.getElementById('proveedor_id');
        const clienteEmailInput = document.getElementById('proveedor_email');

        clienteSelect.addEventListener('change', () => {
            const selectedOption = clienteSelect.options[clienteSelect.selectedIndex];
            const email = selectedOption.getAttribute('data-email') || '';
            clienteEmailInput.value = email;
        });

        addServiceBtn.addEventListener('click', () => {
            const cantidad = document.getElementById('cantidad').value;
            const servicio = document.getElementById('descripcion').value;
            const precio = document.getElementById('precio').value;

            if (!cantidad || !servicio || !precio) {
                alert('Por favor complete todos los campos antes de agregar un servicio.');
                return;
            }

            const li = document.createElement('li');
            li.classList.add('list-group-item');
            li.innerHTML = `
                ${cantidad} x ${servicio} - $${precio}
                <input type="hidden" name="cantidades[]" value="${cantidad}">
                <input type="hidden" name="servicios[]" value="${servicio}">
                <input type="hidden" name="precios[]" value="${precio}">
                <button type="button" class="btn btn-danger btn-sm float-end remove-service">Eliminar</button>
            `;

            if (serviceList.children[0]?.classList.contains('text-muted')) {
                serviceList.innerHTML = '';
            }

            serviceList.appendChild(li);

            li.querySelector('.remove-service').addEventListener('click', () => {
                serviceList.removeChild(li);
                if (serviceList.children.length === 0) {
                    serviceList.innerHTML = '<li class="list-group-item text-muted">No se han agregado servicios aún.</li>';
                }
            });

            document.getElementById('cantidad').value = '';
            document.getElementById('descripcion').value = '';
            document.getElementById('precio').value = '';
        });


        
    });
</script>
