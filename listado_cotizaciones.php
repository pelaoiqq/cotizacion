<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<?php
include 'content/header.php';
include 'content/connect.php'; // Asegúrate que $conn se inicializa aquí
require('fpdf/fpdf.php');

// Obtener parámetros de búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$filtrar_rechazadas = isset($_GET['filtrar_rechazadas']) ? $_GET['filtrar_rechazadas'] : 'on'; // 'on' por defecto
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; //para paginacion
$limit = 25; // Número de cotizaciones por página //para paginacion
$offset = ($page - 1) * $limit; //para paginacion

// Función para obtener el nombre del mes en español
function obtenerNombreMes($fecha) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    $mesNumero = (int)date('n', strtotime($fecha));
    return $meses[$mesNumero];
}

// MODIFICACIÓN PRINCIPAL: Consulta SQL
$sql = "SELECT
    c.id_cotizacion,
    c.area_servicios,
    cl.nombre_cliente,
    cl.nombre_contacto,
    cl.telefono_contacto,
    c.fecha_inicio,
    c.fecha_termino,
    c.factura,  -- Tomado de la tabla cotizaciones
    c.guia,     -- Tomado de la tabla cotizaciones
    SUM(cs_details.total_servicio_item) AS total_servicio, -- Suma de los totales de cada ítem de servicio
    c.created_at,
    c.origen,
    c.destino,
    c.detalle_servicios,
    GROUP_CONCAT(DISTINCT s.nombre_servicio SEPARATOR ', ') AS servicios,
    SUM(cs_details.precio_item) AS total_precio, -- Suma de los precios base de los items
    SUM(cs_details.estadia_item) AS total_estadia, -- Suma de las estadías de los items
    c.descto_servicio AS total_descuento,
    (SUM(cs_details.total_servicio_item) - IFNULL(c.descto_servicio, 0)) AS total, -- Total final
    c.estado_servicio
FROM
    cotizaciones c
INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente
INNER JOIN (
    -- Subconsulta para agregar los detalles de cada servicio en una cotización
    SELECT
        id_cotizacion,
        id_servicio,
        SUM(precio) AS precio_item,
        SUM(estadia) AS estadia_item,
        SUM((IFNULL(precio,0) * IFNULL(cantidad,0)) + IFNULL(estadia,0)) AS total_servicio_item -- Total para este item de servicio específico
    FROM cotizacion_servicios
    GROUP BY id_cotizacion, id_servicio -- Agrupa por cotización y servicio para obtener el total de cada uno
) cs_details ON c.id_cotizacion = cs_details.id_cotizacion
INNER JOIN servicios s ON cs_details.id_servicio = s.id_servicios
WHERE 1 = 1
";

// Filtrar por cliente si se proporciona un término de búsqueda
if (!empty($search)) {
    // Escapar el término de búsqueda para prevenir SQL injection
    $escaped_search = $conn->real_escape_string($search);
    $sql .= " AND cl.nombre_cliente LIKE '%$escaped_search%'";
}

// Filtrar por rango de fechas
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    // Escapar las fechas para prevenir SQL injection
    $escaped_fecha_inicio = $conn->real_escape_string($fecha_inicio);
    $escaped_fecha_fin = $conn->real_escape_string($fecha_fin);
    $sql .= " AND c.fecha_inicio BETWEEN '$escaped_fecha_inicio' AND '$escaped_fecha_fin'";
}

// Modifica el GROUP BY
$sql .= " GROUP BY c.id_cotizacion
          ORDER BY c.created_at DESC";

// Guardar la consulta base sin LIMIT para el conteo total
$sql_for_total = $sql;

// Aplicar limit y offset para la paginación
$sql .= " LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

// Obtener total de resultados para paginación usando la consulta guardada antes del LIMIT
// Es importante que $sql_for_total tenga los mismos JOINs y WHEREs que $sql principal
// y el mismo GROUP BY para que el conteo sea preciso.
$total_sql = "SELECT COUNT(*) as total FROM (
    SELECT c.id_cotizacion -- Solo necesitamos contar las cotizaciones agrupadas 
    FROM
        cotizaciones c
    INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente
    INNER JOIN (
        SELECT
            id_cotizacion,
            id_servicio
        FROM cotizacion_servicios
        GROUP BY id_cotizacion, id_servicio
    ) cs_details ON c.id_cotizacion = cs_details.id_cotizacion
    INNER JOIN servicios s ON cs_details.id_servicio = s.id_servicios
    WHERE 1 = 1
";

if (!empty($search)) {
    $escaped_search = $conn->real_escape_string($search);
    $total_sql .= " AND cl.nombre_cliente LIKE '%$escaped_search%'";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $escaped_fecha_inicio = $conn->real_escape_string($fecha_inicio);
    $escaped_fecha_fin = $conn->real_escape_string($fecha_fin);
    $total_sql .= " AND c.fecha_inicio BETWEEN '$escaped_fecha_inicio' AND '$escaped_fecha_fin'";
}
$total_sql .= " GROUP BY c.id_cotizacion
) AS subquery_count";

$total_result = $conn->query($total_sql);
$total_rows = 0;
if ($total_result && $total_result->num_rows > 0) {
   $total_rows_data = $total_result->fetch_assoc();
   $total_rows = $total_rows_data['total'];
}
$total_pages = ceil($total_rows / $limit);


$cotizacionesPorMes = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mesAnio = date('Y-m', strtotime($row['created_at']));
        $cotizacionesPorMes[$mesAnio][] = $row;
    }
}
?>

<div class="container my-5 small">
    <h2 class="text-center mb-4">Listado de Cotizaciones</h2>

    <form method="GET" action="listado_cotizaciones.php" class="mb-4">
        <div class="row g-4">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Buscar por cliente" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fecha_fin); ?>">
            </div>
                <div class="col-md-5">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <a href="listado_cotizaciones.php" class="btn btn-secondary">Limpiar</a>
                    <div class="form-check d-inline-block ms-2">
                        <input class="form-check-input" type="checkbox" id="filterRejected" checked>
                        <label class="form-check-label" for="filterRejected">
                            Ocultar Cotizaciones Rechazadas
                        </label>
                    </div>
                </div>
        </div>
    </form>

        <!-- Aquí agregas el botón CSV -->
    <form method="GET" action="exportar_cotizaciones.php">
        <input type="hidden" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
        <input type="hidden" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-success mb-3">Exportar a CSV</button>
    </form>

    <?php if (!empty($cotizacionesPorMes)): ?>
        <?php foreach ($cotizacionesPorMes as $mesAnio => $cotizaciones): ?>
            <h4 class="mt-4"><?php echo obtenerNombreMes($mesAnio . '-01') . ' ' . date('Y', strtotime($mesAnio . '-01')); ?></h4>
            <table class="table table-bordered table-striped mt-3">
            <thead class="table-primary">
                    <tr>
                        <th>Nº Coti</th>
                        <th style="width: 50px;">Factura</th>
                        <th>Guia</th>
                        <th style="width: 200px;">Cliente</th>
                        <th>Inicio Servicio</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Servicios</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $total_precio_mes = 0; ?>
                    <?php foreach ($cotizaciones as $row): ?>
                        <?php
                            // Determinar la clase de la fila según el estado del servicio
                            $row_class = '';
                            if ($row['estado_servicio'] == 'Finalizado') {
                                $row_class = 'table-warning'; // Verde
                            } elseif ($row['estado_servicio'] == 'Rechazada') {
                                $row_class = 'table-danger'; // Rojo
                            }
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo $row['id_cotizacion']; ?></td>
                            <td class="text-end"><?php echo $row['factura']; ?></td>
                            <td><?php echo $row['guia']; ?></td>
                            <td><?php echo $row['nombre_cliente']; ?></td>
                            <td><?php
                                $fecha = new DateTime($row['fecha_inicio']);
                                echo $fecha->format('d-m-Y');
                            ?></td>
                            <td><?php echo $row['origen']; ?></td>
                            <td><?php echo $row['destino']; ?></td>
                            <td><?php echo $row['detalle_servicios']; ?></td>
                            <td class="text-end">$<?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                            <td>
                                <form method="POST" action="actualizar_estado.php" class="d-inline">
                                    <select name="nuevo_estado" class="form-select form-select-sm" required>
                                        <option value="Pendiente" <?php if ($row['estado_servicio'] == 'Pendiente') echo 'selected'; ?>>Pendiente</option>
                                        <option value="Aprobada" <?php if ($row['estado_servicio'] == 'Aprobada') echo 'selected'; ?>>Aprobada</option>
                                        <option value="Rechazada" <?php if ($row['estado_servicio'] == 'Rechazada') echo 'selected'; ?>>Rechazada</option>
                                        <option value="Finalizado" <?php if ($row['estado_servicio'] == 'Finalizado') echo 'selected'; ?>>Finalizado</option>
                                    </select>
                                    <input type="hidden" name="id_cotizacion" value="<?php echo $row['id_cotizacion']; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary mt-2">Actualizar</button>
                                </form>
                            </td>
                            <td>
                                <form method="GET" action="agregar_varios.php" class="d-inline">
                                    <!-- Botón Editar -->
                                <a href="editar_cotizacion.php?id_cotizacion=<?php echo $row['id_cotizacion']; ?>" 
                                class="btn btn-warning btn-sm d-flex justify-content-center align-items-center text-white mb-2">
                                Editar
                                </a>
                                </form>

                                <form method="GET" id="form-<?php echo $row['id_cotizacion']; ?>" target="_blank">
                                    <input type="hidden" name="id_cotizacion" value="<?php echo $row['id_cotizacion']; ?>">
                                    <input type="hidden" name="area_servicios" value="<?php echo $row['area_servicios']; ?>">
                                    <button type="button" class="btn btn-sm btn-success mb-2" onclick="handlePrint(<?php echo $row['id_cotizacion']; ?>, '<?php echo $row['area_servicios']; ?>')">
                                        Imprimir
                                    </button>
                                </form>
                                <form method="GET" action="agregar_varios.php" class="d-inline">
                                    <input type="hidden" name="id_cotizacion" value="<?php echo $row['id_cotizacion']; ?>">
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-primary"
                                        <?php if ($row['estado_servicio'] == 'Pendiente' || $row['estado_servicio'] == 'Rechazada') echo 'disabled'; ?>>
                                        Agregar
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php
                        // Acumular totales solo si el estado no es Rechazada
                        if ($row['estado_servicio'] !== 'Rechazada') {
                            $total_precio_mes += $row['total'];
                        }
                        ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <th colspan="8" class="text-end">Total del Mes:</th>
                        <th class="text-end">$<?php echo number_format($total_precio_mes, 0, ',', '.'); ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        <?php endforeach; ?>



       <!-- <nav> <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>"><?php echo $i; ?></a>
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
        <p class="text-center">No se encontraron cotizaciones en el rango de fechas seleccionado.</p>
    <?php endif; ?>
</div>

<?php $conn->close(); ?>
<?php include 'content/footer.php'; ?>
<script>
    function handlePrint(idCotizacion, areaServicios) {
        let actionUrl;

        switch (areaServicios) {
            case "Servicio":
                actionUrl = "generar_pdf_serv.php";
                break;
            case "Transporte":
                actionUrl = "generar_pdf_trans.php";
                break;
            case "Arriendo":
                actionUrl = "generar_pdf_arri.php";
                break;
            case "Ventas":
                actionUrl = "generar_pdf_ventas.php";
                break;
            default:
                alert("Área de servicio no válida.");
                return;
        }

        // Obtener el formulario correspondiente
        const form = document.getElementById(`form-${idCotizacion}`);
        form.action = actionUrl;
        form.submit();
    }

    document.addEventListener("DOMContentLoaded", function() {
        const filterCheckbox = document.getElementById("filterRejected");

        function filterRows() {
            document.querySelectorAll("tr.table-danger").forEach(row => {
                row.style.display = filterCheckbox.checked ? "none" : "";
            });
        }

        // Aplicar el filtro al cargar la página
        filterRows();

        // Cambiar visibilidad al hacer clic en el checkbox
        filterCheckbox.addEventListener("change", filterRows);
    });
</script>