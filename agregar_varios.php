<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<?php
include 'content/header.php';
include 'content/connect.php';

// Obtener el número de cotización desde el parámetro GET
$id_cotizacion = isset($_GET['id_cotizacion']) ? $_GET['id_cotizacion'] : null;

// Obtener los conductores
$sqlConductor = "SELECT id_conductor, nombre_conductor FROM conductores";
$resultConductor = $conn->query($sqlConductor);

// Obtener los Camiones
$sqlCamion = "SELECT id_camiones, marca_camion, patente_camion FROM camiones";
$resultCamion = $conn->query($sqlCamion);

// Obtener las Ramplas
$sqlRampla = "SELECT id_rampla, patente_rampla FROM rampla";
$resultRampla = $conn->query($sqlRampla);

// Obtener las Camabaja
$sqlCamabaja = "SELECT id_camabaja, patente_camabaja FROM camabaja";
$resultCamabaja = $conn->query($sqlCamabaja);

// Consultar los datos de la cotización seleccionada 
$sql = "SELECT 
            c.id_cotizacion,
            cl.nombre_cliente, 
            cl.nombre_contacto, 
            cl.telefono_contacto, 
            c.origen, 
            c.destino, 
            c.estado_servicio,
            c.descto_servicio AS descuento,
            c.fecha_inicio, 
            c.fecha_termino,
            GROUP_CONCAT(DISTINCT s.nombre_servicio SEPARATOR ', ') AS servicios,
            c.total_servicio AS total,
            cs.estadia AS estadia_total, 
            cs.total_servicio AS total_total,
            c.guia,
            c.nroservicio,
            c.oc,
            c.factura,
            c.detalle_servicios
        FROM cotizaciones c
        INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente
        INNER JOIN (
            SELECT 
                id_cotizacion, 
                id_servicio, 
                SUM(precio) AS precio, 
                SUM(estadia) AS estadia, 
                SUM(total_servicio) AS total_servicio
            FROM cotizacion_servicios
            GROUP BY id_cotizacion, id_servicio
        ) cs ON c.id_cotizacion = cs.id_cotizacion
        INNER JOIN servicios s ON cs.id_servicio = s.id_servicios
        LEFT JOIN varios_serv vs ON c.id_cotizacion = vs.id_cotizacion
        WHERE c.id_cotizacion = ?
        GROUP BY c.id_cotizacion";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cotizacion);
$stmt->execute();
$result = $stmt->get_result();
$cotizacion = $result->fetch_assoc();
$stmt->close();

// Consultar registros de varios_serv asociados a la cotización
$sqlVariosServ = "SELECT vs.*, 
                    cs.estadia AS estadia_precio,
                    cs.cantidad,
                    cs.total_servicio,
                    cs.precio AS servicio_precio
                    FROM varios_serv vs 
                    LEFT JOIN cotizacion_servicios cs ON vs.id_cotizacion = cs.id_cotizacion AND vs.id_serv = cs.id_serv 
                    WHERE vs.id_cotizacion = ?
                    ORDER BY vs.id_serv ASC"; 

$stmtVariosServ = $conn->prepare($sqlVariosServ);
$stmtVariosServ->bind_param("i", $id_cotizacion);
$stmtVariosServ->execute();
$resultVariosServ = $stmtVariosServ->get_result();
$variosServData = $resultVariosServ->fetch_all(MYSQLI_ASSOC);
$stmtVariosServ->close();

// Verificar si la cotización está finalizada 
$esFinalizado = ($cotizacion['estado_servicio'] === 'Finalizado' || $cotizacion['estado_servicio'] === 'Rechazada') ? "readonly" : "";
$esDisabled = ($cotizacion['estado_servicio'] === 'Finalizado' || $cotizacion['estado_servicio'] === 'Rechazada') ? "disabled" : "";

// Verificar si ya existe un PDF para la cotización
$sql_check = "SELECT id_pdf FROM upload_pdf WHERE id_cotizacion = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $id_cotizacion);
$stmt_check->execute();
$stmt_check->store_result();
$pdf_available = ($stmt_check->num_rows > 0); 
$stmt_check->close();
$conn->close();
?>

<div class="container my-5">
    <h2 class="text-center mb-4">Agregar Información Adicional</h2>
    <?php if ($cotizacion): ?>
        <form method="POST" action="guardar_datos.php" id="formularioDatos">
            <h5>Datos del Servicio</h5>
            <!-- Cabecera sin cambios -->
            <div class="form-floating row g-3"> 
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="id_cotizacion" name="id_cotizacion" value="<?php echo $cotizacion['id_cotizacion']; ?>" readonly>
                        <label for="id_cotizacion" class="form-label">N° Cotización</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="nombre_cliente" value="<?php echo $cotizacion['nombre_cliente']; ?>" readonly>
                        <label for="nombre_cliente" class="form-label">Cliente</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="nombre_contacto" value="<?php echo $cotizacion['nombre_contacto']; ?>" readonly>
                        <label for="nombre_contacto" class="form-label">Contacto</label>
                    </div>
                </div>
            </div>

            <div class="form-floating row g-3"> 
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="origen" value="<?php echo $cotizacion['origen']; ?>" readonly>
                        <label for="origen" class="form-label">Origen</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="destino" value="<?php echo $cotizacion['destino']; ?>" readonly>
                        <label for="destino" class="form-label">Destino</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="servicios" name="servicios" value="<?php echo $cotizacion['detalle_servicios']; ?>">
                        <label for="servicios" class="form-label">Servicio(s) a Realizar</label>
                    </div>
                </div>
            </div>

            <div class="form-floating row g-3"> 
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="valor_servicio" name="valor_servicio" value="<?php echo $cotizacion['total']; ?>">
                        <label for="valor_servicio" class="form-label">Valor del Servicio</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" value="<?php echo $cotizacion['fecha_inicio']; ?>">
                        <label for="fecha_inicio">Inicio del Servicio</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="date" class="form-control" name="fecha_termino" id="fecha_termino" value="<?php echo $cotizacion['fecha_termino']; ?>">
                        <label for="fecha_termino">Fin del Servicio</label>
                    </div>
                </div>
            </div>
            <hr>
            
            <?php foreach ($variosServData as $index => $varios): ?>
                <h5>Datos para agregar del Servicio Nº <?php echo $index + 1; ?></h5>
                <!-- IMPORTANTE: Aquí usamos índices explícitos [$index] para evitar mezcla de datos -->
                <div class="form-floating row g-3"> 
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="conductor_id_<?php echo $index; ?>" name="conductor_id[<?php echo $index; ?>]" <?php echo $esDisabled; ?>>
                                <option value="">Seleccione un Conductor</option>
                                <?php
                                if ($resultConductor->num_rows > 0) {
                                    $resultConductor->data_seek(0); 
                                    while ($conductor = $resultConductor->fetch_assoc()) {
                                        $selected = ($varios['id_conductor'] == $conductor['id_conductor']) ? "selected" : "";
                                        echo "<option value='" . $conductor['id_conductor'] . "' $selected>" . $conductor['nombre_conductor'] . "</option>";
                                    }
                                }
                                ?>
                            </select>                     
                            <label for="conductor_id_<?php echo $index; ?>" class="form-label">Conductor</label>
                        </div>
                    </div>
                    
                    <input type="hidden" class="form-control" id="id_serv_<?php echo $index; ?>" name="id_serv[<?php echo $index; ?>]" value="<?php echo isset($varios['id_serv']) && $varios['id_serv'] !== '' ? $varios['id_serv'] : 0; ?>" min="0" placeholder="id_serv" <?php echo $esFinalizado; ?>>
                    
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <!-- Se agregó $esDisabled aquí, faltaba en tu código original -->
                            <select class='form-select' name='camion_id[<?php echo $index; ?>]' <?php echo $esDisabled; ?>>
                                <option value='' <?php echo (empty($varios['id_camiones']) ? 'selected' : ''); ?>>Seleccione un Camión</option>
                                <?php
                                if ($resultCamion->num_rows > 0) {
                                    $resultCamion->data_seek(0);
                                    while ($camion = $resultCamion->fetch_assoc()) {
                                        $selected = ($varios['id_camiones'] == $camion['id_camiones']) ? 'selected' : '';
                                        echo "<option value='" . $camion['id_camiones'] . "' $selected>" . $camion['patente_camion'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                            <label for="camion_id_<?php echo $index; ?>" class="form-label">Camión Asignado</label>
                        </div>
                    </div>

                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="rampla_id_<?php echo $index; ?>" name="rampla_id[<?php echo $index; ?>]" <?php echo $esDisabled; ?>>
                                <option value='' <?php echo (empty($varios['id_rampla']) ? 'selected' : ''); ?>>Seleccione una Rampla</option>
                                <?php
                                if ($resultRampla->num_rows > 0) {
                                    $resultRampla->data_seek(0);
                                    while ($rampla = $resultRampla->fetch_assoc()) {
                                        $selected = ($varios['id_rampla'] == $rampla['id_rampla']) ? "selected" : "";
                                        echo "<option value='" . $rampla['id_rampla'] . "' $selected>" . $rampla['patente_rampla'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                            <label for="rampla_id_<?php echo $index; ?>" class="form-label">Rampla Asignada</label>
                        </div>
                    </div>

                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="camabaja_id_<?php echo $index; ?>" name="camabaja_id[<?php echo $index; ?>]" <?php echo $esDisabled; ?>>
                                <option value='' <?php echo (empty($varios['id_camabaja']) ? 'selected' : ''); ?>>Seleccione una Cama Baja</option>
                                <?php
                                if ($resultCamabaja->num_rows > 0) {
                                    $resultCamabaja->data_seek(0);
                                    while ($camabaja = $resultCamabaja->fetch_assoc()) {
                                        $selected = ($varios['id_camabaja'] == $camabaja['id_camabaja']) ? "selected" : "";
                                        echo "<option value='" . $camabaja['id_camabaja'] . "' $selected>" . $camabaja['patente_camabaja'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                            <label for="camabaja_id_<?php echo $index; ?>" class="form-label">Cama Baja Asignada</label>
                        </div>
                    </div>
                </div>

                <div class="form-floating row g-3"> 
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="cantidad[<?php echo $index; ?>]" value="<?php echo isset($varios['cantidad']) ? $varios['cantidad'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                            <label class="form-label">Cantidad</label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="precio[<?php echo $index; ?>]" value="<?php echo isset($varios['servicio_precio']) ? $varios['servicio_precio'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                            <label class="form-label">Precio</label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="total[<?php echo $index; ?>]" value="<?php echo isset($varios['total_servicio']) ? $varios['total_servicio'] : 0; ?>" min="0" readonly>
                            <label class="form-label">Total</label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="estadia[<?php echo $index; ?>]" value="<?php echo isset($varios['estadia_precio']) ? $varios['estadia_precio'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                            <label class="form-label">Sobre Estadía / Otros</label>
                        </div>
                    </div>
                     <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="total_se[<?php echo $index; ?>]" value="<?php echo isset($varios['total_se']) ? $varios['total_se'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                            <label class="form-label">Total SE</label>
                        </div>
                    </div>
                </div>
                
                <!-- GASTOS OPERATIVOS CON INDICES EXPLICITOS -->
                <div class="form-floating row g-3">
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="kilometraje[<?php echo $index; ?>]" value="<?php echo isset($varios['kilometraje']) ? $varios['kilometraje'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                            <label class="form-label">Kilometraje</label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="combustible[<?php echo $index; ?>]" value="<?php echo isset($varios['combustible']) ? $varios['combustible'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                            <label class="form-label">Combustible</label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="peaje[<?php echo $index; ?>]" value="<?php echo isset($varios['peaje']) ? $varios['peaje'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                            <label class="form-label">Peaje</label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="lavado[<?php echo $index; ?>]" value="<?php echo isset($varios['lavado']) ? $varios['lavado'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                            <label class="form-label">Lavado</label>
                        </div>
                    </div>
                </div>
                <div class="form-floating row g-3"> 
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="viatico[<?php echo $index; ?>]" value="<?php echo isset($varios['viatico']) ? $varios['viatico'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                            <label class="form-label">Viático</label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="neumatico[<?php echo $index; ?>]" value="<?php echo isset($varios['neumatico']) ? $varios['neumatico'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                            <label class="form-label">Rep. Neumático</label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="externo[<?php echo $index; ?>]" value="<?php echo isset($varios['externo']) ? $varios['externo'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                            <label class="form-label">Pago Externos</label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="otros[<?php echo $index; ?>]" value="<?php echo isset($varios['otros']) ? $varios['otros'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                            <label class="form-label">Otros</label>
                        </div>
                    </div>
                </div>           
                <hr>

            <?php endforeach; ?>
            
            <!-- Resto del formulario (totales, botones) igual que antes -->
            <h5>Cierre del Servicio</h5>
            <div class="form-floating row g-3"> 
                <div class="col-md">
                    <div class="form-floating mb-3">
                    <input type="number" class="form-control" id="descuento" name="descuento" value="<?php echo isset($cotizacion['descuento']) ? $cotizacion['descuento'] : 0; ?>" min="0" <?php echo $esFinalizado; ?>>
                    <label for="descuento" class="form-label">Descuentos al Cliente</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating mb-3">
                    <input type="number" class="form-control" id="valor_total" name="valor_total" readonly>
                    <label for="valor_total" class="form-label">Valor Total con Desctos</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="guia" name="guia" value="<?php echo isset($cotizacion['guia']) ? $cotizacion['guia'] : ''; ?>" placeholder="guia" <?php echo $esFinalizado; ?>>
                        <label for="guia" class="form-label">Número de Guía</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="nroservicio" name="nroservicio" value="<?php echo isset($cotizacion['nroservicio']) ? $cotizacion['nroservicio'] : ''; ?>" placeholder="Nº Servicio" <?php echo $esFinalizado; ?>>
                        <label for="nroservicio" class="form-label">Nº de Servicio</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="oc" name="oc" value="<?php echo isset($cotizacion['oc']) ? $cotizacion['oc'] : ''; ?>" placeholder="Orden de Compra" <?php echo $esFinalizado; ?>>
                        <label for="oc" class="form-label">Nº Orden de Compra</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="factura" name="factura" value="<?php echo isset($cotizacion['factura']) ? $cotizacion['factura'] : ''; ?>" placeholder="factura" <?php echo $esFinalizado; ?>>
                        <label for="factura" class="form-label">Número de Factura</label>
                    </div>
                </div>
            </div>
            <br>
            <div style="display: flex; justify-content: space-between;">
                <div>
                    <button type="submit" class="btn btn-primary" <?php echo $esDisabled; ?>>Guardar</button>
                    <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center" onclick="window.location.href='listado_cotizaciones.php';">Volver</button>
                </div>
                <div>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#uploadPdfModal">
                        Cargar OC en PDF
                    </button>
                    <button type="button" class="btn btn-success" id="viewPdfBtn" disabled onclick="viewPdf()">
                        Visualizar PDF
                    </button>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-danger">No se encontraron datos.</div>
    <?php endif; ?>
</div>

<!-- Scripts (se mantienen igual, solo ajustar nombres si es necesario) -->
<script>
function obtenerValor(campo) {
    const valor = parseFloat(campo.value) || 0; 
    return valor;
}

document.addEventListener("DOMContentLoaded", function () {
    // Nota: Como cambiamos a name="campo[index]", getElementsByName buscará el exacto.
    // Usaremos querySelectorAll para buscar por atributo que contenga el nombre base
    const selectorBase = (name) => document.querySelectorAll(`input[name^="${name}["]`);

    const descuentoCampos = ["combustible", "peaje", "lavado", "viatico", "neumatico", "externo", "otros"]
        .map(name => Array.from(selectorBase(name))).flat();

    const estadiaCampos = Array.from(selectorBase("estadia"));
    const valorServicioInput = document.getElementById("valor_servicio");
    const descuentoInput = document.getElementById("descuento");
    const valorTotalInput = document.getElementById("valor_total");

    const cantidadInputs = Array.from(selectorBase("cantidad"));
    const precioInputs = Array.from(selectorBase("precio"));
    const totalInputs = Array.from(selectorBase("total")); // Ojo, el nombre base es 'total' no 'total[]'
    const totalSEInputs = Array.from(selectorBase("total_se"));

    function calcularTotal(index) {
        if(!cantidadInputs[index] || !precioInputs[index]) return;
        
        const cantidad = obtenerValor(cantidadInputs[index]);
        const precio = obtenerValor(precioInputs[index]);
        const estadia = obtenerValor(estadiaCampos[index]);

        const total = cantidad * precio;
        if(totalInputs[index]) totalInputs[index].value = total.toFixed(0);

        const totalSE = total + estadia;
        if(totalSEInputs[index]) totalSEInputs[index].value = totalSE.toFixed(0);

        calcularTotales(); 
    }

    function calcularTotales() {
        let valorServicio = 0;
        totalInputs.forEach(input => {
            valorServicio += obtenerValor(input);
        });

        const descuento = obtenerValor(descuentoInput);
        const totalEstadia = estadiaCampos.reduce((acum, campo) => acum + obtenerValor(campo), 0);
        const totalDescuentos = descuentoCampos.reduce((acum, campo) => acum + obtenerValor(campo), 0);

        const totalConDescuentos = valorServicio + totalEstadia - descuento - totalDescuentos;
        valorTotalInput.value = totalConDescuentos.toFixed(0);
    }

    [valorServicioInput, descuentoInput, ...descuentoCampos, ...estadiaCampos].forEach(campo => {
        if(campo) campo.addEventListener("input", calcularTotales);
    });

    cantidadInputs.forEach((campo, index) => {
        campo.addEventListener("input", () => calcularTotal(index));
    });

    precioInputs.forEach((campo, index) => {
        campo.addEventListener("input", () => calcularTotal(index));
    });
    
    estadiaCampos.forEach((campo, index) => {
        campo.addEventListener("input", () => calcularTotal(index));
    });

    // Calcular inicial
    cantidadInputs.forEach((_, index) => calcularTotal(index));
    calcularTotales();
});
</script>
<!-- Scripts del PDF y footer se mantienen igual -->
<?php include 'content/footer.php'; ?>
<!-- Modales se mantienen igual -->