<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/header.php';
include 'content/connect.php';

$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Consulta base
$sql = "SELECT
    ct.id_cotizacion,
    ct.fecha_inicio,
    SUM(vs.lavado) AS total_lavado,
    SUM(vs.neumatico) AS total_neumatico
FROM cotizaciones ct
INNER JOIN varios_serv vs ON vs.id_cotizacion = ct.id_cotizacion
WHERE ct.estado_servicio NOT IN ('Rechazada', 'Pendiente')";

// Filtros
$conditions = [];
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $conditions[] = "ct.fecha_inicio BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}
if (count($conditions) > 0) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY ct.id_cotizacion, ct.fecha_inicio ORDER BY ct.fecha_inicio DESC, ct.id_cotizacion DESC";

// Para paginación
$total_sql = "SELECT COUNT(*) as total FROM (" . $sql . ") AS subquery";
$total_result = $conn->query($total_sql);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql .= " LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Totales
$total_lavado = 0;
$total_neumatico = 0;
$gran_total = 0;
?>

<div class="container my-5">
    <h2 class="text-center mb-4">Listado de Lavado y Reparación de Neumáticos</h2>

    <form method="GET" action="listado_neumatico_lavado.php" class="mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fecha_fin); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <a href="listado_neumatico_lavado.php" class="btn btn-secondary">Limpiar</a>
            </div>
        </div>
    </form>
    <!-- Botón de exportar CSV -->
    <form method="GET" action="exportar_neumatico.php">
        <input type="hidden" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
        <input type="hidden" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
        <button type="submit" class="btn btn-success mb-3">Exportar a CSV</button>
    </form>

    <?php if ($result && $result->num_rows > 0): ?>
        <table class="table table-bordered table-striped mt-3">
            <thead class="table-primary">
                <tr>
                    <th>Cotización</th>
                    <th>Fecha Inicio</th>
                    <th>Lavado de Camión</th>
                    <th>Reparación Neumáticos</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php 
                        $total_fila = $row['total_lavado'] + $row['total_neumatico'];
                        $gran_total += $total_fila;
                    ?>
                    <tr>
                        <td><?php echo $row['id_cotizacion']; ?></td>
                        <td><?php echo date("d-m-Y", strtotime($row['fecha_inicio'])); ?></td>
                        <td class="text-end">$<?php echo number_format($row['total_lavado'], 0, ',', '.'); ?></td>
                        <td class="text-end">$<?php echo number_format($row['total_neumatico'], 0, ',', '.'); ?></td>
                        <td class="text-end">$<?php echo number_format($total_fila, 0, ',', '.'); ?></td>
                    </tr>
                    <?php
                        $total_lavado += $row['total_lavado'];
                        $total_neumatico += $row['total_neumatico'];
                    ?>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <th colspan="2" class="text-end">Totales:</th>
                    <th class="text-end">$<?php echo number_format($total_lavado, 0, ',', '.'); ?></th>
                    <th class="text-end">$<?php echo number_format($total_neumatico, 0, ',', '.'); ?></th>
                    <th class="text-end">$<?php echo number_format($gran_total, 0, ',', '.'); ?></th>
                </tr>
            </tfoot>
        </table>

<nav>
            <ul class="pagination justify-content-center">
                <!-- Botón Anterior -->
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>&patente=<?php echo urlencode($patente); ?>" aria-label="Anterior">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Página 1 -->
                <li class="page-item <?php if ($page == 1) echo 'active'; ?>">
                    <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>">1</a>
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
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <!-- Puntos suspensivos después -->
                <?php if ($page < $total_pages - 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>

                <!-- Última página -->
                <?php if ($total_pages > 1): ?>
                    <li class="page-item <?php if ($page == $total_pages) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>"><?php echo $total_pages; ?></a>
                    </li>
                <?php endif; ?>

                <!-- Botón Siguiente -->
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>" aria-label="Siguiente">
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

<?php $conn->close(); ?>
<?php include 'content/footer.php'; ?>
