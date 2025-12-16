<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. VALIDACIÓN BÁSICA
    $id_cotizacion = isset($_POST['id_cotizacion']) ? intval($_POST['id_cotizacion']) : 0;

    if ($id_cotizacion <= 0) {
        die("Error Crítico: ID inválido.");
    }
    
    // 2. SEGURIDAD: Verificar estado antes de guardar
    $sqlState = "SELECT estado_servicio FROM cotizaciones WHERE id_cotizacion = ?";
    $stmtState = $conn->prepare($sqlState);
    $stmtState->bind_param("i", $id_cotizacion);
    $stmtState->execute();
    $resState = $stmtState->get_result();
    $rowState = $resState->fetch_assoc();
    $stmtState->close();
    
    if ($rowState && ($rowState['estado_servicio'] === 'Finalizado' || $rowState['estado_servicio'] === 'Rechazada')) {
        echo "<script>alert('Error: No se pueden modificar cotizaciones Finalizadas o Rechazadas.'); window.location.href='listado_cotizaciones.php';</script>";
        exit();
    }

    // 3. RECUPERAR DATOS (Arrays con índices explícitos)
    $id_servicio_list = isset($_POST['id_serv']) ? $_POST['id_serv'] : array();
    
    // Arrays de Cotización Servicios
    $precios = isset($_POST['precio']) ? $_POST['precio'] : array();
    $cantidades = isset($_POST['cantidad']) ? $_POST['cantidad'] : array();
    $estadias = isset($_POST['estadia']) ? $_POST['estadia'] : array();
    
    // Arrays de Vehículos (Varios Serv)
    $conductor_ids = isset($_POST['conductor_id']) ? $_POST['conductor_id'] : array();
    $camion_ids = isset($_POST['camion_id']) ? $_POST['camion_id'] : array();
    $rampla_ids = isset($_POST['rampla_id']) ? $_POST['rampla_id'] : array();
    $camabaja_ids = isset($_POST['camabaja_id']) ? $_POST['camabaja_id'] : array();

    // Arrays de Gastos
    $kilometrajes = isset($_POST['kilometraje']) ? $_POST['kilometraje'] : array();
    $combustibles = isset($_POST['combustible']) ? $_POST['combustible'] : array();
    $peajes = isset($_POST['peaje']) ? $_POST['peaje'] : array();
    $lavados = isset($_POST['lavado']) ? $_POST['lavado'] : array();
    $viaticos = isset($_POST['viatico']) ? $_POST['viatico'] : array();
    $neumaticos = isset($_POST['neumatico']) ? $_POST['neumatico'] : array();
    $externos = isset($_POST['externo']) ? $_POST['externo'] : array();
    $otros = isset($_POST['otros']) ? $_POST['otros'] : array();

    // Datos de Cabecera
    $servicios = isset($_POST['servicios']) ? $_POST['servicios'] : null;
    $fecha_inicio = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
    $fecha_termino = !empty($_POST['fecha_termino']) ? $_POST['fecha_termino'] : null;
    
    $guia = isset($_POST['guia']) ? $_POST['guia'] : '';
    $factura = isset($_POST['factura']) ? $_POST['factura'] : '';
    $oc = isset($_POST['oc']) ? $_POST['oc'] : '';
    $nroservicio = isset($_POST['nroservicio']) ? $_POST['nroservicio'] : '';
    
    $valor_total = isset($_POST['valor_total']) ? floatval($_POST['valor_total']) : 0;
    $descuento = isset($_POST['descuento']) ? floatval($_POST['descuento']) : 0;
    $total_servicio_head = isset($_POST['valor_servicio']) ? floatval($_POST['valor_servicio']) : 0;

    $conn->autocommit(FALSE);

    try {
        // PASO A: Actualizar cotizacion_servicios
        $sql_serv = "UPDATE cotizacion_servicios SET 
                     precio = ?, cantidad = ?, total_servicio = ?, estadia = ?, 
                     valor_total_descto = ?, total_se = ?
                     WHERE id_cotizacion = ? AND id_serv = ?";
        $stmt_serv = $conn->prepare($sql_serv);

        foreach ($id_servicio_list as $index => $id_serv) {
            // Verificar existencia segura del índice
            $p_precio   = isset($precios[$index]) ? floatval($precios[$index]) : 0;
            $p_cant     = isset($cantidades[$index]) ? intval($cantidades[$index]) : 0;
            $p_estadia  = isset($estadias[$index]) ? floatval($estadias[$index]) : 0;
            
            $p_total    = $p_precio * $p_cant;
            $p_total_se = $p_total + $p_estadia;

            $stmt_serv->bind_param("didddiii",
                $p_precio, $p_cant, $p_total, $p_estadia,
                $valor_total, $p_total_se, $id_cotizacion, $id_serv
            );
            
            if (!$stmt_serv->execute()) throw new Exception("Error update servicio: " . $stmt_serv->error);
        }
        $stmt_serv->close();

        // PASO B: Actualizar cabecera
        // Sumar gastos de forma segura (verificando índices)
        $descuentos_vs = 0;
        foreach ($id_servicio_list as $idx => $val) {
             $descuentos_vs += (isset($combustibles[$idx]) ? floatval($combustibles[$idx]) : 0) + 
                               (isset($peajes[$idx]) ? floatval($peajes[$idx]) : 0) + 
                               (isset($lavados[$idx]) ? floatval($lavados[$idx]) : 0) +
                               (isset($viaticos[$idx]) ? floatval($viaticos[$idx]) : 0) + 
                               (isset($neumaticos[$idx]) ? floatval($neumaticos[$idx]) : 0) + 
                               (isset($externos[$idx]) ? floatval($externos[$idx]) : 0) + 
                               (isset($otros[$idx]) ? floatval($otros[$idx]) : 0);
        }

        $sql_head = "UPDATE cotizaciones SET 
            total_servicio = ?, descto_servicio = ?, descto_vs = ?, detalle_servicios = ?,
            guia = ?, nroservicio = ?, oc = ?, factura = ?,  
            total = ?, fecha_inicio = ?, fecha_termino = ?
            WHERE id_cotizacion = ?";
        
        $stmt_head = $conn->prepare($sql_head);
        $stmt_head->bind_param("dddsssssdssi", 
            $total_servicio_head, $descuento, $descuentos_vs, $servicios, 
            $guia, $nroservicio, $oc, $factura, 
            $valor_total, $fecha_inicio, $fecha_termino, $id_cotizacion
        );
        
        if (!$stmt_head->execute()) throw new Exception("Error update cabecera: " . $stmt_head->error);
        $stmt_head->close();

        // PASO C: Insertar/Actualizar varios_serv
        $sql_check = "SELECT COUNT(*) FROM varios_serv WHERE id_cotizacion = ? AND id_serv = ?";
        $stmt_check = $conn->prepare($sql_check);

        $sql_upd_var = "UPDATE varios_serv SET 
            id_conductor = ?, id_camiones = ?, id_rampla = ?, id_camabaja = ?, 
            kilometraje = ?, combustible = ?, peaje = ?, lavado = ?, viatico = ?, 
            neumatico = ?, externo = ?, otros = ? 
            WHERE id_cotizacion = ? AND id_serv = ?"; 
        $stmt_upd_var = $conn->prepare($sql_upd_var);

        $sql_ins_var = "INSERT INTO varios_serv (
            id_cotizacion, id_serv, id_conductor, id_camiones, id_rampla, id_camabaja, 
            kilometraje, combustible, peaje, lavado, viatico, neumatico, externo, otros
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_ins_var = $conn->prepare($sql_ins_var);

        foreach ($id_servicio_list as $index => $id_serv) {
            
            // Verificamos si existe registro
            $stmt_check->bind_param("ii", $id_cotizacion, $id_serv);
            $stmt_check->execute();
            $stmt_check->bind_result($exists);
            $stmt_check->fetch();
            $stmt_check->free_result(); 

            // Sanitización USANDO EL ÍNDICE $index. Si no existe, es NULL o 0.
            $v_cond  = !empty($conductor_ids[$index]) ? intval($conductor_ids[$index]) : NULL;
            $v_cam   = !empty($camion_ids[$index]) ? intval($camion_ids[$index]) : NULL;
            $v_ramp  = !empty($rampla_ids[$index]) ? intval($rampla_ids[$index]) : NULL;
            $v_baja  = !empty($camabaja_ids[$index]) ? intval($camabaja_ids[$index]) : NULL;

            $g_km    = !empty($kilometrajes[$index]) ? floatval($kilometrajes[$index]) : 0;
            $g_comb  = !empty($combustibles[$index]) ? floatval($combustibles[$index]) : 0;
            $g_peaje = !empty($peajes[$index]) ? floatval($peajes[$index]) : 0;
            $g_lav   = !empty($lavados[$index]) ? floatval($lavados[$index]) : 0;
            $g_viat  = !empty($viaticos[$index]) ? floatval($viaticos[$index]) : 0;
            $g_neu   = !empty($neumaticos[$index]) ? floatval($neumaticos[$index]) : 0;
            $g_ext   = !empty($externos[$index]) ? floatval($externos[$index]) : 0;
            $g_otros = !empty($otros[$index]) ? floatval($otros[$index]) : 0;

            if ($exists > 0) {
                // Solo se actualiza SI el ID_COTIZACION coincide. Imposible modificar otro.
                $stmt_upd_var->bind_param("iiiiddddddddii",
                    $v_cond, $v_cam, $v_ramp, $v_baja,
                    $g_km, $g_comb, $g_peaje, $g_lav, $g_viat, $g_neu, $g_ext, $g_otros,
                    $id_cotizacion, $id_serv 
                );
                if (!$stmt_upd_var->execute()) throw new Exception("Error Update Varios: " . $stmt_upd_var->error);
            } else {
                $stmt_ins_var->bind_param("iiiiiiiddddddd",
                    $id_cotizacion, $id_serv,
                    $v_cond, $v_cam, $v_ramp, $v_baja,
                    $g_km, $g_comb, $g_peaje, $g_lav, $g_viat, $g_neu, $g_ext, $g_otros
                );
                if (!$stmt_ins_var->execute()) throw new Exception("Error Insert Varios: " . $stmt_ins_var->error);
            }
        }

        $stmt_check->close();
        $stmt_upd_var->close();
        $stmt_ins_var->close();
        $conn->commit();
        
        echo "<script>alert('Datos almacenados correctamente.'); window.location.href='listado_cotizaciones.php';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        die("Error en la operación: " . $e->getMessage());
    }

    $conn->close();

} else {
    die("Método no permitido.");
}
?>