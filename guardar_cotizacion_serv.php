<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_cliente = isset($_POST['id_cliente']) ? $_POST['id_cliente'] : '';
    $email_cliente = isset($_POST['email_cliente']) ? $_POST['email_cliente'] : '';
    $detalle_servicios = isset($_POST['descripcion_servicio']) ? $_POST['descripcion_servicio'] : '';
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_termino = isset($_POST['fecha_termino']) ? $_POST['fecha_termino'] : '';
    $forma_pago = isset($_POST['forma_pago']) ? $_POST['forma_pago'] : '';
    $estado_servicio = "Pendiente";
    $area_servicios = "Servicio";

    // Total general
    $total_servicios = isset($_POST['total_servicios']) ? $_POST['total_servicios'] : array();
    $total = is_array($total_servicios) ? array_sum($total_servicios) : 0;

    if (empty($id_cliente) || empty($email_cliente) || empty($fecha_inicio)) {
        die("Por favor completa todos los campos requeridos.");
    }
/*
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    exit();
*/
    $conn->begin_transaction();

    try {
        // Insertar en cotizaciones
        $sql = "INSERT INTO cotizaciones (area_servicios, id_cliente, email_cliente, detalle_servicios, fecha_inicio, fecha_termino, estado_servicio, total, total_servicio, forma_pago) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en prepare cotizaciones: " . $conn->error);
        }

        $stmt->bind_param('sisssssiis', $area_servicios, $id_cliente, $email_cliente, $detalle_servicios, $fecha_inicio, $fecha_termino, $estado_servicio, $total, $total, $forma_pago);

        if (!$stmt->execute()) {
            throw new Exception("Error al guardar la cotización: " . $stmt->error);
        }

        $id_cotizacion = $stmt->insert_id;
        $stmt->close();

        // Insertar detalle de servicios
        if (!empty($_POST['cantidades']) && !empty($_POST['servicios']) && !empty($_POST['valores']) && !empty($_POST['total_servicios'])) {
            $cantidades = $_POST['cantidades'];
            $servicios = $_POST['servicios'];
            $valores = $_POST['valores'];
            $total_servicios = $_POST['total_servicios'];

            // Iniciar contador de servicios
            $id_serv = 1;
            $id_servicio = 3; // fijo o dinámico si deseas cambiarlo

            foreach ($cantidades as $index => $cantidad) {
                if (isset($servicios[$index], $valores[$index], $total_servicios[$index])) {

                    $servicio = $servicios[$index];
                    $valor = $valores[$index];
                    $total_servicio = $total_servicios[$index];

                    $sqlDetalle = "INSERT INTO cotizacion_servicios 
                        (id_cotizacion, id_servicio, detalle_servicios, cantidad, servicio_manual, precio, id_serv, total_servicio) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmtDetalle = $conn->prepare($sqlDetalle);

                    if (!$stmtDetalle) {
                        throw new Exception("Error en prepare detalle: " . $conn->error);
                    }

                    // Tipos: i = int, s = string, d = double
                    $stmtDetalle->bind_param("iisisiid", $id_cotizacion, $id_servicio, $detalle_servicios, $cantidad, $servicio, $valor, $id_serv, $total_servicio);

                    if (!$stmtDetalle->execute()) {
                        throw new Exception("Error al guardar el detalle del servicio: " . $stmtDetalle->error);
                    }

                    $stmtDetalle->close();

                    // Incrementar número de servicio dentro de la misma cotización
                    $id_serv++;
                }
            }
        }

        $conn->commit();
        $_SESSION['success'] = "Cotización guardada exitosamente.";
        $_SESSION['id_cotizacion'] = $id_cotizacion;
        header("Location: confirmacion.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
        header("Location: listado_cotizaciones.php");
        exit();
    } finally {
        if (isset($stmt)) $stmt->close();
        if (isset($stmtDetalle)) $stmtDetalle->close();
        $conn->close();
    }

} else {
    $_SESSION['error'] = "Método de solicitud no válido.";
    header("Location: listado_cotizaciones.php");
}
?>
