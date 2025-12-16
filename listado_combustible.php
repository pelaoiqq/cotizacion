<?php
session_start();

/*// Habilitar errores para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

// Validar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/header.php';
include 'content/connect.php';

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Entradas
$patente = isset($_GET['patente']) ? $conn->real_escape_string(trim($_GET['patente'])) : '';
$conductor = isset($_GET['conductor']) ? $conn->real_escape_string(trim($_GET['conductor'])) : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Consulta base
$sql = "SELECT ct.id_cotizacion, ct.created_at, vs.combustible, c.patente_camion, cd.nombre_conductor
        FROM cotizaciones ct 
        INNER JOIN varios_serv vs ON vs.id_cotizacion = ct.id_cotizacion 
        INNER JOIN conductores cd ON vs.id_conductor = cd.id_conductor
        INNER JOIN camiones c ON vs.id_camiones = c.id_camiones
        WHERE ct.estado_servicio NOT IN ('Rechazada', 'Pendiente')";

// Filtros
if (!empty($conductor)) {
    $sql .= " AND cd.nombre_conductor LIKE '%$conductor%'";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $sql .= " AND ct.created_at BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}
if (!empty($patente)) {
    $sql .= " AND c.patente_camion LIKE '%$patente%'";
}

$sql .= " ORDER BY ct.created_at DESC, vs.id_cotizacion DESC LIMIT $limit OFFSET $offset";

// Consulta total
$total_sql = "SELECT COUNT(*) as total FROM cotizaciones ct 
              INNER JOIN varios_serv vs ON vs.id_cotizacion = ct.id_cotizacion 
              INNER JOIN conductores cd ON vs.id_conductor = cd.id_conductor
              INNER JOIN camiones c ON vs.id_camiones = c.id_camiones
              WHERE ct.estado_servicio NOT IN ('Rechazada', 'Pendiente')";

if (!empty($conductor)) {
    $total_sql .= " AND cd.nombre_conductor LIKE '%$conductor%'";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $total_sql .= " AND ct.created_at BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}
if (!empty($patente)) {
    $total_sql .= " AND c.patente_camion LIKE '%$patente%'";
}

$total_result = $conn->query($total_sql);
if (!$total_result) {
    die("Error en el conteo: " . $conn->error);
}
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta principal: " . $conn->error);
}

$total_combustible = 0;
?>

<div class="container my-5">
    <h2 class="text-center mb-4">Listado de Uso de Combustible</h2>

    <form method="GET" action="listado_combustible.php" class="mb-4">
        <div class="row g-3">
            <div class="col-md-2">
                <input type="text" name="patente" class="form-control" placeholder="Buscar por patente" value="<?php echo htmlspecialchars($patente); ?>">
            </div>
            <div class="col-md-3">
                <input type="text" name="conductor" class="form-control" placeholder="Buscar por conductor" value="<?php echo htmlspecialchars($conductor); ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fecha_fin); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <a href="listado_combustible.php" class="btn btn-secondary">Limpiar</a>
            </div>
        </div>
    </form>

    <form method="GET" action="exportar_combustible.php">
        <input type="hidden" name="patente" value="<?php echo htmlspecialchars($patente); ?>">
        <input type="hidden" name="conductor" value="<?php echo htmlspecialchars($conductor); ?>">
        <input type="hidden" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
        <input type="hidden" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
        <button type="submit" class="btn btn-success mb-3">Exportar a CSV</button>
    </form>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped mt-3">
            <thead class="table-primary">
                <tr>
                    <th>Cotización</th>
                    <th>Fecha Creación</th>
                    <th>Patente Camión</th> 
                    <th>Conductor</th> 
                    <th>Combustible</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id_cotizacion']; ?></td>
                        <td><?php echo date("d-m-Y", strtotime($row['created_at'])); ?></td>
                        <td><?php echo $row['patente_camion']; ?></td>
                        <td><?php echo $row['nombre_conductor']; ?></td>
                        <td class="text-end">$<?php echo number_format($row['combustible'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php $total_combustible += $row['combustible']; ?>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <th colspan="4" class="text-end">Total Combustible:</th>
                    <th class="text-end">$<?php echo number_format($total_combustible, 0, ',', '.'); ?></th>
                </tr>
            </tfoot>
        </table>

       <!-- <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&conductor=<?php echo urlencode($conductor); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>&patente=<?php echo urlencode($patente); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>-->

        <nav>
            <ul class="pagination justify-content-center">
                <!-- Botón Anterior -->
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&conductor=<?php echo urlencode($conductor); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>&patente=<?php echo urlencode($patente); ?>" aria-label="Anterior">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Página 1 -->
                <li class="page-item <?php if ($page == 1) echo 'active'; ?>">
                    <a class="page-link" href="?page=1&conductor=<?php echo urlencode($conductor); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>&patente=<?php echo urlencode($patente); ?>">1</a>
                </li>

                <!-- Puntos suspensivos antes -->
                <?php if ($page > 3): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>

                <!-- Páginas cercanas a la actual -->
                <?php
                for ($i = max(2, $page - 1); $i <= min($total_pages - 1, $page + 1); $i++):
                ?>
                    <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&conductor=<?php echo urlencode($conductor); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>&patente=<?php echo urlencode($patente); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <!-- Puntos suspensivos después -->
                <?php if ($page < $total_pages - 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>

                <!-- Última página -->
                <?php if ($total_pages > 1): ?>
                    <li class="page-item <?php if ($page == $total_pages) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&conductor=<?php echo urlencode($conductor); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>&patente=<?php echo urlencode($patente); ?>"><?php echo $total_pages; ?></a>
                    </li>
                <?php endif; ?>

                <!-- Botón Siguiente -->
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&conductor=<?php echo urlencode($conductor); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>&patente=<?php echo urlencode($patente); ?>" aria-label="Siguiente">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

    <?php else: ?>
        <p class="text-center">No se encontraron registros.</p>
    <?php endif; ?>
</div>

<?php
$conn->close();
include 'content/footer.php';
?>
