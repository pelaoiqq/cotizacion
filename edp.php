<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
include 'content/header.php';
include 'content/connect.php';

// Obtener lista de clientes
$clientes_result = $conn->query("SELECT id_cliente, nombre_cliente FROM clientes ORDER BY nombre_cliente ASC");
$clientes = array();
while ($cliente_row = $clientes_result->fetch_assoc()) {
    $clientes[] = $cliente_row;
}

$id_cliente = '';
$fecha_inicio = '';
$fecha_termino = '';
$resultados = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['buscar'])) {
    $id_cliente = isset($_POST['id_cliente']) ? (int) $_POST['id_cliente'] : '';
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_termino = isset($_POST['fecha_termino']) ? $_POST['fecha_termino'] : '';

    if ($id_cliente && $fecha_inicio && $fecha_termino) {
        // --- CORRECCIÓN IMPORTANTE ---
        // Se cambió c.total_servicio por el cálculo matemático real: (precio * cantidad)
        // Se asegura de usar IFNULL para evitar errores con valores vacíos
        $query = "SELECT
                c.id_cotizacion,
                c.detalle_servicios,
                c.fecha_inicio,
                c.fecha_termino,
                GROUP_CONCAT(DISTINCT cs.guia SEPARATOR ', ') AS guias,
                
                -- CÁLCULO DINÁMICO (Igual que Listado y Exportar)
                SUM(IFNULL(cs.precio, 0) * IFNULL(cs.cantidad, 0)) AS total_precio,
                SUM(IFNULL(cs.estadia, 0)) AS estadia_total,
                
                -- Se asume que esta es la columna de descuento (si usas descto_servicio cámbialo aquí)
                c.descto_servicio AS valor_total_descto 
            FROM cotizaciones c
            LEFT JOIN cotizacion_servicios cs ON c.id_cotizacion = cs.id_cotizacion
            WHERE c.id_cliente = ?
              AND c.fecha_inicio >= ?
              AND c.fecha_termino <= ?
              AND (c.estado_servicio = 'Aprobada' OR c.estado_servicio = 'Finalizado')
            GROUP BY c.id_cotizacion
            ORDER BY c.fecha_inicio, c.id_cotizacion";

        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param('iss', $id_cliente, $fecha_inicio, $fecha_termino);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                // Vinculamos las variables al resultado
                $stmt->bind_result($id_cot, $detalle, $inicio, $fin, $guias, $precio, $estadia, $descto);
                while ($stmt->fetch()) {
                    $resultados[] = array(
                        'id_cotizacion' => $id_cot,
                        'detalle_servicios' => $detalle,
                        'fecha_inicio' => $inicio,
                        'fecha_termino' => $fin,
                        'guias' => $guias,
                        'total_precio' => $precio,
                        'estadia_total' => $estadia,
                        'descuento' => $descto // Guardamos el descuento para usarlo abajo
                    );
                }
            }
            $stmt->close();
        } else {
            echo "Error en consulta: " . $conn->error;
        }
    } else {
        echo "<div class='alert alert-warning'>Por favor, complete todos los campos de búsqueda.</div>";
    }
}
?>

<div class="container mt-4">
    <h2 class="text-center">Estado de Pago (EDP)</h2>
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-4">
            <label for="id_cliente_select">Cliente:</label>
            <select id="id_cliente_select" name="id_cliente" class="form-select" required>
                <option value="">Seleccione un cliente</option>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?php echo htmlspecialchars($cliente['id_cliente']); ?>" <?php echo ($id_cliente == $cliente['id_cliente']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cliente['nombre_cliente']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label>Fecha Inicio:</label>
            <input type="date" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
        </div>
        <div class="col-md-3">
            <label>Fecha Término:</label>
            <input type="date" name="fecha_termino" class="form-control" value="<?php echo htmlspecialchars($fecha_termino); ?>" required>
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" name="buscar" class="btn btn-primary w-100">Buscar</button>
        </div>
    </form>

    <?php if (!empty($resultados)): ?>
        <form method="POST" action="generar_edp.php">
            <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($id_cliente); ?>">
            <input type="hidden" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
            <input type="hidden" name="fecha_termino" value="<?php echo htmlspecialchars($fecha_termino); ?>">

            <table class="table table-bordered table-striped mt-4">
                <thead class="table-primary">
                    <tr>
                        <th>Sel.</th>
                        <th>ID Cotización</th>
                        <th>Servicio</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Guías</th>
                        <th>Valor Servicio</th>
                        <th>Estadía</th>
                        <th>Descuento</th> <!-- Agregué columna visual para validar -->
                        <th>Valor Total Neto</th>
                        <th>IVA (19%)</th>
                        <th>Total Servicio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $row): 
                        // CÁLCULOS MATEMÁTICOS PHP 5.6 COMPATIBLES
                        $valor_servicio = floatval($row['total_precio']); // Suma de (precio * cantidad)
                        $estadia        = floatval($row['estadia_total']);
                        $descuento      = floatval($row['descuento']);

                        // Fórmula: (Servicio + Estadía) - Descuento
                        $valor_total_neto_fila = ($valor_servicio + $estadia) - $descuento;
                        
                        $iva_fila = $valor_total_neto_fila * 0.19;
                        $total_servicio_fila = $valor_total_neto_fila + $iva_fila;
                    ?>
                    <tr>
                        <td><input type="checkbox" name="cotizaciones_seleccionadas[]" value="<?php echo $row['id_cotizacion']; ?>" class="form-check-input"></td>
                        <td><?php echo htmlspecialchars($row['id_cotizacion']); ?></td>
                        <td><?php echo htmlspecialchars($row['detalle_servicios']); ?></td>
                        <td><?php echo htmlspecialchars($row['fecha_inicio']); ?></td>
                        <td><?php echo htmlspecialchars($row['fecha_termino']); ?></td>
                        <td><?php echo htmlspecialchars($row['guias']); ?></td>
                        
                        <!-- Valores Monetarios -->
                        <td class="text-end">$<?php echo number_format($valor_servicio, 0, ',', '.'); ?></td>
                        <td class="text-end">$<?php echo number_format($estadia, 0, ',', '.'); ?></td>
                        
                        <!-- Columna Descuento (Opcional, pero útil para verificar el total) -->
                        <td class="text-end text-danger">-$<?php echo number_format($descuento, 0, ',', '.'); ?></td>

                        <td class="text-end fw-bold">$<?php echo number_format($valor_total_neto_fila, 0, ',', '.'); ?></td>
                        <td class="text-end">$<?php echo number_format($iva_fila, 0, ',', '.'); ?></td>
                        <td class="text-end fw-bold text-success">$<?php echo number_format($total_servicio_fila, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="text-end mt-3">
                <button type="submit" name="generar" value="1" class="btn btn-success">Generar EDP con Seleccionadas</button>
            </div>
            <br>
        </form>
    <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
        <p class="alert alert-info text-center mt-3">No se encontraron cotizaciones para los criterios seleccionados.</p>
    <?php endif; ?>
</div>

<?php include 'content/footer.php'; ?>
<?php $conn->close(); ?>