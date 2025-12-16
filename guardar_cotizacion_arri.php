<?php
// Configuración de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Acceso no autorizado.";
    header("Location: listado_cotizaciones.php");
    exit();
}

// --- 1. Recibir Datos Generales ---
$id_cliente     = isset($_POST['id_cliente']) ? intval($_POST['id_cliente']) : 0;
$email_cliente  = isset($_POST['email_cliente']) ? $_POST['email_cliente'] : '';
$origen         = isset($_POST['origen']) ? strtoupper($_POST['origen']) : '';
$destino        = isset($_POST['destino']) ? strtoupper($_POST['destino']) : '';
$fecha_inicio   = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
$fecha_termino  = !empty($_POST['fecha_termino']) ? $_POST['fecha_termino'] : null;
$forma_pago     = isset($_POST['forma_pago']) ? $_POST['forma_pago'] : '0';

// Moneda y UF
$moneda         = isset($_POST['moneda']) ? $_POST['moneda'] : 'CLP';
$valor_uf_raw   = isset($_POST['valor_uf']) ? $_POST['valor_uf'] : '0.00';
$valor_uf       = str_replace(',', '.', $valor_uf_raw); 

// Datos fijos
$area_servicios  = "Arriendo";
$estado_servicio = "Pendiente";
$detalle_servicios = isset($_POST['servicios'][0]) ? strtoupper($_POST['servicios'][0]) : 'ARRIENDO DE EQUIPOS';

// Calcular Totales Globales
$total_servicios_arr = isset($_POST['total_servicios']) ? $_POST['total_servicios'] : [];
$total_global = 0;
if (is_array($total_servicios_arr)) {
    foreach ($total_servicios_arr as $monto) {
        $total_global += floatval($monto);
    }
}

// INICIAR TRANSACCIÓN
$conn->autocommit(FALSE);

try {
    // -------------------------------------------------------------------------
    // PASO 1: Insertar Cotización (Cabecera)
    // -------------------------------------------------------------------------
    $sql_coti = "INSERT INTO cotizaciones (
                    area_servicios, id_cliente, email_cliente, detalle_servicios, 
                    origen, destino, fecha_inicio, fecha_termino, estado_servicio, 
                    total, total_servicio, forma_pago, moneda, valor_uf
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql_coti);
    $stmt->bind_param("sisssssssddssd", 
        $area_servicios, $id_cliente, $email_cliente, $detalle_servicios, 
        $origen, $destino, $fecha_inicio, $fecha_termino, $estado_servicio, 
        $total_global, $total_global, $forma_pago, $moneda, $valor_uf
    );

    if (!$stmt->execute()) throw new Exception("Error ejecutando cotización: " . $stmt->error);
    $id_cotizacion = $conn->insert_id;
    $stmt->close();

    // -------------------------------------------------------------------------
    // PASO 2 Y 3: Insertar Servicios y Vehículos SINCRONIZADOS
    // -------------------------------------------------------------------------
    
    // Arrays de Servicios (Comercial)
    $cantidades = isset($_POST['cantidades']) ? $_POST['cantidades'] : [];
    $servicios  = isset($_POST['servicios']) ? $_POST['servicios'] : [];
    $valores    = isset($_POST['valores']) ? $_POST['valores'] : [];
    
    // Arrays de Recursos (Vehículos)
    $camion_ids   = isset($_POST['camion_id']) ? $_POST['camion_id'] : [];
    $rampla_ids   = isset($_POST['rampla_id']) ? $_POST['rampla_id'] : [];
    $camabaja_ids = isset($_POST['camabaja_id']) ? $_POST['camabaja_id'] : [];
    $conductor_ids= isset($_POST['conductor_id']) ? $_POST['conductor_id'] : [];

    // Preparar sentencias
    $sql_det = "INSERT INTO cotizacion_servicios (id_cotizacion, id_servicio, detalle_servicios, servicio_manual, cantidad, precio, id_serv, total_servicio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_det = $conn->prepare($sql_det);

    $sql_varios = "INSERT INTO varios_serv (id_cotizacion, id_conductor, id_camiones, id_rampla, id_camabaja, id_serv) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_var = $conn->prepare($sql_varios);

    $id_serv_contador = 1; // Contador único para vincular precio con camión
    $resource_index = 0;   // Índice para recorrer los arrays de camiones

    // Recorremos los Items de Servicio Agregados
    foreach ($cantidades as $index => $qty) {
        $nom_servicio = strtoupper($servicios[$index]);
        $precio_unit  = floatval($valores[$index]);
        $cantidad_item = intval($qty); // Ej: 2

        // BUCLE DE EXPANSIÓN: Si cantidad es 2, insertamos 2 filas
        for ($i = 0; $i < $cantidad_item; $i++) {
            
            // A. Insertar Precio Individual (cotizacion_servicios)
            $id_servicio_fijo = 3; 
            $cant_unitaria = 1; 
            $total_linea = $precio_unit * $cant_unitaria;

            $stmt_det->bind_param("iisssdid", 
                $id_cotizacion, $id_servicio_fijo, $detalle_servicios, 
                $nom_servicio, $cant_unitaria, $precio_unit, 
                $id_serv_contador, $total_linea
            );
            
            if (!$stmt_det->execute()) throw new Exception("Error al insertar servicio.");

            // B. Insertar Vehículo Correspondiente (varios_serv)
            // Verificamos si existe un camión seleccionado para esta posición
            $id_camion   = !empty($camion_ids[$resource_index]) ? intval($camion_ids[$resource_index]) : null;
            $id_conductor= !empty($conductor_ids[$resource_index]) ? intval($conductor_ids[$resource_index]) : null;
            $id_rampla   = !empty($rampla_ids[$resource_index]) ? intval($rampla_ids[$resource_index]) : null;
            $id_camabaja = !empty($camabaja_ids[$resource_index]) ? intval($camabaja_ids[$resource_index]) : null;

            // Insertamos en varios_serv (incluso si es NULL, para mantener el id_serv ocupado)
            $stmt_var->bind_param("iiiiii", 
                $id_cotizacion, $id_conductor, $id_camion, 
                $id_rampla, $id_camabaja, $id_serv_contador
            );

            if (!$stmt_var->execute()) throw new Exception("Error insertando vehículo.");

            // Avanzamos contadores
            $id_serv_contador++;
            $resource_index++;
        }
    }

    $stmt_det->close();
    $stmt_var->close();

    // CONFIRMAR TRANSACCIÓN
    $conn->commit();
    
    $_SESSION['success'] = "Cotización de Arriendo guardada correctamente.";
    $_SESSION['id_cotizacion'] = $id_cotizacion;
    header("Location: listado_cotizaciones.php?msg=guardado"); 
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
$conn->close();
?>