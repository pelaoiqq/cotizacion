<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Conexión a la base de datos
include 'content/connect.php';

// Verificar si los datos fueron enviados correctamente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cotizacion'], $_POST['nuevo_estado'])) {
    // Recibir datos del formulario
    $id_cotizacion = intval($_POST['id_cotizacion']);
    $nuevo_estado = $conn->real_escape_string($_POST['nuevo_estado']);

    // Validar que el estado sea válido
    $estados_validos = ['Pendiente', 'Aprobada', 'Rechazada', 'Finalizado'];
    if (in_array($nuevo_estado, $estados_validos)) {
        // Actualizar el estado en la base de datos
        $sql = "UPDATE cotizaciones SET estado_servicio = '$nuevo_estado' WHERE id_cotizacion = $id_cotizacion";

        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Estado actualizado correctamente.'); window.location.href='listado_cotizaciones.php';</script>";
            exit();
        } else {
            header("Location: listado_cotizaciones.php?error=No se pudo actualizar el estado. Inténtalo de nuevo.");
            exit();
        }
    } else {
        header("Location: listado_cotizaciones.php?error=Estado inválido.");
        exit();
    }
} else {
    header("Location: listado_cotizaciones.php?error=Datos inválidos.");
    exit();
}

// Cerrar conexión
$conn->close();
