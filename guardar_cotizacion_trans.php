<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- DATOS GENERALES ---
    $id_cliente = isset($_POST['id_cliente']) ? $_POST['id_cliente'] : '';
    $email_cliente = isset($_POST['email_cliente']) ? $_POST['email_cliente'] : '';
    $detalle_servicios = isset($_POST['descripcion_servicio']) ? strtoupper($_POST['descripcion_servicio']) : '';
    // $servicio_manual ya no se usa como variable única, sino dentro del array
    $origen = isset($_POST['origen']) ? strtoupper($_POST['origen']) : '';
    $destino = isset($_POST['destino']) ? strtoupper($_POST['destino']) : '';
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_termino = isset($_POST['fecha_termino']) ? $_POST['fecha_termino'] : '';
    $estado_servicio = "Pendiente";
    $area_servicios = "Transporte";
    $forma_pago = isset($_POST['forma_pago']) ? $_POST['forma_pago'] : null;

    // Calcular total global
    $total_servicios_array = isset($_POST['total_servicios']) ? $_POST['total_servicios'] : array();
    $total_global = array_sum($total_servicios_array);

    // 1. INSERTAR EN COTIZACIONES (CABECERA)
    $sql_cotizaciones = "INSERT INTO cotizaciones (area_servicios, id_cliente, email_cliente, detalle_servicios, origen, destino, fecha_inicio, fecha_termino, estado_servicio, total, total_servicio, forma_pago) 
                    VALUES ('$area_servicios', '$id_cliente', '$email_cliente', '$detalle_servicios', '$origen', '$destino', '$fecha_inicio', '$fecha_termino', '$estado_servicio', '$total_global', '$total_global', '$forma_pago')";

    if ($conn->query($sql_cotizaciones)) {
        $id_cotizacion = $conn->insert_id;

        // Arrays de la parte superior (Servicios comerciales)
        $cantidades = $_POST['cantidades']; 
        $nombres_servicios = $_POST['servicios'];   
        $valores_unitarios = $_POST['valores'];       

        // Arrays de la parte inferior (Recursos/Conductores)
        $conductor_ids = isset($_POST['conductor_id']) ? $_POST['conductor_id'] : array();
        $camion_ids = isset($_POST['camion_id']) ? $_POST['camion_id'] : array();
        $rampla_ids = isset($_POST['rampla_id']) ? $_POST['rampla_id'] : array();
        $camabaja_ids = isset($_POST['camabaja_id']) ? $_POST['camabaja_id'] : array();

        // Contadores
        $id_serv_counter = 1; // Contador lógico de servicios (1, 2, 3...)
        $resource_index = 0;  // Índice para recorrer los arrays de conductores (0, 1, 2...)

        // 2. BUCLE PRINCIPAL: Recorremos los Items de Servicio
        foreach ($cantidades as $index => $qty) {
            $nombre_servicio = $nombres_servicios[$index];
            $precio_unitario = $valores_unitarios[$index];
            $cantidad_item = intval($qty);

            // BUCLE DE EXPANSIÓN: Si cantidad es 2, damos 2 vueltas
            for ($i = 0; $i < $cantidad_item; $i++) {
                
                // A. Insertar en cotizacion_servicios
                // Nota: Insertamos individualmente (cantidad 1) para que cada id_serv tenga su fila
                // Si prefieres mantener agrupado, avísame, pero para trazabilidad es mejor desglosar.
                $total_linea = $precio_unitario * 1; 
                $id_servicio_fk = 3; // Valor fijo que tenías en tu código original

                $sql_servicios = "INSERT INTO cotizacion_servicios (id_cotizacion, id_servicio, detalle_servicios, servicio_manual, cantidad, precio, total_servicio, id_serv) 
                                VALUES ('$id_cotizacion', '$id_servicio_fk', '$detalle_servicios', '$nombre_servicio', '1', '$precio_unitario', '$total_linea', '$id_serv_counter')";

                if (!$conn->query($sql_servicios)) {
                    die("Error al insertar en cotizacion_servicios: " . $conn->error);
                }

                // B. Obtener recursos (Conductores/Camiones) si existen disponibles
                // Verificamos si hay un conductor seleccionado en la posición actual del índice de recursos
                $c_id = isset($conductor_ids[$resource_index]) && !empty($conductor_ids[$resource_index]) ? $conductor_ids[$resource_index] : "NULL";
                $cam_id = isset($camion_ids[$resource_index]) && !empty($camion_ids[$resource_index]) ? $camion_ids[$resource_index] : "NULL";
                $ramp_id = isset($rampla_ids[$resource_index]) && !empty($rampla_ids[$resource_index]) ? $rampla_ids[$resource_index] : "NULL";
                $baja_id = isset($camabaja_ids[$resource_index]) && !empty($camabaja_ids[$resource_index]) ? $camabaja_ids[$resource_index] : "NULL";

                // C. Insertar en varios_serv (Tabla de logística)
                // Usamos "NULL" string para SQL si no hay dato, o el numero si hay dato.
                $sql_varios = "INSERT INTO varios_serv (id_cotizacion, id_conductor, id_camiones, id_rampla, id_camabaja, id_serv) 
                               VALUES ($id_cotizacion, $c_id, $cam_id, $ramp_id, $baja_id, $id_serv_counter)";
                
                if (!$conn->query($sql_varios)) {
                     die("Error al insertar en varios_serv: " . $conn->error);
                }

                // Incrementamos contadores
                $id_serv_counter++; 
                $resource_index++;  // Nos movemos al siguiente conductor disponible
            }
        }

        $conn->commit();
        $_SESSION['success'] = "Cotización guardada exitosamente.";
        $_SESSION['id_cotizacion'] = $id_cotizacion;
        header("Location: confirmacion.php");
        exit();

    } else {
        die("Error al insertar en cotizaciones: " . $conn->error);
    }
} else {
    $_SESSION['error'] = "Método de solicitud no válido.";
    header("Location: listado_cotizaciones.php");
}
?>