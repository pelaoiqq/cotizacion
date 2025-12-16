<?php
include("content/connect.php");

$mesFiltro = isset($_GET['mes']) ? $_GET['mes'] : null;
$condicion = "";
$params = [];

if ($mesFiltro) {
    $condicion = "WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
    $params[] = $mesFiltro;
} else {
    // Filtro por los últimos 5 meses
    $fechaLimite = date('Y-m-d', strtotime('-4 months'));
    $condicion = "WHERE created_at >= ?";
    $params[] = $fechaLimite;
}

// Cotizaciones por mes
$sqlMeses = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes, COUNT(*) AS total FROM cotizaciones " .
            $condicion . " GROUP BY mes ORDER BY mes";
$stmt = $conn->prepare($sqlMeses);
$stmt->bind_param("s", $params[0]);
$stmt->execute();
$result = $stmt->get_result();
$meses = $totales_mes = [];
while ($row = $result->fetch_assoc()) {
    $meses[] = $row['mes'];
    $totales_mes[] = (int)$row['total'];
}
$stmt->close();

// Estados de Servicio
$sqlEstados = "SELECT estado_servicio, COUNT(*) AS total FROM cotizaciones " .
              $condicion . " GROUP BY estado_servicio";
$stmt = $conn->prepare($sqlEstados);
$stmt->bind_param("s", $params[0]);
$stmt->execute();
$result = $stmt->get_result();
$estados = $totales_estado = [];
while ($row = $result->fetch_assoc()) {
    $estados[] = $row['estado_servicio'];
    $totales_estado[] = (int)$row['total'];
}
$stmt->close();

// Servicios más cotizados (area_servicios)
$sqlServicios = "SELECT area_servicios, COUNT(*) AS total FROM cotizaciones " .
                $condicion . " GROUP BY area_servicios";
$stmt = $conn->prepare($sqlServicios);
$stmt->bind_param("s", $params[0]);
$stmt->execute();
$result = $stmt->get_result();
$servicios = $totales_servicio = [];
while ($row = $result->fetch_assoc()) {
    $servicios[] = $row['area_servicios'];
    $totales_servicio[] = (int)$row['total'];
}
$stmt->close();

// Ingresos por mes
$sqlIngresos = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes, SUM(total) AS total FROM cotizaciones " .
               $condicion . " GROUP BY mes ORDER BY mes";
$stmt = $conn->prepare($sqlIngresos);
$stmt->bind_param("s", $params[0]);
$stmt->execute();
$result = $stmt->get_result();
$meses_ingresos = $totales_ingresos = [];
while ($row = $result->fetch_assoc()) {
    $meses_ingresos[] = $row['mes'];
    $totales_ingresos[] = (float)$row['total'];
}
$stmt->close();

echo json_encode([
    'meses' => $meses,
    'totales_mes' => $totales_mes,
    'estados' => $estados,
    'totales_estado' => $totales_estado,
    'servicios' => $servicios,
    'totales_servicio' => $totales_servicio,
    'meses_ingresos' => $meses_ingresos,
    'totales_ingresos' => $totales_ingresos
]);
?>
