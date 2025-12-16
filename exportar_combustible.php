<?php
session_start();
include 'content/connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$patente = isset($_GET['patente']) ? $conn->real_escape_string(trim($_GET['patente'])) : '';
$conductor = isset($_GET['conductor']) ? $conn->real_escape_string(trim($_GET['conductor'])) : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

$sql = "SELECT ct.id_cotizacion, ct.created_at, vs.combustible, c.patente_camion, cd.nombre_conductor
        FROM cotizaciones ct 
        INNER JOIN varios_serv vs ON vs.id_cotizacion = ct.id_cotizacion 
        INNER JOIN conductores cd ON vs.id_conductor = cd.id_conductor
        INNER JOIN camiones c ON vs.id_camiones = c.id_camiones
        WHERE ct.estado_servicio NOT IN ('Rechazada', 'Pendiente')";

if (!empty($conductor)) {
    $sql .= " AND cd.nombre_conductor LIKE '%$conductor%'";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $sql .= " AND ct.created_at BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}
if (!empty($patente)) {
    $sql .= " AND c.patente_camion LIKE '%$patente%'";
}

$sql .= " ORDER BY ct.created_at DESC, vs.id_cotizacion DESC";

$result = $conn->query($sql);

// Crear archivo CSV 
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="listado_combustible_' . date('Ymd_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fputcsv($output, ['Cotización', 'Fecha Creación', 'Patente Camión', 'Conductor', 'Combustible']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id_cotizacion'],
        date("d-m-Y", strtotime($row['created_at'])),
        $row['patente_camion'],
        $row['nombre_conductor'],
        $row['combustible']
    ]);
}

fclose($output);
$conn->close();
exit;
