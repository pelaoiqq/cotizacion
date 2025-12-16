<?php
include 'content/connect.php';

// Obtener los filtros desde la URL
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Crear archivo CSV 
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="listado_viaticos_peaje_' . date('Ymd_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Crear el archivo en la salida
$output = fopen('php://output', 'w');

// Escribir encabezados
fputcsv($output, ['Cotización', 'Fecha Inicio', 'Viatico', 'Peaje', 'Total']);

// Consulta SQL
$sql = "SELECT
    ct.id_cotizacion,
    ct.fecha_inicio,
    SUM(vs.peaje) AS total_peaje,
    SUM(vs.viatico) AS total_viatico
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

$result = $conn->query($sql);

// Escribir datos al CSV
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $total = $row['total_viatico'] + $row['total_peaje'];
        fputcsv($output, [
            $row['id_cotizacion'],
            date("d-m-Y", strtotime($row['fecha_inicio'])),
            number_format($row['total_viatico'], 0, ',', '.'),
            number_format($row['total_peaje'], 0, ',', '.'),
            number_format($total, 0, ',', '.')
        ]);
    }
}

fclose($output);
exit;
?>
