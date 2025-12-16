<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
include 'content/header.php';
include 'content/connect.php';

// Obtener los clientes de la base de datos
$sqlClientes = "SELECT id_cliente, nombre_cliente, email_cliente FROM clientes ORDER BY nombre_cliente ASC";
$resultClientes = $conn->query($sqlClientes);
?>

<div class="container my-5">
    <h2 class="text-center mb-4">Generar Cotización de Ventas</h2>

    <form id="cotizacionForm" method="POST" action="guardar_cotizacion_ventas.php" class="needs-validation" novalidate>
        
        <!-- SECCIÓN CLIENTE -->
        <div class="form-floating row g-3">
            <div class="col-md">
                <div class="form-floating">
                    <select class="form-select" name="id_cliente" id="cliente_id" required>
                        <option value="">Seleccione un cliente</option>
                        <?php
                        if ($resultClientes && $resultClientes->num_rows > 0) {
                            while ($cliente = $resultClientes->fetch_assoc()) {
                                // Sanitizamos la salida para evitar XSS
                                $nombre = htmlspecialchars($cliente['nombre_cliente']);
                                $email = htmlspecialchars($cliente['email_cliente']);
                                echo "<option value='{$cliente['id_cliente']}' data-email='{$email}'>{$nombre}</option>";
                            }
                        } else {
                            echo "<option value=''>No hay clientes disponibles</option>";
                        }
                        ?>
                    </select>
                    <label for="cliente_id">Nombre del Cliente</label>
                </div>
            </div>

            <!-- Campos ocultos necesarios -->
            <input type="hidden" name="tipo_cotizacion" value="Ventas">
            <input type="hidden" name="estado" value="Pendiente">
            <input type="hidden" name="servicio_list" value="Otros">

            <div class="col-md">
                <div class="form-floating">
                    <input type="email" class="form-control" name="email_cliente" id="cliente_email" placeholder="name@example.com" readonly required>
                    <label for="cliente_email">Correo Electrónico</label>
                </div>
            </div>
        </div>

        <!-- DESCRIPCIÓN GENERAL -->
        <div class="row">
            <div class="col-md-12">
                <h5 class="mt-4">Descripción de la Reparación y/o Servicio</h5>
                <div class="mb-3">
                    <textarea name="descripcion_servicio" id="descripcion_servicio" class="form-control" rows="3" style="text-transform: uppercase;"></textarea>
                </div>
            </div>
        </div>

        <!-- AGREGAR SERVICIOS -->
        <h5 class="mt-4">Agregar Servicios / Productos</h5>
        <div class="row g-3 align-items-center">
            <div class="col-md-2">
                <div class="form-floating mb-3">
                    <input type="number" class="form-control" id="cantidad" placeholder="Cantidad" min="1">
                    <label for="cantidad">Cantidad</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="servicio_manual" placeholder="Servicio" style="text-transform: uppercase;">
                    <label for="servicio_manual">Descripción del Item</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating mb-3">
                    <input type="number" class="form-control" id="precio" placeholder="Precio" min="0">
                    <label for="precio">Precio Unitario</label>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <button type="button" id="addServiceBtn" class="btn btn-success w-100" style="height: 58px;">Agregar</button>
            </div>
        </div>

        <!-- LISTA DE SERVICIOS -->
        <div class="col-md-12">
            <h5 class="mt-2">Ítems Agregados</h5>
            <ul class="list-group mb-3" id="serviceList">
                <li class="list-group-item text-muted text-center">No se han agregado ítems aún.</li>
            </ul>
        </div>

        <!-- DATOS DEL SERVICIO (FECHAS Y RUTAS) -->
        <h5 class="mt-4">Datos del Servicio</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-floating">
                    <input type="text" class="form-control" name="origen" id="origen" placeholder="Origen" required style="text-transform: uppercase;">
                    <label for="origen">Origen</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating">
                    <input type="text" class="form-control" name="destino" id="destino" placeholder="Destino" required style="text-transform: uppercase;">
                    <label for="destino">Destino</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating">
                    <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" required>
                    <label for="fecha_inicio">Inicio</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating">
                    <input type="date" class="form-control" name="fecha_termino" id="fecha_termino" required>
                    <label for="fecha_termino">Fin</label>
                </div>
            </div>
            
            <!-- FORMAS DE PAGO -->
            <div class="col-md-6">
                <div class="card p-2">
                    <label class="form-label fw-bold ms-2">Forma de Pago:</label>
                    <div class="d-flex gap-4 ms-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="forma_pago" id="contado" value="Contado" checked required>
                            <label class="form-check-label" for="contado">Contado</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="forma_pago" id="credito" value="Crédito">
                            <label class="form-check-label" for="credito">Crédito</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOTÓN GUARDAR -->
        <div class="text-center mt-5 mb-5">
            <button type="submit" class="btn btn-primary btn-lg">Guardar Cotización</button>
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
        const fechaInicio = document.getElementById("fecha_inicio");
        const fechaTermino = document.getElementById("fecha_termino");

        // Autocompletar email
        clienteSelect.addEventListener('change', () => {
            const selectedOption = clienteSelect.options[clienteSelect.selectedIndex];
            clienteEmailInput.value = selectedOption.getAttribute('data-email') || '';
        });

        // Validar fechas
        fechaInicio.addEventListener("change", () => {
            fechaTermino.min = fechaInicio.value;
        });

        fechaTermino.addEventListener("change", () => {
            if (fechaInicio.value && fechaTermino.value < fechaInicio.value) {
                alert("La fecha de término no puede ser menor que la fecha de inicio.");
                fechaTermino.value = "";
            }
        });

        // Agregar Servicio
        addServiceBtn.addEventListener('click', () => {
            const cantidad = document.getElementById('cantidad').value;
            const servicio = document.getElementById('servicio_manual').value.toUpperCase(); // Convertir a mayúsculas
            const precio = document.getElementById('precio').value;

            if (!cantidad || !servicio || !precio) {
                alert('Por favor complete cantidad, descripción y precio.');
                return;
            }

            const li = document.createElement('li');
            li.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center');

            const total_servicio = (parseFloat(cantidad) * parseFloat(precio)).toFixed(0); // Sin decimales si es CLP

            li.innerHTML = `
                <div>
                    <strong>${cantidad}</strong> x ${servicio} 
                    <span class="badge bg-secondary ms-2">$${precio}</span>
                </div>
                <div>
                    <span class="fw-bold me-3">Total: $${total_servicio}</span>
                    
                    <input type="hidden" name="cantidades[]" value="${cantidad}">
                    <input type="hidden" name="servicios[]" value="${servicio}">
                    <input type="hidden" name="valores[]" value="${precio}">
                    <input type="hidden" name="total_servicios[]" value="${total_servicio}">
                    
                    <button type="button" class="btn btn-danger btn-sm remove-service">X</button>
                </div>
            `;

            // Limpiar mensaje de "vacío" si existe
            if (serviceList.children[0]?.classList.contains('text-muted')) {
                serviceList.innerHTML = '';
            }

            serviceList.appendChild(li);

            // Limpiar campos
            document.getElementById('cantidad').value = '';
            document.getElementById('servicio_manual').value = '';
            document.getElementById('precio').value = '';
            document.getElementById('cantidad').focus(); // Volver foco a cantidad
        });

        // Eliminar Servicio (Delegación de eventos para mejor rendimiento)
        serviceList.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-service')) {
                e.target.closest('li').remove();
                if (serviceList.children.length === 0) {
                    serviceList.innerHTML = '<li class="list-group-item text-muted text-center">No se han agregado ítems aún.</li>';
                }
            }
        });
    });
</script>