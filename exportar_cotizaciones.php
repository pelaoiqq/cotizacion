<?php
include 'content/connect.php';

// 1. OBTENER Y SANITIZAR FILTROS
// Usamos operador ternario clásico compatible con PHP 5.6
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin    = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Sanitización
$fecha_inicio_s = $conn->real_escape_string($fecha_inicio);
$fecha_fin_s    = $conn->real_escape_string($fecha_fin);
$search_s       = $conn->real_escape_string($search_query);

// 2. CONFIGURAR CABECERAS CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="exportar_cotizaciones_' . date('Ymd_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Crear puntero de salida
$output = fopen('php://output', 'w');

// BOM para que Excel reconozca caracteres especiales (tildes, ñ)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados de columnas (Sintaxis [] es compatible desde PHP 5.4)
fputcsv($output, [
    'N Coti', 
    'Factura', 
    'Conductor', 
    'Cliente', 
    'Inicio', 
    'Origen', 
    'Destino',
    'Servicios', 
    'Total Venta',
    'COMBUSTIBLE', 
    'VIATICO', 
    'PEAJES', 
    'TERCERO', 
    'LAVADO',
    'NEUMATICO', 
    'GANANCIA'
], ";"); 

// 3. CONSTRUCCIÓN DE LA CONSULTA SQL
$sql = "SELECT 
            c.id_cotizacion,
            c.factura,
            cl.nombre_cliente,
            c.fecha_inicio,
            c.origen,
            c.destino,
            c.estado_servicio,
            d.nombre_conductor,
            
            -- Subconsulta para el subtotal de servicios
            (
                SELECT SUM( (IFNULL(cs.precio, 0) * IFNULL(cs.cantidad, 0)) + IFNULL(cs.estadia, 0) )
                FROM cotizacion_servicios cs
                WHERE cs.id_cotizacion = c.id_cotizacion
            ) AS subtotal_servicios,
            
            c.descto_servicio,

            -- Subconsulta para nombres de servicios
            (
                SELECT GROUP_CONCAT(DISTINCT s.nombre_servicio SEPARATOR ' | ')
                FROM cotizacion_servicios cs
                LEFT JOIN servicios s ON cs.id_servicio = s.id_servicios
                WHERE cs.id_cotizacion = c.id_cotizacion
            ) AS nombres_servicios,

            -- Gastos (usamos IFNULL de SQL aquí, que es correcto)
            IFNULL(v.combustible, 0) as combustible,
            IFNULL(v.viatico, 0) as viatico,
            IFNULL(v.peaje, 0) as peaje,
            IFNULL(v.externo, 0) as externo,
            IFNULL(v.lavado, 0) as lavado,
            IFNULL(v.neumatico, 0) as neumatico,
            IFNULL(v.otros, 0) as otros

        FROM cotizaciones c
        INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente
        LEFT JOIN varios_serv v ON c.id_cotizacion = v.id_cotizacion
        LEFT JOIN conductores d ON v.id_conductor = d.id_conductor
        WHERE c.estado_servicio NOT IN ('Rechazada') "; 

// 4. APLICACIÓN DE FILTROS
if (!empty($fecha_inicio_s) && !empty($fecha_fin_s)) {
    $sql .= " AND c.fecha_inicio BETWEEN '$fecha_inicio_s' AND '$fecha_fin_s'";
}

if (!empty($search_s)) {
    $sql .= " AND cl.nombre_cliente LIKE '%$search_s%'";
}

$sql .= " GROUP BY c.id_cotizacion ORDER BY c.id_cotizacion DESC";

$result = $conn->query($sql);

// 5. ESCRIBIR DATOS
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        
        // --- CORRECCIÓN CLAVE PARA PHP 5.6 ---
        // Aseguramos que los valores sean números (float) para evitar errores
        $subtotal = floatval($row['subtotal_servicios']);
        $descuento = floatval($row['descto_servicio']);
        
        // Calculamos el total de venta
        $total_venta = $subtotal - $descuento;

        // Sumamos los gastos
        $total_gastos = floatval($row['combustible']) + 
                        floatval($row['viatico']) + 
                        floatval($row['peaje']) + 
                        floatval($row['externo']) + 
                        floatval($row['lavado']) + 
                        floatval($row['neumatico']) + 
                        floatval($row['otros']);

        // Calculamos Ganancia
        $ganancia = $total_venta - $total_gastos;

        // Formato de fecha
        $fecha_fmt = date("d-m-Y", strtotime($row['fecha_inicio']));

        fputcsv($output, [
            $row['id_cotizacion'],
            $row['factura'],
            $row['nombre_conductor'],
            $row['nombre_cliente'],
            $fecha_fmt,
            $row['origen'],
            $row['destino'],
            $row['nombres_servicios'],
            $total_venta,  
            $row['combustible'],
            $row['viatico'],
            $row['peaje'],
            $row['externo'],
            $row['lavado'],
            $row['neumatico'],
            $ganancia
        ], ";");
    }
}

fclose($output);
exit;
?>