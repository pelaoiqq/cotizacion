<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que los campos obligatorios están presentes
    if (empty($_POST['id_proveedor'])) {
        die("Error: Faltan datos obligatorios.");
    }

    // Capturar datos principales
    $id_proveedor = intval($_POST['id_proveedor']);
    $forma_pago = mysqli_real_escape_string($conn, $_POST['forma_pago']);
    $cotizacion = isset($_POST['cotizacion']) ? $_POST['cotizacion'] : 0;


    // Insertar Orden de Compra
    $stmt = mysqli_prepare($conn, "INSERT INTO oc (id_proveedor, forma_pago, cotizacion) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iss", $id_proveedor, $forma_pago, $cotizacion);

    if (mysqli_stmt_execute($stmt)) {
        $orden_id = mysqli_insert_id($conn); // Obtener el ID de la OC recién creada
        mysqli_stmt_close($stmt);

        // Preparar la inserción de los detalles de la OC
        $stmt = mysqli_prepare($conn, "INSERT INTO oc_detalle (orden_id, item, descripcion, cantidad, precio, valor_neto, iva, total) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            die("Error en la preparación de la consulta: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "iisidddd", $orden_id, $item, $descripcion, $cantidad, $precio, $valor_neto, $iva, $total);

        $servicios = $_POST['servicios'];
        $cantidades = $_POST['cantidades'];
        $precios = $_POST['precios'];

        foreach ($servicios as $index => $servicio) {
            $item = $index + 1; // Asignar número de ítem
            $descripcion = mysqli_real_escape_string($conn, $servicio);
            $cantidad = intval($cantidades[$index]);
            $precio = floatval($precios[$index]);

            // Calcular valores por cada ítem
            $valor_neto = $cantidad * $precio;
            $iva = $valor_neto * 0.19;
            $total = $valor_neto + $iva;

            if (!mysqli_stmt_execute($stmt)) {
                die("Error al insertar en oc_detalle: " . mysqli_stmt_error($stmt));
            }
        }

        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        // Redireccionar con éxito
        header("Location: listado_oc.php?success=1");
        exit();
    } else {
        die("Error al guardar la orden de compra: " . mysqli_error($conn));
    }
} else {
    die("Acceso no permitido.");
}
