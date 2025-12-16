<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
include 'content/header.php';
include 'content/connect.php';

if (!isset($_GET['id_cotizacion'])) {
    die("Cotización no especificada.");
}

$cotizacion_id = $_GET['id_cotizacion'];

// Consultas
$stmt = $conn->prepare("SELECT * FROM cotizaciones WHERE id_cotizacion = ?");
$stmt->bind_param("i", $cotizacion_id);
$stmt->execute();
$cotizacion = $stmt->get_result()->fetch_assoc();

// Consultar detalles y vehículos (sin cambios en la lógica de obtención)
$stmtDet = $conn->prepare("SELECT * FROM cotizacion_servicios WHERE id_cotizacion = ?");
$stmtDet->bind_param("i", $cotizacion_id);
$stmtDet->execute();
$resultDetalles = $stmtDet->get_result();

$stmtVar = $conn->prepare("SELECT * FROM varios_serv WHERE id_cotizacion = ?");
$stmtVar->bind_param("i", $cotizacion_id);
$stmtVar->execute();
$resultVarios = $stmtVar->get_result();

// Cliente
$sqlCli = "SELECT nombre_cliente, email_cliente FROM clientes WHERE id_cliente = " . $cotizacion['id_cliente'];
$cliente = $conn->query($sqlCli)->fetch_assoc();

// Listas
$conductores = []; $r = $conn->query("SELECT id_conductor, nombre_conductor FROM conductores"); while($row=$r->fetch_assoc()) $conductores[]=$row;
$camiones = []; $r = $conn->query("SELECT id_camiones, patente_camion FROM camiones"); while($row=$r->fetch_assoc()) $camiones[]=$row;
$ramplas = []; $r = $conn->query("SELECT id_rampla, patente_rampla FROM rampla"); while($row=$r->fetch_assoc()) $ramplas[]=$row;
$camabajas = []; $r = $conn->query("SELECT id_camabaja, patente_camabaja FROM camabaja"); while($row=$r->fetch_assoc()) $camabajas[]=$row;

$forma_pago_actual = isset($cotizacion['forma_pago']) ? $cotizacion['forma_pago'] : '';
$moneda_actual = isset($cotizacion['moneda']) ? $cotizacion['moneda'] : 'CLP';
$valor_uf_actual = isset($cotizacion['valor_uf']) ? $cotizacion['valor_uf'] : '0.00';
?>

<div class="container my-5">
    <h2 class="text-center mb-4">Editar Cotización N° <?php echo $cotizacion_id; ?></h2>

    <form id="editarCotForm" action="guardar_edicion_cotizacion.php" method="POST">
        <input type="hidden" name="cotizacion_id" value="<?php echo $cotizacion_id; ?>">

        <!-- Datos Cliente -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label>Cliente</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($cliente['nombre_cliente']); ?>" readonly>
            </div>
            <div class="col-md-6">
                <label>Email</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($cliente['email_cliente']); ?>" readonly>
            </div>
        </div>

        <!-- SECCIÓN MONEDA (NUEVO) -->
        <div class="card p-3 mb-3 bg-light">
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Tipo de Moneda</label>
                    <select class="form-select" name="moneda" id="tipoMoneda">
                        <option value="CLP" <?php echo ($moneda_actual == 'CLP') ? 'selected' : ''; ?>>Pesos (CLP)</option>
                        <option value="UF" <?php echo ($moneda_actual == 'UF') ? 'selected' : ''; ?>>Unidad de Fomento (UF)</option>
                    </select>
                </div>
                <div class="col-md-6" id="ufContainer" style="display: <?php echo ($moneda_actual == 'UF') ? 'block' : 'none'; ?>;">
                    <label>Valor UF del día</label>
                    <input type="number" step="0.01" class="form-control" name="valor_uf" id="valorUF" value="<?php echo $valor_uf_actual; ?>">
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label>Descripción General</label>
            <textarea name="descripcion_servicio" class="form-control" style="text-transform: uppercase;"><?php echo htmlspecialchars($cotizacion['detalle_servicios']); ?></textarea>
        </div>

        <!-- ITEMS DE SERVICIO -->
        <h5>Editar Ítems Existentes</h5>
        <ul class="list-group mb-4">
            <?php while ($detalle = $resultDetalles->fetch_assoc()): ?>
            <li class="list-group-item">
                <div class="row g-2 align-items-center">
                    <input type="hidden" name="ids_existentes[]" value="<?php echo $detalle['id_cot_servicios']; ?>">
                    <div class="col-md-1">
                        <label class="small">Cant.</label>
                        <input type="number" class="form-control" name="cantidades_existentes[]" value="<?php echo $detalle['cantidad']; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="small">Descripción</label>
                        <textarea name="descripciones_existentes[]" class="form-control" rows="1"><?php echo htmlspecialchars($detalle['servicio_manual']); ?></textarea>
                    </div>
                    <div class="col-md-2">
                        <label class="small">Precio</label>
                        <input type="number" class="form-control" name="precios_existentes[]" value="<?php echo $detalle['precio']; ?>" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <label class="small">Total</label>
                        <input type="number" class="form-control" name="totales_existentes[]" value="<?php echo $detalle['total_servicio']; ?>" readonly>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-item-btn" data-id="<?php echo $detalle['id_cot_servicios']; ?>">X</button>
                    </div>
                </div>
            </li>
            <?php endwhile; ?>
        </ul>

        <!-- VEHÍCULOS ASIGNADOS EXISTENTES (VARIOS) -->
        <?php if ($resultVarios->num_rows > 0): ?>
        <h5>Vehículos Asignados (Existentes)</h5>
        <div class="card p-3 mb-4 bg-light">
            <?php while ($varios = $resultVarios->fetch_assoc()): ?>
            <input type="hidden" name="id_serv_varios_existente[]" value="<?php echo $varios['id_serv']; ?>">
            <div class="row g-2 mb-2 border-bottom pb-2">
                <div class="col-md-3">
                    <label class="small">Conductor</label>
                    <select class="form-select form-select-sm" name="conductor_id_existente[]">
                        <option value="">Sin Conductor</option>
                        <?php foreach($conductores as $c): ?>
                            <option value="<?php echo $c['id_conductor']; ?>" <?php if($varios['id_conductor'] == $c['id_conductor']) echo 'selected'; ?>>
                                <?php echo $c['nombre_conductor']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">Camión</label>
                    <select class="form-select form-select-sm" name="camion_id_existente[]">
                        <option value="">Sin Camión</option>
                        <?php foreach($camiones as $c): ?>
                            <option value="<?php echo $c['id_camiones']; ?>" <?php if($varios['id_camiones'] == $c['id_camiones']) echo 'selected'; ?>>
                                <?php echo $c['patente_camion']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">Rampla</label>
                    <select class="form-select form-select-sm" name="rampla_id_existente[]">
                        <option value="">Sin Rampla</option>
                        <?php foreach($ramplas as $r): ?>
                            <option value="<?php echo $r['id_rampla']; ?>" <?php if($varios['id_rampla'] == $r['id_rampla']) echo 'selected'; ?>>
                                <?php echo $r['patente_rampla']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">Cama Baja</label>
                    <select class="form-select form-select-sm" name="camabaja_id_existente[]">
                        <option value="">Sin Cama Baja</option>
                        <?php foreach($camabajas as $cb): ?>
                            <option value="<?php echo $cb['id_camabaja']; ?>" <?php if($varios['id_camabaja'] == $cb['id_camabaja']) echo 'selected'; ?>>
                                <?php echo $cb['patente_camabaja']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <!-- DATOS SERVICIO -->
        <div class="row g-3 mb-4">
            <div class="col-md-6"><label>Origen</label><input type="text" class="form-control" name="origen" value="<?php echo $cotizacion['origen']; ?>"></div>
            <div class="col-md-6"><label>Destino</label><input type="text" class="form-control" name="destino" value="<?php echo $cotizacion['destino']; ?>"></div>
            <div class="col-md-6"><label>Inicio</label><input type="date" class="form-control" name="fecha_inicio" value="<?php echo $cotizacion['fecha_inicio']; ?>"></div>
            <div class="col-md-6"><label>Fin</label><input type="date" class="form-control" name="fecha_termino" value="<?php echo $cotizacion['fecha_termino']; ?>"></div>
        </div>

        <hr>

        <!-- AGREGAR NUEVOS COMPLEJOS -->
        <h5>Agregar Servicios con Vehículos (Nuevos)</h5>
        <ul class="list-group mb-2" id="nuevoServicioConVehiculosList"></ul>
        <button type="button" class="btn btn-primary" onclick="agregarNuevoServicio()">+ Agregar Servicio con Vehículo</button>

        <hr>

        <!-- AGREGAR NUEVOS SIMPLES -->
        <h5>Agregar Ítems Simples (Sin Vehículos)</h5>
        <ul class="list-group mb-2" id="itemsSimplesList"></ul>
        <button type="button" class="btn btn-secondary" onclick="agregarItemSimple()">+ Agregar Ítem Simple</button>

        <hr>

        <!-- PAGO -->
        <div class="mb-3">
            <label>Forma de Pago:</label><br>
            <input type="radio" name="forma_pago" value="Contado" <?php if($forma_pago_actual=='Contado') echo 'checked'; ?>> Contado
            <input type="radio" name="forma_pago" value="Crédito" <?php if($forma_pago_actual=='Crédito') echo 'checked'; ?>> Crédito
        </div>

        <div class="mt-4 text-center">
            <button type="submit" class="btn btn-success">Guardar Cambios</button>
            <a href="listado_cotizaciones.php" class="btn btn-outline-secondary">Volver</a>
        </div>
    </form>
</div>

<script>
// Lógica para mostrar/ocultar UF
document.getElementById('tipoMoneda').addEventListener('change', function() {
    const container = document.getElementById('ufContainer');
    const input = document.getElementById('valorUF');
    if (this.value === 'UF') {
        container.style.display = 'block';
        input.setAttribute('required', 'required');
    } else {
        container.style.display = 'none';
        input.removeAttribute('required');
        input.value = '0.00';
    }
});

// Strings de opciones para JS
const optCond = '<option value="">Sel. Conductor</option>' + `<?php foreach($conductores as $c) echo "<option value='{$c['id_conductor']}'>{$c['nombre_conductor']}</option>"; ?>`;
const optCam = '<option value="">Sel. Camión</option>' + `<?php foreach($camiones as $c) echo "<option value='{$c['id_camiones']}'>{$c['patente_camion']}</option>"; ?>`;
const optRam = '<option value="">Sel. Rampla</option>' + `<?php foreach($ramplas as $r) echo "<option value='{$r['id_rampla']}'>{$r['patente_rampla']}</option>"; ?>`;
const optBaja = '<option value="">Sel. Cama Baja</option>' + `<?php foreach($camabajas as $c) echo "<option value='{$c['id_camabaja']}'>{$c['patente_camabaja']}</option>"; ?>`;

function agregarNuevoServicio() {
    const container = document.getElementById('nuevoServicioConVehiculosList');
    const li = document.createElement('li');
    li.className = 'list-group-item bg-light mb-3';
    li.innerHTML = `
        <div class="row g-2">
            <div class="col-md-2"><label class="small">Cant.</label><input type="number" class="form-control" name="cantidades_nuevas[]" value="1" required></div>
            <div class="col-md-8"><label class="small">Descripción</label><textarea class="form-control" name="descripciones_nuevas[]" rows="1" required></textarea></div>
            <div class="col-md-2"><label class="small">Precio</label><input type="number" class="form-control" name="precios_nuevos[]" required step="0.01"></div>
        </div>
        <div class="row g-2 mt-1">
            <div class="col-md-3"><select class="form-select form-select-sm" name="conductor_nuevo[]">${optCond}</select></div>
            <div class="col-md-3"><select class="form-select form-select-sm" name="camion_nuevo[]">${optCam}</select></div>
            <div class="col-md-3"><select class="form-select form-select-sm" name="rampla_nuevo[]">${optRam}</select></div>
            <div class="col-md-3"><select class="form-select form-select-sm" name="camabaja_nuevo[]">${optBaja}</select></div>
        </div>
        <div class="text-end mt-1"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('li').remove()">Eliminar</button></div>
    `;
    container.appendChild(li);
}

function agregarItemSimple() {
    const container = document.getElementById('itemsSimplesList');
    const li = document.createElement('li');
    li.className = 'list-group-item mb-2';
    li.innerHTML = `
        <div class="row g-2 align-items-end">
            <div class="col-md-2"><label class="small">Cant.</label><input type="number" class="form-control cant-s" name="cantidad_simple[]" value="1" required></div>
            <div class="col-md-6"><label class="small">Descripción</label><textarea class="form-control" name="descripcion_simple[]" rows="1" required></textarea></div>
            <div class="col-md-2"><label class="small">Precio</label><input type="number" class="form-control prec-s" name="precio_simple[]" required step="0.01"></div>
            <div class="col-md-2"><label class="small">Total</label><input type="number" class="form-control tot-s" readonly></div>
            <div class="col-md-12 text-end mt-2"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('li').remove()">X</button></div>
        </div>
    `;
    container.appendChild(li);
    
    // Calcular total dinámico
    const cant = li.querySelector('.cant-s');
    const prec = li.querySelector('.prec-s');
    const tot = li.querySelector('.tot-s');
    const calc = () => { tot.value = (parseFloat(cant.value)||0) * (parseFloat(prec.value)||0); };
    cant.addEventListener('input', calc);
    prec.addEventListener('input', calc);
}

// Lógica de borrar existentes
document.querySelectorAll('.remove-item-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'eliminar_ids[]'; input.value = id;
        document.getElementById('editarCotForm').appendChild(input);
        this.closest('li').remove();
    });
});
</script>

<?php include 'content/footer.php'; ?>