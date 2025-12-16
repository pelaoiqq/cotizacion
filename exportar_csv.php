<?php
include('content/connect.php');

// Parámetros
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
$fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';

// Consulta
$sql = "SELECT 
            vs.id_cotizacion,
            ct.factura,
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

// Filtros
if (!empty($search)) {
    $sql .= " AND c.nombre_conductor LIKE '%$search%'";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $sql .= " AND ct.fecha_inicio BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

$sql .= " GROUP BY vs.id_cotizacion, vs.id_serv, ct.factura, c.nombre_conductor, cs.detalle_servicios, cs.total_servicio
          ORDER BY vs.id_cotizacion DESC";

$result = $conn->query($sql);

// Nombre archivo
$fecha_hora = date('Ymd_His');
$filename = "reporte_conductores_$fecha_hora.csv";

// Cabeceras para descarga
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");

// Abrir salida
$output = fopen('php://output', 'w');

// Encabezados
fputcsv($output, ['Cotización', 'Factura', 'Conductor', 'Servicio', 'Valor Total Servicio', 'Descuento', 'Diferencia']);

// Datos
$total_precio = 0;
$total_descuento = 0;
$total_diferencia = 0;

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id_cotizacion'],
        $row['factura'],
        $row['nombre_conductor'],
        $row['detalle_servicios'],
        number_format($row['total_servicio'], 0, ',', '.'),
        number_format($row['total_descuento'], 0, ',', '.'),
        number_format($row['diferencia'], 0, ',', '.')
    ]);

    $total_precio += $row['total_servicio'];
    $total_descuento += $row['total_descuento'];
    $total_diferencia += $row['diferencia'];
}

    // Fila final con totales
    fputcsv($output, ['', '', '', 'Totales', 
        number_format($total_precio, 0, ',', '.'),
        number_format($total_descuento, 0, ',', '.'),
        number_format($total_diferencia, 0, ',', '.')
    ]);

fclose($output);
exit;
?>
