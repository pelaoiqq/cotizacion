<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/header.php';
include 'content/connect.php';

// Obtener parámetros de búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
$limit = 20; 
$offset = ($page - 1) * $limit; 

// Consulta principal
$sql = "SELECT 
            vs.id_cotizacion,
            ct.factura,
            c.id_conductor,
            c.nombre_conductor,
            cs.detalle_servicios,
            cs.total_servicio,
            (IFNULL(SUM(vs.combustible),0) + IFNULL(SUM(vs.peaje),0) + IFNULL(SUM(vs.lavado),0) + 
             IFNULL(SUM(vs.viatico),0) + IFNULL(SUM(vs.neumatico),0) + IFNULL(SUM(vs.externo),0) + 
             IFNULL(SUM(vs.otros),0)) AS total_descuento,
            (cs.total_servicio - (IFNULL(SUM(vs.combustible),0) + IFNULL(SUM(vs.peaje),0) + 
             IFNULL(SUM(vs.lavado),0) + IFNULL(SUM(vs.viatico),0) + IFNULL(SUM(vs.neumatico),0) + 
             IFNULL(SUM(vs.externo),0) + IFNULL(SUM(vs.otros),0))) AS diferencia
        FROM varios_serv vs
        INNER JOIN conductores c 
            ON c.id_conductor = vs.id_conductor
        INNER JOIN cotizacion_servicios cs 
            ON vs.id_cotizacion = cs.id_cotizacion 
            AND vs.id_serv = cs.id_serv
        INNER JOIN cotizaciones ct 
            ON vs.id_cotizacion = ct.id_cotizacion
        WHERE ct.estado_servicio NOT IN ('Pendiente', 'Rechazada')";
        
// Filtros dinámicos
$conditions = [];
if (!empty($search)) {
    $conditions[] = "c.nombre_conductor LIKE '%$search%'";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $conditions[] = "ct.fecha_inicio BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}
if (count($conditions) > 0) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY vs.id_cotizacion, vs.id_serv, ct.factura, c.id_conductor, c.nombre_conductor, cs.detalle_servicios, cs.total_servicio";
$sql .= " ORDER BY vs.id_cotizacion DESC";


// Total de resultados para paginación
$total_sql = "SELECT COUNT(*) as total FROM (" . $sql . ") AS subquery"; 
$total_result = $conn->query($total_sql); 
$total_rows = $total_result->fetch_assoc()['total']; 
$total_pages = ceil($total_rows / $limit); 

$sql .= " LIMIT $limit OFFSET $offset"; 

$result = $conn->query($sql);

// Variables para acumular totales
$total_precio = 0;
$total_descuento = 0;
$total_diferencia = 0;
?>

<div class="container my-5">
    <h2 class="text-center mb-4">Listado de Conductores</h2>

    <form method="GET" action="listado_conductores.php" class="mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Buscar por conductor" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fecha_fin); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <a href="listado_conductores.php" class="btn btn-secondary">Limpiar</a>
            </div>
        </div>
    </form>
    
    <!-- Botón para exportar a CSV  -->

    <form action="exportar_csv.php" method="post">
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
        <input type="hidden" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
        <input type="hidden" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
        <button type="submit" class="btn btn-success">Exportar a CSV</button>
    </form>

    <?php if ($result && $result->num_rows > 0): ?>
        <table class="table table-bordered table-striped mt-3">
            <thead class="table-primary">
                <tr>
                    <th>Cotización</th>
                    <th>Factura</th>
                    <th>Conductor</th>
                    <th>Servicio</th>
                    <th>Valor Total Servicio</th>
                    <th>Descuento</th>
                    <th>Diferencia</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id_cotizacion']; ?></td>
                        <td><?php echo $row['factura']; ?></td>
                        <td><?php echo $row['nombre_conductor']; ?></td>
                        <td><?php echo $row['detalle_servicios']; ?></td>
                        <td class="text-end">$<?php echo number_format($row['total_servicio'], 0, ',', '.'); ?></td>
                        <td class="text-end">$<?php echo number_format($row['total_descuento'], 0, ',', '.'); ?></td>
                        <td class="text-end">$<?php echo number_format($row['diferencia'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php
                    $total_precio += $row['total_servicio'];
                    $total_descuento += $row['total_descuento'];
                    $total_diferencia += $row['diferencia'];
                    ?>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <th colspan="4" class="text-end">Totales:</th>
                    <th class="text-end">$<?php echo number_format($total_precio, 0, ',', '.'); ?></th>
                    <th class="text-end">$<?php echo number_format($total_descuento, 0, ',', '.'); ?></th>
                    <th class="text-end">$<?php echo number_format($total_diferencia, 0, ',', '.'); ?></th>
                </tr>
            </tfoot>
        </table>

     <!--   <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>-->

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
        <p class="text-center">No se encontraron cotizaciones.</p>
    <?php endif; ?>
</div>

<?php $conn->close(); ?>
<?php include 'content/footer.php'; ?>
