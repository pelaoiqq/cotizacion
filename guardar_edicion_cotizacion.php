<?php
// Configuración de errores para depuración (puedes comentarlo en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validar ID de cotización
    if (!isset($_POST['cotizacion_id'])) {
        die("Error: ID de cotización no recibido.");
    }

    $cotizacion_id = intval($_POST['cotizacion_id']);
    
    // --- 1. ACTUALIZAR DATOS GENERALES ---
    $forma_pago = isset($_POST['forma_pago']) ? $_POST['forma_pago'] : '';
    $descripcion_servicio = isset($_POST['descripcion_servicio']) ? strtoupper($_POST['descripcion_servicio']) : '';
    $origen = isset($_POST['origen']) ? strtoupper(trim($_POST['origen'])) : '';
    $destino = isset($_POST['destino']) ? strtoupper(trim($_POST['destino'])) : '';
    $fecha_inicio = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
    $fecha_termino = !empty($_POST['fecha_termino']) ? $_POST['fecha_termino'] : null;

    // --- NUEVO: Capturar Moneda y UF ---
    $moneda = isset($_POST['moneda']) ? $_POST['moneda'] : 'CLP';
    $valor_uf_raw = isset($_POST['valor_uf']) ? $_POST['valor_uf'] : '0.00';
    $valor_uf = str_replace(',', '.', $valor_uf_raw); // Asegurar formato decimal para BD

    // Consulta SQL actualizada para incluir moneda y valor_uf
    $sql = "UPDATE cotizaciones SET forma_pago = ?, detalle_servicios = ?, origen = ?, destino = ?, fecha_inicio = ?, fecha_termino = ?, moneda = ?, valor_uf = ? WHERE id_cotizacion = ?";
    $stmt = $conn->prepare($sql);
    
    // "sssssssd i" -> 7 strings, 1 double (decimal), 1 int
    $stmt->bind_param("sssssssdi", $forma_pago, $descripcion_servicio, $origen, $destino, $fecha_inicio, $fecha_termino, $moneda, $valor_uf, $cotizacion_id);
    $stmt->execute();
    $stmt->close();

    // --- 2. ACTUALIZAR ÍTEMS DE SERVICIO EXISTENTES ---
    if (!empty($_POST['ids_existentes'])) {
        foreach ($_POST['ids_existentes'] as $i => $detalle_id) {
            $cantidad = intval($_POST['cantidades_existentes'][$i]);
            $descripcion = strtoupper(trim($_POST['descripciones_existentes'][$i]));
            $precio = floatval($_POST['precios_existentes'][$i]);
            // Recalcular total para asegurar consistencia
            $total = $cantidad * $precio;

            $sql = "UPDATE cotizacion_servicios SET cantidad = ?, servicio_manual = ?, precio = ?, total_servicio = ? WHERE id_cot_servicios = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isddi", $cantidad, $descripcion, $precio, $total, $detalle_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- 3. ACTUALIZAR VEHÍCULOS ASIGNADOS EXISTENTES (VARIOS_SERV) ---
    // Aquí nos aseguramos de actualizar Conductor, Camión, Rampla y Cama Baja
    if (!empty($_POST['id_serv_varios_existente'])) {
        foreach ($_POST['id_serv_varios_existente'] as $i => $id_serv_varios) {
            
            // Función auxiliar para manejar vacíos como NULL
            $fn_val = function($key) use ($i) {
                return (!empty($_POST[$key][$i])) ? intval($_POST[$key][$i]) : null;
            };

            $conductor_id = $fn_val('conductor_id_existente');
            $camion_id    = $fn_val('camion_id_existente');
            $rampla_id    = $fn_val('rampla_id_existente');
            $camabaja_id  = $fn_val('camabaja_id_existente');

            $sql = "UPDATE varios_serv SET 
                    id_conductor = ?, 
                    id_camiones = ?, 
                    id_rampla = ?, 
                    id_camabaja = ? 
                    WHERE id_serv = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiiii", $conductor_id, $camion_id, $rampla_id, $camabaja_id, $id_serv_varios);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- 4. ELIMINAR ÍTEMS MARCADOS ---
    if (!empty($_POST['eliminar_ids'])) {
        foreach ($_POST['eliminar_ids'] as $idEliminar) {
            // Primero eliminar de varios_serv si existe relación (opcional, depende de tu estructura, pero seguro borrar servicios)
            // Borrar de cotizacion_servicios
            $stmt = $conn->prepare("DELETE FROM cotizacion_servicios WHERE id_cot_servicios = ?");
            $stmt->bind_param("i", $idEliminar);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- 5. AGREGAR NUEVOS SERVICIOS COMPLEJOS (CON VEHÍCULOS) ---
    if (!empty($_POST['cantidades_nuevas'])) {
        
        // Obtener el último id_serv usado para generar los siguientes
        $res = $conn->query("SELECT MAX(id_serv) as max_id FROM cotizacion_servicios WHERE id_cotizacion = $cotizacion_id");
        $row = $res->fetch_assoc();
        $next_id_serv = ($row['max_id']) ? $row['max_id'] + 1 : 1;

        foreach ($_POST['cantidades_nuevas'] as $i => $cantidad) {
            $descripcion = strtoupper(trim($_POST['descripciones_nuevas'][$i]));
            $precio = floatval($_POST['precios_nuevos'][$i]);
            $total = $cantidad * $precio;

            if ($cantidad > 0) {
                // Insertar Servicio
                $stmt = $conn->prepare("INSERT INTO cotizacion_servicios (id_cotizacion, id_serv, cantidad, servicio_manual, precio, total_servicio) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisdd", $cotizacion_id, $next_id_serv, $cantidad, $descripcion, $precio, $total);
                $stmt->execute();
                $stmt->close();

                // Datos de Vehículos
                $cond_n = !empty($_POST['conductor_nuevo'][$i]) ? intval($_POST['conductor_nuevo'][$i]) : null;
                $cam_n  = !empty($_POST['camion_nuevo'][$i]) ? intval($_POST['camion_nuevo'][$i]) : null;
                $ramp_n = !empty($_POST['rampla_nuevo'][$i]) ? intval($_POST['rampla_nuevo'][$i]) : null;
                $cama_n = !empty($_POST['camabaja_nuevo'][$i]) ? intval($_POST['camabaja_nuevo'][$i]) : null;

                // Insertar en Varios_Serv (Incluyendo los 4 tipos)
                $stmt = $conn->prepare("INSERT INTO varios_serv (id_cotizacion, id_serv, id_conductor, id_camiones, id_rampla, id_camabaja) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiiii", $cotizacion_id, $next_id_serv, $cond_n, $cam_n, $ramp_n, $cama_n);
                $stmt->execute();
                $stmt->close();

                $next_id_serv++; // Incrementar para el siguiente
            }
        }
    }

    // --- 6. AGREGAR NUEVOS ÍTEMS SIMPLES (SIN VEHÍCULOS) ---
    // Esta es la parte que faltaba o fallaba
    if (!empty($_POST['cantidad_simple'])) {
        
        // Obtenemos de nuevo el max ID por si se agregaron complejos arriba
        $res = $conn->query("SELECT MAX(id_serv) as max_id FROM cotizacion_servicios WHERE id_cotizacion = $cotizacion_id");
        $row = $res->fetch_assoc();
        $next_id_serv = ($row['max_id']) ? $row['max_id'] + 1 : 1;

        foreach ($_POST['cantidad_simple'] as $i => $qty) {
            $cantidad = intval($qty);
            $descripcion = strtoupper(trim($_POST['descripcion_simple'][$i]));
            $precio = floatval($_POST['precio_simple'][$i]);
            $total = $cantidad * $precio;

            if ($cantidad > 0 && !empty($descripcion)) {
                // Insertamos SOLO en cotizacion_servicios
                $stmt = $conn->prepare("INSERT INTO cotizacion_servicios (id_cotizacion, id_serv, cantidad, servicio_manual, precio, total_servicio) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisdd", $cotizacion_id, $next_id_serv, $cantidad, $descripcion, $precio, $total);
                $stmt->execute();
                $stmt->close();
                
                // NO insertamos en varios_serv porque es simple
                $next_id_serv++;
            }
        }
    }

    $conn->close();
    header("Location: listado_cotizaciones.php?msg=edicion_exitosa");
    exit();

} else {
    echo "Acceso denegado.";
}
?>