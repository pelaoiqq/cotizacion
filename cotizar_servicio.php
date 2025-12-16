<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<?php include 'content/header.php'; ?>
<div class="container my-5">
<h2 class="text-center mb-4">Generar Cotización de Reparación y Servicios</h2>

    <?php 
    include 'content/connect.php';

    // Obtener los conductores
    $sqlConductor = "SELECT id_conductor, nombre_conductor FROM conductores";
    $resultConductor = $conn->query($sqlConductor);

    // Obtener los Camiones
    $sqlCamion = "SELECT id_camiones, marca_camion, patente_camion FROM camiones";
    $resultCamion = $conn->query($sqlCamion);

    // Obtener las Ramplas
    $sqlRampla = "SELECT id_rampla, patente_rampla FROM rampla";
    $resultRampla = $conn->query($sqlRampla);

    // Obtener las Ramplas
    $sqlCamabaja = "SELECT id_camabaja, patente_camabaja FROM camabaja";
    $resultCamabaja = $conn->query($sqlCamabaja);

    // Obtener los clientes de la base de datos
    $sqlClientes = "SELECT id_cliente, nombre_cliente, email_cliente FROM clientes";
    $resultClientes = $conn->query($sqlClientes);

   /* // Obtener los servicios de la base de datos filtrados por área_servicio = "Servicio"
    $sqlServicios = "SELECT id_servicio, nombre_servicio, valor_servicio FROM servicios WHERE area_servicio = 'Servicios'";
    $resultServicios = $conn->query($sqlServicios);*/
    ?>

<form id="cotizacionForm" method="POST" action="guardar_cotizacion_serv.php" class="needs-validation" novalidate>
    <!-- Campo para seleccionar cliente -->
    <div class="form-floating row g-3">
        <div class="col-md">
            <div class="form-floating">
                <select class="form-select" name="id_cliente" id="cliente_id" required>
                    <option value="">Seleccione un cliente</option>
                    <?php
                    if ($resultClientes->num_rows > 0) {
                        while ($cliente = $resultClientes->fetch_assoc()) {
                            echo "<option value='" . $cliente['id_cliente'] . "' data-email='" . $cliente['email_cliente'] . "'>" . $cliente['nombre_cliente'] . "</option>";
                        }
                    } else {
                        echo "<option value=''>No hay clientes disponibles</option>";
                    }
                    ?>
                </select>
                <label for="cliente_id">Nombre del Cliente</label>
            </div>
        </div>
        <input type="hidden" name="tipo_cotizacion" value="Servicios">
        <input type="hidden" name="estado" value="Pendiente">
        <input type="hidden" name="servicio_list" value="Otros">

        <div class="col-md">
            <div class="form-floating">
                <input type="email" class="form-control" name="email_cliente" id="cliente_email" placeholder="name@example.com" required>
                <label for="cliente_email">Correo Electrónico</label>
            </div>
        </div>
    </div>

    <!-- Otros campos  -->
    <div class="row">
        <div class="col-md-6">
            <h5 class="mt-4">Descripción de la Reparación y/o Servicio</h5>
            <div class="mb-3">
                <textarea name="descripcion_servicio" id="descripcion_servicio" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>
        <!-- Área para agregar múltiples servicios -->
        <h5 class="mt-4">Agregar Servicios</h5>
        <div class="form-floating row g-3">
            <div class="col-md">
                <div class="form-floating mb-3">
                    <input type="number" class="form-control" id="cantidad" placeholder="Cantidad">
                    <label for="cantidad">Cantidad</label>
                </div>
            </div>

            <div class="col-md">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="servicio_manual" placeholder="Servicio">
                    <label for="servicio_manual">Servicio</label>
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

        <!-- Lista de servicios agregados -->
        <div class="col-md">
            <h5>Servicios Agregados</h5>
            <ul class="list-group" id="serviceList">
                <li class="list-group-item text-muted">No se han agregado servicios aún.</li>
            </ul>
        </div>
        <br>
        <h5>Datos del Servicio</h5>
        <div class="form-floating row g-3">

           <div class="col-md">
                <div class="form-floating">
                    <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" required>
                    <label for="fecha_inicio">Inicio del Servicio</label>
                </div>
            </div>

            <div class="col-md">
                <div class="form-floating">
                    <input type="date" class="form-control" name="fecha_termino" id="fecha_termino" required>
                    <label for="fecha_termino">Fin del Servicio</label>
                </div>
            </div>
            <div class="col-md">
                <div class="form-floating mb-3">
                </div>
            </div>
            <div class="col-md">
                <div class="form-floating">
                </div>
            </div>
                        <!-- formas de pago  -->
            <h5>Formas de Pago</h5>
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

            <script>
                // Obtener los elementos de las fechas
                const fechaInicio = document.getElementById("fecha_inicio");
                const fechaTermino = document.getElementById("fecha_termino");

                // Escuchar cambios en la fecha de inicio
                fechaInicio.addEventListener("change", () => {
                    // Actualizar el atributo "min" de la fecha de término
                    fechaTermino.min = fechaInicio.value;
                });

                // Escuchar cambios en la fecha de término
                fechaTermino.addEventListener("change", () => {
                    if (fechaTermino.value < fechaInicio.value) {
                        alert("La fecha de término no puede ser menor que la fecha de inicio.");
                        fechaTermino.value = ""; // Resetear el valor de la fecha de término
                    }
                });
            </script>

        </div>

        <!-- Botón para enviar el formulario -->
        <div class="text-center">
            <button type="submit" class="btn btn-primary mt-4">Guardar Cotización</button>
        </div>
    </form>
</div>

<?php include 'content/footer.php'; ?>
<?php $conn->close(); ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const addServiceBtn = document.getElementById('addServiceBtn');
        const serviceList = document.getElementById('serviceList');
        const clienteSelect = document.getElementById('cliente_id');
        const clienteEmailInput = document.getElementById('cliente_email');

        clienteSelect.addEventListener('change', () => {
            const selectedOption = clienteSelect.options[clienteSelect.selectedIndex];
            const email = selectedOption.getAttribute('data-email') || '';
            clienteEmailInput.value = email;
        });

        addServiceBtn.addEventListener('click', () => {
            const cantidad = document.getElementById('cantidad').value;
            const servicio = document.getElementById('servicio_manual').value;
            const precio = document.getElementById('precio').value;

            if (!cantidad || !servicio || !precio) {
                alert('Por favor complete todos los campos antes de agregar un servicio.');
                return;
            }

            const li = document.createElement('li');
            li.classList.add('list-group-item');

            // Cálculo del total
            const total_servicio = cantidad * precio;  // Multiplicación de cantidad por precio 

            li.innerHTML = `
                ${cantidad} x ${servicio} - $${precio} (Total: $${total_servicio})  
                <input type="hidden" name="cantidades[]" value="${cantidad}">
                <input type="hidden" name="servicios[]" value="${servicio}">
                <input type="hidden" name="valores[]" value="${precio}">  <!-- Guardar valor unitario -->
                <input type="hidden" name="total_servicios[]" value="${total_servicio}"> <!-- Guardar total calculado -->
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
            document.getElementById('servicio_manual').value = '';
            document.getElementById('precio').value = '';
        });


    });
</script>
