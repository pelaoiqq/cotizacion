<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
/*
echo "<pre>";
print_r($_POST);
echo "</pre>";
exit();*/


include 'content/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el ID de la orden de compra
    $id_oc = intval($_POST['orden_id']);


    if ($id_oc > 0) {
        // Actualizar ítems existentes
        if (isset($_POST['ids_existentes'], $_POST['cantidades_existentes'], $_POST['descripciones_existentes'], $_POST['precios_existentes'])) {
            $detalle_ids = $_POST['ids_existentes'];
            $cantidades = $_POST['cantidades_existentes'];
            $descripciones = $_POST['descripciones_existentes'];
            $precios = $_POST['precios_existentes'];
            $forma_pago = $_POST['forma_pago'];

        
            for ($i = 0; $i < count($detalle_ids); $i++) {
                $detalle_id = intval($detalle_ids[$i]); // ID único de cada ítem
                $cantidad = intval($cantidades[$i]); // Nueva cantidad
                $descripcion = $conn->real_escape_string($descripciones[$i]); // Nueva descripción
                $precio = floatval($precios[$i]); // Nuevo precio
                // Calcular valores por cada ítem
                $valor_neto = $cantidad * $precio;
                $iva = $valor_neto * 0.19;
                $total = $valor_neto + $iva;

        
                // Consulta SQL para actualizar
                $sqlUpdate = "UPDATE oc_detalle od, oc o
                SET od.cantidad = $cantidad, 
                    od.descripcion = '$descripcion', 
                    od.precio = $precio, 
                    od.valor_neto = $valor_neto, 
                    od.iva = $iva, 
                    od.total = $total, 
                    o.forma_pago = '$forma_pago'
                WHERE od.id_oc_detalle = $detalle_id 
                AND od.orden_id = o.id_oc";
                
                if (!$conn->query($sqlUpdate)) {
                    $_SESSION['error_message'] = "Error al actualizar el ítem con ID $detalle_id: " . $conn->error;
                    header("Location: listado_oc.php");
                    exit();
                }
            }
        }

        // Agregar nuevos ítems
        if (isset($_POST['cantidades_nuevas'], $_POST['descripciones_nuevas'], $_POST['precios_nuevos'])) {
            $cantidades_nuevas = $_POST['cantidades_nuevas'];
            $descripciones_nuevas = $_POST['descripciones_nuevas'];
            $precios_nuevos = $_POST['precios_nuevos'];

            // Obtener el último número de ítem (CORREGIDO)
            $stmtUltimoItem = $conn->prepare("SELECT MAX(item) FROM oc_detalle WHERE orden_id = ?"); // Asegúrate de que tu tabla tenga una columna 'item'
            $stmtUltimoItem->bind_param("i", $id_oc);
            $stmtUltimoItem->execute();
            $resultUltimoItem = $stmtUltimoItem->get_result();
            $ultimoItem = $resultUltimoItem->fetch_assoc();
            $numeroItem = $ultimoItem['MAX(item)'] ? intval($ultimoItem['MAX(item)']) + 1 : 1; // Si no hay ítems, empieza desde 1
            $stmtUltimoItem->close();


            for ($i = 0; $i < count($cantidades_nuevas); $i++) {
                $cantidad = intval($cantidades_nuevas[$i]);
                $descripcion = $conn->real_escape_string($descripciones_nuevas[$i]);
                $precio = floatval($precios_nuevos[$i]);
                
            // Calcular valores por cada ítem
            $valor_neto = $cantidad * $precio;
            $iva = $valor_neto * 0.19;
            $total = $valor_neto + $iva;

                if ($cantidad > 0 && !empty($descripcion) && $precio > 0) {
                    $sqlInsert = "INSERT INTO oc_detalle (orden_id, cantidad, descripcion, precio, item, valor_neto, iva, total) 
                                  VALUES ($id_oc, $cantidad, '$descripcion', $precio, $numeroItem, $valor_neto, $iva, $total)";
                    if (!$conn->query($sqlInsert)) {
                        $_SESSION['error_message'] = "Error al agregar un nuevo ítem: " . $conn->error;
                        header("Location: listado_oc.php");
                        exit();
                    }
                    $numeroItem++; // Incrementar el número de ítem para el siguiente registro
                }
            }
        }

        if (isset($_POST['eliminar_ids'])) {
            if (isset($_POST['eliminar_ids'])) {
                // Verificar si es una cadena y convertirla en un array
                if (is_string($_POST['eliminar_ids'])) {
                    $_POST['eliminar_ids'] = explode(',', $_POST['eliminar_ids']);
                }
            }            if (!empty($_POST['eliminar_ids'])) {
                foreach ($_POST['eliminar_ids'] as $id_detalle_eliminar) {
                   
                    
                    // Asegúrate de que el ID sea válido y esté en el formato adecuado
                  //  $id_detalle_eliminar = intval($id_detalle_eliminar);
                    
                    // Validar si el ID existe en la base de datos (opcional)
                    $sqlCheck = "SELECT 1 FROM oc_detalle WHERE id_oc_detalle = ?";
                    $stmtCheck = $conn->prepare($sqlCheck);
                    $stmtCheck->bind_param("i", $id_detalle_eliminar);
                    $stmtCheck->execute();
                    $stmtCheck->store_result();
        
                    if ($stmtCheck->num_rows > 0) {
                        // Si existe el id, proceder a eliminar
                        $sqlDelete = "DELETE FROM oc_detalle WHERE id_oc_detalle = ?";
                        $stmt = $conn->prepare($sqlDelete);
                        $stmt->bind_param("i", $id_detalle_eliminar);
                        if ($stmt->execute()) {
                            // Eliminación exitosa
                            $stmt->close();
                        } else {
                            // Error al eliminar
                            $_SESSION['error_message'] = "Error al eliminar: " . $stmt->error;
                            break; // Salir del loop en caso de error
                        }
                    } else {
                        $_SESSION['error_message'] = "ID no encontrado: $id_detalle_eliminar";
                        break; // Salir si el ID no existe
                    }
                    $stmtCheck->close();
                }
                // Redirigir solo después de intentar eliminar todos los registros
                header("Location: listado_oc.php");
                exit();
            } else {
                $_SESSION['error_message'] = "No se enviaron IDs para eliminar.";
                header("Location: listado_oc.php");
                exit();
            }
        }

        // Redirigir al listado de órdenes con un mensaje de éxito
        $_SESSION['success_message'] = "Orden de compra actualizada correctamente.";
        header("Location: listado_oc.php");
        exit();
    } else {
        $_SESSION['error_message'] = "ID de la orden de compra no válido.";
        header("Location: listado_oc.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "Solicitud inválida.";
    header("Location: listado_oc.php");
    exit();
}

$conn->close();

?>


