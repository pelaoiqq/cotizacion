<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generar'])) {
    $id_cliente = $_POST['id_cliente'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_termino = $_POST['fecha_termino'];
    $cotizaciones = $_POST['cotizaciones_seleccionadas'];

    // Verificar si las cotizaciones están vacías
    if (empty($cotizaciones)) {
        $_SESSION['error'] = "No hay cotizaciones seleccionadas.";
        header("Location: edp.php");
        exit();
    }

    try {
        // Crear los marcadores de posición para la consulta
        $placeholders = implode(',', array_fill(0, count($cotizaciones), '?'));
        $query = "
            SELECT id_cotizacion, total_servicio
            FROM cotizaciones
            WHERE id_cliente = ?
            AND id_cotizacion IN ($placeholders)
            AND estado_servicio IN ('Aprobada', 'Finalizado')
        ";

        $stmt = $conn->prepare($query);
        // Parametros: cliente + cotizaciones
        $params = array_merge([$id_cliente], $cotizaciones);
        $stmt->bind_param(str_repeat('i', count($params)), ...$params); // 'i' para enteros
        $stmt->execute();
        
        // Obtener los resultados con fetch_assoc() para mysqli
        $result = $stmt->get_result();
        $cotizaciones_data = [];
        
        while ($row = $result->fetch_assoc()) {
            $cotizaciones_data[] = $row;
        }

        if ($cotizaciones_data) {
            // Inicia la transacción
            $conn->begin_transaction();

            // Insertar en la tabla edp
            $insert_edp_query = "INSERT INTO edp (id_cliente, fecha_inicio, fecha_fin) VALUES (?, ?, ?)";
            $insert_edp_stmt = $conn->prepare($insert_edp_query);
            $insert_edp_stmt->bind_param('iss', $id_cliente, $fecha_inicio, $fecha_termino);
            $insert_edp_stmt->execute();
            $id_edp = $conn->insert_id; // Obtener el último ID insertado

            // Obtener el próximo número de grupo
            $query_numero_grupo = "SELECT MAX(numero_grupo) FROM edp_servicios";
            $result_numero_grupo = $conn->query($query_numero_grupo);
            $row_numero_grupo = $result_numero_grupo->fetch_assoc();
            $numero_grupo = (isset($row_numero_grupo['MAX(numero_grupo)']) ? $row_numero_grupo['MAX(numero_grupo)'] : 0) + 1;

            // Insertar datos de edp_servicios
            foreach ($cotizaciones_data as $row) {
                $id_cotizacion = $row['id_cotizacion'];
                $total = isset($row['total_servicio']) ? $row['total_servicio'] : 0;
                $iva = $total * 0.19;
                $valor_total = $total + $iva;

                $insert_edp_servicio_query = "
                    INSERT INTO edp_servicios (id_edp, id_cotizacion, total, iva, valor_total, numero_grupo)
                    VALUES (?, ?, ?, ?, ?, ?)
                ";
                $insert_edp_servicio_stmt = $conn->prepare($insert_edp_servicio_query);
                $insert_edp_servicio_stmt->bind_param('iidddi', $id_edp, $id_cotizacion, $total, $iva, $valor_total, $numero_grupo);
                $insert_edp_servicio_stmt->execute();

                // Actualizar cotizacion_servicios (Consulta Corregida)
                $update_cotizacion_servicios_query = "
                UPDATE cotizacion_servicios
                SET iva = ?, valor_total = ?
                WHERE id_cotizacion = ?
                AND id_cot_servicios = (
                    -- Envolver la subconsulta original dentro de otra y darle un alias
                    SELECT max_id 
                    FROM (
                        SELECT MAX(id_cot_servicios) as max_id 
                        FROM cotizacion_servicios 
                        WHERE id_cotizacion = ?
                    ) AS temp_max_id -- Alias para la tabla derivada (obligatorio)
                )
                ";
                $update_cotizacion_servicios_stmt = $conn->prepare($update_cotizacion_servicios_query);
                if (!$update_cotizacion_servicios_stmt) {
                // Si aún hay error aquí, podría ser otro problema, pero no el 'target table'.
                die("Error en prepare: " . $conn->error . " | Query: " . $update_cotizacion_servicios_query); 
                }

                // Corregir los tipos en bind_param:
                // 'd' para double/decimal (iva, valor_total)
                // 'i' para integer (id_cotizacion)
                // Se necesitan 4 parámetros: iva, valor_total, id_cotizacion (WHERE principal), id_cotizacion (WHERE subconsulta)
                $update_cotizacion_servicios_stmt->bind_param('ddii', $iva, $valor_total, $id_cotizacion, $id_cotizacion); 

                $result_exec = $update_cotizacion_servicios_stmt->execute();

                // Opcional: Verificar si la ejecución fue exitosa
                if ($result_exec === false) {
                // Lanzar una excepción para que el rollback funcione
                throw new Exception("Error al actualizar cotizacion_servicios: " . $update_cotizacion_servicios_stmt->error);
                }
                $update_cotizacion_servicios_stmt->close(); // Buena práctica cerrar el statement
            }

            // Si todo va bien, se hace commit
            $conn->commit();
            $_SESSION['message'] = "Estado de Pago generado correctamente.";
            header("Location: listado_edp.php");
            exit();

        } else {
            $_SESSION['error'] = "No se encontraron cotizaciones para generar el EDP.";
            header("Location: edp.php");
            exit();
        }

    } catch (Exception $e) {
        // Si ocurre un error, hacer rollback
        $conn->rollback();
        $_SESSION['error'] = "Error al generar el EDP: " . $e->getMessage();
        header("Location: edp.php");
        exit();
    }
}
?>
