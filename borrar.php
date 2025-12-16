<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php'; // Archivo para conectar a la base de datos   

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Paso 1: Insertar en la tabla `cotizaciones`
    $id_cliente = isset($_POST['id_cliente']) ? $_POST['id_cliente'] : '';
    $email_cliente = isset($_POST['email_cliente']) ? $_POST['email_cliente'] : '';
    $detalle_servicios = isset($_POST['descripcion_servicio']) ? $_POST['descripcion_servicio'] : '';
    $servicio_manual = isset($_POST['servicio']) ? $_POST['servicio'] : null;
    $origen = isset($_POST['origen']) ? $_POST['origen'] : '';
    $destino = isset($_POST['destino']) ? $_POST['destino'] : '';
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_termino = isset($_POST['fecha_termino']) ? $_POST['fecha_termino'] : '';
    $estado_servicio = "Pendiente";
    $area_servicios = "Transporte";
    $forma_pago = isset($_POST['forma_pago']) ? $_POST['forma_pago'] : null;

    // Consulta para insertar los datos generales en `cotizaciones`
    $sql_cotizaciones = "INSERT INTO cotizaciones (area_servicios, id_cliente, email_cliente, detalle_servicios, origen, destino, fecha_inicio, fecha_termino, estado_servicio) 
                        VALUES ('$area_servicios', '$id_cliente', '$email_cliente', '$detalle_servicios', '$origen', '$destino', '$fecha_inicio', '$fecha_termino', '$estado_servicio')";

    if ($conn->query($sql_cotizaciones)) {
        $id_cotizacion = $conn->insert_id; // ID generado de la cotización

        $cantidades = $_POST['cantidades']; // Array
        $servicios = $_POST['servicios'];   // Array
        $valores = $_POST['valores'];       // Array
        $total_servicios = $_POST['total_servicios']; // Array

        $id_serv = 1; // Inicializamos el contador de servicios

        foreach ($cantidades as $index => $cantidad) {
            $servicio = $servicios[$index];
            $precio = $valores[$index];
            $total = $total_servicios[$index];
            $id_servicio = 3; // Cambiar este valor si corresponde

            // Inserción de cada servicio relacionado en `cotizacion_servicios`
            $sql_servicios = "INSERT INTO cotizacion_servicios (id_cotizacion, id_servicio, detalle_servicios, servicio_manual, cantidad, precio, total_servicio, forma_pago, id_serv) 
                            VALUES ('$id_cotizacion', '$id_servicio', '$detalle_servicios', '$servicio', '$cantidad', '$precio', '$total', '{$_POST['forma_pago']}', '$id_serv')";

            if (!$conn->query($sql_servicios)) {
                die("Error al insertar en cotizacion_servicios: " . $conn->error);
            }

            $id_serv++; // Incrementamos el contador para el siguiente servicio
        }

        $conductor_ids = $_POST['conductor_id']; // Array
        $camion_ids = $_POST['camion_id'];       // Array
        $rampla_ids = $_POST['rampla_id'];       // Array
        $camabaja_ids = $_POST['camabaja_id'];   // Array

        $id_serv = 1; // Reiniciamos el contador para los varios_serv

        foreach ($conductor_ids as $index => $conductor_id) {
            $camion_id = !empty($camion_ids[$index]) ? intval($camion_ids[$index]) : null;
            $rampla_id = !empty($rampla_ids[$index]) ? intval($rampla_ids[$index]) : null;
            $camabaja_id = !empty($camabaja_ids[$index]) ? intval($camabaja_ids[$index]) : null;

            if ($rampla_id !== null && $camabaja_id !== null) {
                $query = "INSERT INTO varios_serv (id_cotizacion, id_conductor, id_camiones, id_rampla, id_camabaja, id_serv) 
                        VALUES ($id_cotizacion, $conductor_id, $camion_id, $rampla_id, $camabaja_id, $id_serv)";
            } elseif ($rampla_id !== null) {
                $query = "INSERT INTO varios_serv (id_cotizacion, id_conductor, id_camiones, id_rampla, id_serv) 
                        VALUES ($id_cotizacion, $conductor_id, $camion_id, $rampla_id, $id_serv)";
            } elseif ($camabaja_id !== null) {
                $query = "INSERT INTO varios_serv (id_cotizacion, id_conductor, id_camiones, id_camabaja, id_serv) 
                        VALUES ($id_cotizacion, $conductor_id, $camion_id, $camabaja_id, $id_serv)";
            } else {
                $query = "INSERT INTO varios_serv (id_cotizacion, id_conductor, id_camiones, id_serv) 
                        VALUES ($id_cotizacion, $conductor_id, $camion_id, $id_serv)";
            }

            $conn->query($query);
            $id_serv++;
        }

        $conn->commit();
        $_SESSION['success'] = "Cotización guardada exitosamente.";
        header("Location: listado_cotizaciones.php");
        exit();
    } else {
        die("Error al insertar en cotizaciones: " . $conn->error);
    }
} else {
    $_SESSION['error'] = "Método de solicitud no válido.";
    header("Location: listado_cotizaciones.php");
}
?>