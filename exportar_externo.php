<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

// Obtener fechas desde GET
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Encabezados para forzar descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="listado_pagos_externos_' . date('Ymd_His') . '.csv"');

// Abrir salida
$output = fopen('php://output', 'w');

// Encabezado de columnas
fputcsv($output, ['Cotización', 'Fecha Inicio', 'Pagos Externos', 'Total']);

// Consulta
$sql = "SELECT
    ct.id_cotizacion,
    ct.fecha_inicio,
    SUM(vs.externo) AS total_externo
FROM cotizaciones ct
INNER JOIN varios_serv vs ON vs.id_cotizacion = ct.id_cotizacion
WHERE ct.estado_servicio NOT IN ('Rechazada', 'Pendiente')";

// Filtro por fecha si está definido
$conditions = [];
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $conditions[] = "ct.fecha_inicio BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}
if (count($conditions) > 0) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY ct.id_cotizacion, ct.fecha_inicio ORDER BY ct.fecha_inicio DESC";

$result = $conn->query($sql);

// Procesar filas
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $total = $row['total_externo'];
        fputcsv($output, [
            $row['id_cotizacion'],
            date("d-m-Y", strtotime($row['fecha_inicio'])),
            number_format($row['total_externo'], 0, ',', '.'),
            number_format($total, 0, ',', '.')
        ]);
    }
} else {
    fputcsv($output, ['Sin resultados']);
}

fclose($output);
$conn->close();
exit();



