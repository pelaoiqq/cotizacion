<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. RECEPCIÓN Y SANITIZACIÓN DE DATOS (PHP 5.6 COMPATIBLE)
    $id_cliente = isset($_POST['id_cliente']) ? intval($_POST['id_cliente']) : 0;
    $email_cliente = isset($_POST['email_cliente']) ? $_POST['email_cliente'] : '';
    
    // Convertimos a mayúsculas para estandarizar
    $detalle_servicios = isset($_POST['descripcion_servicio']) ? strtoupper(trim($_POST['descripcion_servicio'])) : '';
    $origen = isset($_POST['origen']) ? strtoupper(trim($_POST['origen'])) : '';
    $destino = isset($_POST['destino']) ? strtoupper(trim($_POST['destino'])) : '';
    
    // Manejo de fechas (NULL si están vacías)
    $fecha_inicio = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : NULL;
    $fecha_termino = !empty($_POST['fecha_termino']) ? $_POST['fecha_termino'] : NULL;
    
    $estado_servicio = isset($_POST['estado']) ? $_POST['estado'] : 'Pendiente';
    $area_servicios = isset($_POST['tipo_cotizacion']) ? $_POST['tipo_cotizacion'] : 'Venta';
    $forma_pago = isset($_POST['forma_pago']) ? $_POST['forma_pago'] : '';

    // 2. CALCULAR TOTAL GENERAL
    $total_cotizacion = 0;
    if (!empty($_POST['total_servicios'])) {
        foreach ($_POST['total_servicios'] as $monto) {
            $total_cotizacion += floatval($monto);
        }
    }

    // 3. INICIAR TRANSACCIÓN
    $conn->autocommit(FALSE); // Modo transacción

    try {
        // --- INSERTAR EN TABLA COTIZACIONES (USANDO PREPARED STATEMENTS) ---
        $sql_cot = "INSERT INTO cotizaciones 
            (area_servicios, id_cliente, email_cliente, detalle_servicios, origen, destino, fecha_inicio, fecha_termino, estado_servicio, total, total_servicio, forma_pago) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql_cot);
        if (!$stmt) { throw new Exception("Error preparando cotización: " . $conn->error); }

        // Tipos: s=string, i=integer, d=double(float)
        // Nota: Las fechas se pasan como string 's'. Si son NULL, PHP lo maneja bien en bind_param si no forzamos tipos estrictos.
        $stmt->bind_param("sissssssssds", 
            $area_servicios, 
            $id_cliente, 
            $email_cliente, 
            $detalle_servicios, 
            $origen, 
            $destino, 
            $fecha_inicio, 
            $fecha_termino, 
            $estado_servicio, 
            $total_cotizacion, // total
            $total_cotizacion, // total_servicio (parece redundante, pero respeto tu estructura)
            $forma_pago
        );

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando cotización: " . $stmt->error);
        }

        $id_cotizacion = $conn->insert_id;
        $stmt->close();

        // --- INSERTAR DETALLES (COTIZACION_SERVICIOS) ---
        $cantidades = isset($_POST['cantidades']) ? $_POST['cantidades'] : array();
        $servicios = isset($_POST['servicios']) ? $_POST['servicios'] : array();
        $valores = isset($_POST['valores']) ? $_POST['valores'] : array();
        $total_items = isset($_POST['total_servicios']) ? $_POST['total_servicios'] : array();

        // Preparamos la sentencia UNA sola vez fuera del bucle para eficiencia
        $sql_det = "INSERT INTO cotizacion_servicios 
            (id_cotizacion, id_servicio, detalle_servicios, servicio_manual, cantidad, precio, id_serv, total_servicio) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_det = $conn->prepare($sql_det);
        if (!$stmt_det) { throw new Exception("Error preparando detalles: " . $conn->error); }

        foreach ($cantidades as $index => $qty) {
            $cantidad = intval($qty);
            $nombre_servicio = isset($servicios[$index]) ? strtoupper(trim($servicios[$index])) : '';
            $precio_unit = isset($valores[$index]) ? floatval($valores[$index]) : 0;
            $total_linea = isset($total_items[$index]) ? floatval($total_items[$index]) : 0;
            
            $id_serv_secuencia = $index + 1; 
            $id_servicio_fijo = 3; // ID Fijo según tu código original (Ajustar si es necesario)

            if ($cantidad > 0) {
                $stmt_det->bind_param("iisssdid", 
                    $id_cotizacion, 
                    $id_servicio_fijo, 
                    $detalle_servicios, // Descripción general heredada
                    $nombre_servicio,   // Descripción específica del ítem
                    $cantidad, 
                    $precio_unit, 
                    $id_serv_secuencia, 
                    $total_linea
                );

                if (!$stmt_det->execute()) {
                    throw new Exception("Error insertando ítem #$index: " . $stmt_det->error);
                }
            }
        }
        $stmt_det->close();

        // 4. CONFIRMAR TRANSACCIÓN
        $conn->commit();

        // Redirigir a éxito
        $_SESSION['success'] = "Cotización de venta N° $id_cotizacion guardada exitosamente.";
        
        // OJO: Redirige a donde prefieras. Si confirmacion.php no existe, usa listado_cotizaciones.php
        // header("Location: confirmacion.php?id=" . $id_cotizacion); 
        header("Location: listado_cotizaciones.php?msg=guardado");
        exit();

    } catch (Exception $e) {
        // Si algo falla, revertir todo
        $conn->rollback();
        // Guardar error en sesión para mostrarlo amigablemente
        $_SESSION['error'] = "Error al guardar: " . $e->getMessage();
        header("Location: cotizar_ventas.php"); // Volver al formulario
        exit();
    } finally {
        $conn->autocommit(TRUE); // Restaurar autocommit
        $conn->close();
    }

} else {
    // Si intentan entrar directo sin POST
    header("Location: listado_cotizaciones.php");
    exit();
}
?>