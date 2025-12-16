<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
include 'content/header.php'; 
include 'content/connect.php';

// Consultas optimizadas (una sola vez)
$sqlCamion = "SELECT id_camiones, marca_camion, patente_camion FROM camiones ORDER BY patente_camion ASC";
$resultCamion = $conn->query($sqlCamion);

$sqlRampla = "SELECT id_rampla, patente_rampla FROM rampla ORDER BY patente_rampla ASC";
$resultRampla = $conn->query($sqlRampla);

$sqlCamabaja = "SELECT id_camabaja, patente_camabaja FROM camabaja ORDER BY patente_camabaja ASC";
$resultCamabaja = $conn->query($sqlCamabaja);

$sqlClientes = "SELECT id_cliente, nombre_cliente, email_cliente FROM clientes ORDER BY nombre_cliente ASC";
$resultClientes = $conn->query($sqlClientes);
?>

<div class="container my-5">
    <h2 class="text-center mb-4">Generar Cotización de Arriendos</h2>

    <form id="cotizacionForm" method="POST" action="guardar_cotizacion_arri.php" class="needs-validation" novalidate>
        
        <!-- SECCIÓN CLIENTE -->
        <div class="card p-4 mb-4 shadow-sm">
            <h5 class="card-title text-primary">Datos del Cliente</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" name="id_cliente" id="cliente_id" required>
                            <option value="">Seleccione un cliente</option>
                            <?php
                            if ($resultClientes && $resultClientes->num_rows > 0) {
                                while ($cliente = $resultClientes->fetch_assoc()) {
                                    $nombre = htmlspecialchars($cliente['nombre_cliente']);
                                    $email = htmlspecialchars($cliente['email_cliente']);
                                    echo "<option value='{$cliente['id_cliente']}' data-email='{$email}'>{$nombre}</option>";
                                }
                            }
                            ?>
                        </select>
                        <label for="cliente_id">Cliente</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="email" class="form-control" name="email_cliente" id="cliente_email" placeholder="Email" required readonly>
                        <label for="cliente_email">Correo Electrónico (Automático)</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN SERVICIOS -->
        <div class="card p-4 mb-4 shadow-sm">
            <h5 class="card-title text-primary">Detalle de Servicios / Ítems</h5>
            
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="tipoMoneda" required>
                            <option value="CLP">Pesos Chilenos (CLP)</option>
                            <option value="UF">Unidad de Fomento (UF)</option>
                        </select>
                        <label for="tipoMoneda">Moneda</label>
                    </div>
                </div>
                <div class="col-md-4" id="ufValueContainer" style="display:none;">
                    <div class="form-floating">
                        <!-- Step any permite decimales -->
                        <input type="number" step="0.01" class="form-control" id="valorUF" placeholder="Valor UF">                
                        <label for="valorUF">Valor UF del día (Ej: 38000.50)</label>
                    </div>
                </div>
            </div>

            <div class="row g-3 align-items-center bg-light p-3 rounded">
                <div class="col-md-2">
                    <div class="form-floating">
                        <input type="number" class="form-control" id="cantidad" placeholder="Cant" min="1">
                        <label>Cantidad</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="servicio_manual" placeholder="Descripción" style="text-transform: uppercase;">
                        <label>Descripción del Servicio</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="number" class="form-control" id="precio" placeholder="Precio" min="0" step="0.01">
                        <label>Precio Unitario</label>
                    </div>
                </div>
                <div class="col-md-1 text-center">
                    <button type="button" id="addServiceBtn" class="btn btn-success btn-lg" title="Agregar a la lista">
                        <i class="bi bi-plus-lg">+</i>
                    </button>
                </div>
            </div>

            <div class="mt-3">
                <ul class="list-group" id="serviceList">
                    <li class="list-group-item text-muted text-center">No se han agregado servicios a la lista.</li>
                </ul>
            </div>
        </div>

        <!-- SECCIÓN ASIGNACIÓN DE VEHÍCULOS -->
        <div class="card p-4 mb-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title text-primary m-0">Asignación de Recursos (Camiones)</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="omitirAsignacion">
                    <label class="form-check-label fw-bold" for="omitirAsignacion">Solo cotizar (No asignar vehículos)</label>
                </div>
            </div>

            <div id="panelAsignacion">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label for="cantidadServicios" class="col-form-label">¿Cuántos equipos utilizará?</label>
                    </div>
                    <div class="col-auto">
                        <select class="form-select" id="cantidadServicios" onchange="generarFilas()">
                            <option value="0">0</option>
                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div id="contenedorFilasServicios" class="mt-3"></div>
            </div>
            
            <div id="mensajeOmitido" class="alert alert-warning mt-3" style="display:none;">
                <i class="bi bi-info-circle"></i> Se generará una cotización general sin asignar vehículos específicos.
            </div>
        </div>

        <!-- DATOS OPERATIVOS -->
        <div class="card p-4 mb-4 shadow-sm">
            <h5 class="card-title text-primary">Datos Operativos</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="origen" id="origen" placeholder="Origen" required style="text-transform: uppercase;">
                        <label>Origen</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="destino" id="destino" placeholder="Destino" required style="text-transform: uppercase;">
                        <label>Destino</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" required>
                        <label>Inicio</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="date" class="form-control" name="fecha_termino" id="fecha_termino" required>
                        <label>Término</label>
                    </div>
                </div>
            </div>

            <h6 class="mt-4">Condiciones de Pago</h6>
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="forma_pago" id="contado" value="Contado" checked>
                <label class="btn btn-outline-secondary" for="contado">Contado</label>

                <input type="radio" class="btn-check" name="forma_pago" id="credito" value="Crédito">
                <label class="btn btn-outline-secondary" for="credito">Crédito 30 días</label>
            </div>
        </div>

        <!-- Inputs ocultos para guardar moneda y UF -->
        <input type="hidden" name="moneda" id="hiddenMoneda" value="CLP">
        <input type="hidden" name="valor_uf" id="hiddenValorUF" value="0.00">
        
        <!-- Inputs ocultos fijos -->
        <input type="hidden" name="tipo_cotizacion" value="Arriendo">
        <input type="hidden" name="estado" value="Pendiente">

        <div class="d-grid gap-2 mb-5">
            <button type="submit" class="btn btn-primary btn-lg">Generar y Guardar Cotización</button>
        </div>
    </form>
</div>

<?php include 'content/footer.php'; ?>
<?php $conn->close(); ?>

<script>
    // 1. GENERAR OPCIONES PARA SELECTS (JS embebido con PHP limpio)
    const camionOpts = '<option value="">Seleccione Camión</option>' + `<?php 
        if($resultCamion) {
            $resultCamion->data_seek(0); 
            while($r=$resultCamion->fetch_assoc()){
                echo "<option value='".$r['id_camiones']."'>".$r['patente_camion']." - ".$r['marca_camion']."</option>";
            }
        }
    ?>`;
    
    const ramplaOpts = '<option value="">Sin Rampla</option>' + `<?php 
        if($resultRampla) {
            $resultRampla->data_seek(0); 
            while($r=$resultRampla->fetch_assoc()){
                echo "<option value='".$r['id_rampla']."'>".$r['patente_rampla']."</option>";
            }
        }
    ?>`;
    
    const camaOpts = '<option value="">Sin Cama Baja</option>' + `<?php 
        if($resultCamabaja) {
            $resultCamabaja->data_seek(0); 
            while($r=$resultCamabaja->fetch_assoc()){
                echo "<option value='".$r['id_camabaja']."'>".$r['patente_camabaja']."</option>";
            }
        }
    ?>`;

    // 2. FUNCIÓN GENERAR FILAS
    function generarFilas() {
        const cantidad = parseInt(document.getElementById('cantidadServicios').value) || 0;
        const divFilas = document.getElementById('contenedorFilasServicios');
        divFilas.innerHTML = ''; 

        for (let i = 0; i < cantidad; i++) {
            const fila = document.createElement('div');
            fila.className = 'card card-body bg-light mb-2 border-0 shadow-sm';
            fila.innerHTML = `
                <h6 class="card-subtitle mb-2 text-primary">Equipo #${i + 1}</h6>
                <!-- Input oculto para conductor -->
                <input type="hidden" name="conductor_id[]" value="0"> 
                <div class="row g-2">
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" name="camion_id[]" required>${camionOpts}</select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" name="rampla_id[]">${ramplaOpts}</select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" name="camabaja_id[]">${camaOpts}</select>
                    </div>
                </div>`;
            divFilas.appendChild(fila);
        }
    }

    // 3. EVENTOS
    document.addEventListener('DOMContentLoaded', () => {
        // --- Moneda y UF ---
        const tipoMoneda = document.getElementById('tipoMoneda');
        const ufContainer = document.getElementById('ufValueContainer');
        const valorUF = document.getElementById('valorUF');
        const hiddenMoneda = document.getElementById('hiddenMoneda');
        const hiddenValorUF = document.getElementById('hiddenValorUF');

        tipoMoneda.addEventListener('change', () => {
            hiddenMoneda.value = tipoMoneda.value;
            if (tipoMoneda.value === 'UF') {
                ufValueContainer.style.display = 'block';
                valorUF.setAttribute('required', 'required');
            } else {
                ufValueContainer.style.display = 'none';
                valorUF.removeAttribute('required');
                valorUF.value = '';
                hiddenValorUF.value = '0.00';
            }
        });

        // Al cambiar el input visual de UF, actualizar el hidden
        valorUF.addEventListener('input', () => {
            // Reemplazar comas por puntos para la BD
            hiddenValorUF.value = valorUF.value.replace(',', '.');
        });

        // --- Omitir Asignación ---
        const checkOmitir = document.getElementById('omitirAsignacion');
        if(checkOmitir) {
            checkOmitir.addEventListener('change', function() {
                const panel = document.getElementById('panelAsignacion');
                const msg = document.getElementById('mensajeOmitido');
                if(this.checked) {
                    panel.style.display = 'none';
                    msg.style.display = 'block';
                    document.getElementById('cantidadServicios').value = 0;
                    document.getElementById('contenedorFilasServicios').innerHTML = '';
                } else {
                    panel.style.display = 'block';
                    msg.style.display = 'none';
                }
            });
        }

        // --- Agregar Servicio (CORREGIDO) ---
        document.getElementById('addServiceBtn').addEventListener('click', () => {
            const cant = document.getElementById('cantidad').value;
            const desc = document.getElementById('servicio_manual').value.toUpperCase();
            const prec = document.getElementById('precio').value;

            if (!cant || !desc || !prec) {
                alert('Complete todos los campos del servicio.');
                return;
            }

            const moneda = tipoMoneda.value;
            const total = (parseFloat(cant) * parseFloat(prec)).toFixed(2);
            const simbolo = moneda === 'UF' ? 'UF' : '$';

            // --- ESTA PARTE FALTABA EN TU CÓDIGO ---
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `
                <div>
                    <strong>${cant}</strong> x ${desc} 
                    <span class="badge bg-secondary ms-2">${simbolo} ${prec}</span>
                </div>
                <div>
                    <span class="fw-bold me-3">Total: ${simbolo} ${total}</span>
                    <input type="hidden" name="cantidades[]" value="${cant}">
                    <input type="hidden" name="servicios[]" value="${desc}">
                    <input type="hidden" name="valores[]" value="${prec}">
                    <input type="hidden" name="total_servicios[]" value="${total}">
                    <button type="button" class="btn btn-danger btn-sm remove-service">X</button>
                </div>`;
            // ---------------------------------------

            const list = document.getElementById('serviceList');
            if (list.children[0]?.classList.contains('text-muted')) list.innerHTML = '';
            list.appendChild(li);

            // Actualizar total de equipos abajo (función de sincronización)
            actualizarTotalEquipos();

            // Limpiar inputs
            document.getElementById('cantidad').value = '';
            document.getElementById('servicio_manual').value = '';
            document.getElementById('precio').value = '';

            // Evento borrar
            li.querySelector('.remove-service').addEventListener('click', () => {
                li.remove();
                if (list.children.length === 0) list.innerHTML = '<li class="list-group-item text-muted text-center">No se han agregado servicios a la lista.</li>';
                
                // Actualizar total al borrar
                actualizarTotalEquipos();
            });
        });

        // === FUNCIÓN PARA SINCRONIZAR ===
        function actualizarTotalEquipos() {
            const inputsCant = document.getElementsByName('cantidades[]');
            let total = 0;
            for(let input of inputsCant) {
                total += parseInt(input.value) || 0;
            }
            
            // Actualizar select de abajo
            const selectEquipos = document.getElementById('cantidadServicios');
            if(selectEquipos) {
                selectEquipos.value = total;
                // Disparar evento para que se generen las filas de camiones
                selectEquipos.dispatchEvent(new Event('change'));
            }
        }

        // --- Cliente Email ---
        const cliSelect = document.getElementById('cliente_id');
        cliSelect.addEventListener('change', () => {
            document.getElementById('cliente_email').value = cliSelect.options[cliSelect.selectedIndex].getAttribute('data-email') || '';
        });

        // --- Fechas ---
        const fInicio = document.getElementById("fecha_inicio");
        const fTermino = document.getElementById("fecha_termino");
        fInicio.addEventListener("change", () => fTermino.min = fInicio.value);
    });
</script>