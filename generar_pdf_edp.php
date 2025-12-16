<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
date_default_timezone_set('America/Santiago');
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

require('fpdf/fpdf.php');
include 'content/connect.php';

class PDF extends FPDF
{
    function Header()
    {
        $this->Image('img/LOGO-FYJ.png', 15, 8, 25);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(79, 113, 190);
        $this->Cell(0, 10, utf8_decode('Estado de Pago Nº ') . $_GET['id_edp'], 0, 1, 'C');
        $this->Ln(-4);
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, utf8_decode('FFYJ Servicios Rut:76.973.144-K'), 0, 1, 'C');
        $this->Ln(8);
    }

    function Footer()
    {
        $this->SetY(-10);
        $this->SetTextColor(121, 125, 127);
        $this->SetFont('Arial','',10);
        $this->Cell(0, 8, utf8_decode('Altos del Pacífico 2848, Cond. Altos del Sur III, Alto Hospicio - Contacto: +569 963541816 - www.ffyjservicios.cl - ffyj.servicios@gmail.com'), 0, 1, 'C');
    }

    function RedibujarEncabezadosTabla()
    {
        $this->SetFillColor(0, 0, 0);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 10);

        $this->MultiCell(74, 16, 'Detalles', 1, 'C');
        $this->SetXY($this->GetX() + 74, $this->GetY() - 16);

        $this->Cell(30, 8, 'Periodo', 1, 0, 'C');
        $this->SetXY($this->GetX() - 30, $this->GetY() + 8);
        $this->Cell(15, 8, 'Inicio', 1, 0, 'C');
        $this->Cell(15, 8, 'Fin', 1, 0, 'C');

        $this->SetXY($this->GetX(), $this->GetY() - 8);
        $this->MultiCell(25, 16, 'Cantidad', 1, 'C');
        $this->SetXY($this->GetX() + 129, $this->GetY() - 16);

        $this->Cell(25, 8, 'Valor', 1, 0, 'C');
        $this->SetXY($this->GetX() - 25, $this->GetY() + 8);
        $this->Cell(25, 8, 'Servicios', 1, 0, 'C');

        $this->Cell(25, -8, 'Estadia', 1, 0, 'C');
        $this->SetXY($this->GetX() - 25, $this->GetY());
        $this->Cell(25, 8, 'Otros', 1, 0, 'C');
        $this->SetXY($this->GetX(), $this->GetY() - 8);

        $this->Cell(25, 16, 'Total Neto', 1, 0, 'C');
        $this->Cell(25, 16, 'I.V.A', 1, 0, 'C');
        $this->Cell(30, 16, 'TOTAL', 1, 1, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('L', 'Letter');
$pdf->SetFont('Arial','',12);

if (isset($_GET['id_edp'])) {
    $id_edp = $_GET['id_edp'];

    $sql_header = "SELECT 
        e.id_edp, 
        c.nombre_cliente, 
        c.email_cliente,
        e.fecha_inicio, 
        e.fecha_fin,
        e.created_at
    FROM edp e
    JOIN clientes c ON e.id_cliente = c.id_cliente
    WHERE e.id_edp = ?";

    $stmt_header = $conn->prepare($sql_header);
    $stmt_header->bind_param("i", $id_edp);
    $stmt_header->execute();
    $result_header = $stmt_header->get_result();

    if ($result_header->num_rows > 0) {
        $row_edp = $result_header->fetch_assoc();

        $pdf->Ln(-2);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(79,113,190);
        $pdf->Cell(0, 10, utf8_decode('TRANSPORTE DE CARGA, ARRIENDO DE MAQUINARIAS, REPARACIONES SPA'), 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', '', 9.5);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetX(10);
        $pdf->Cell(20, 5, 'Cliente', 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(80, 5, utf8_decode($row_edp['nombre_cliente']), 0, 0);

        $pdf->SetX(150);
        $pdf->Cell(25, 5, 'Email', 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(30, 5, utf8_decode($row_edp['email_cliente']), 0, 1);

        $pdf->SetX(10);
        $pdf->Cell(20, 5, 'Fecha Inicio', 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        $fecha_i = new DateTime($row_edp['fecha_inicio']);
        $pdf->Cell(80, 5, $fecha_i->format('d-m-Y'), 0, 0);

        $pdf->SetX(150);
        $pdf->Cell(25, 5, 'Fecha Termino', 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        if (!empty($row_edp['fecha_fin'])) {
            $fecha_f = new DateTime($row_edp['fecha_fin']);
            $pdf->Cell(30, 5, $fecha_f->format('d-m-Y'), 0, 1);
        } else {
            $pdf->Cell(30, 5, 'N/A', 0, 1);
        }

        $pdf->SetX(10);
        $pdf->Cell(20, 5, 'Creado el', 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        $fecha_creacion = new DateTime($row_edp['created_at']);
        $pdf->MultiCell(80, 5, $fecha_creacion->format('d-m-Y') . " a las " . $fecha_creacion->format('H:i'), 0, 'L');
        $pdf->Ln(5);

        $pdf->SetFillColor(79, 113, 190);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 8, utf8_decode('DETALLE DE SERVICIOS REALIZADOS'), 0, 1, 'C', true);
        $pdf->Ln(5);

        $pdf->RedibujarEncabezadosTabla();
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        $servicios_query = 
        "SELECT 
            c.id_cotizacion,
            t.detalle_servicio_principal,
            t.total_cantidad,
            c.fecha_inicio AS fecha_inicio_servicio,
            c.fecha_termino AS fecha_fin_servicio,
            c.guia,
            c.oc,
            t.total_precio,
            t.total_estadia,
            t.total_total,
            t.total_iva,
            t.total_valor
        FROM cotizaciones c
        JOIN (
            SELECT 
                id_cotizacion,
                GROUP_CONCAT(servicio_manual SEPARATOR ', ') AS detalle_servicio_principal,
                SUM(cantidad) AS total_cantidad,
                SUM(cantidad * precio) AS total_precio,
                SUM(estadia) AS total_estadia,
                SUM(cantidad * precio + estadia) AS total_total,
                SUM(cantidad * precio + estadia) * 0.19 AS total_iva,
                SUM(cantidad * precio + estadia) * 1.19 AS total_valor
            FROM cotizacion_servicios
            GROUP BY id_cotizacion
        ) t ON t.id_cotizacion = c.id_cotizacion
        JOIN edp_servicios es ON es.id_cotizacion = c.id_cotizacion
        JOIN edp e ON e.id_edp = es.id_edp
        WHERE e.id_edp = ?
        AND (c.estado_servicio = 'Aprobada' OR c.estado_servicio = 'Finalizado')
        ORDER BY c.id_cotizacion ASC";


        $stmt_servicios = $conn->prepare($servicios_query);
        $stmt_servicios->bind_param("i", $id_edp);
        $stmt_servicios->execute();
        $servicios_query_result = $stmt_servicios->get_result();

        $total_servicio_sum = 0;
        $total_estadia_sum = 0;
        $total_neto_sum = 0;
        $total_iva_sum = 0;
        $total_valor_sum = 0;

        while ($row_servicio = $servicios_query_result->fetch_assoc()) {
            $espacio_necesario = 25;
            if ($pdf->GetY() + $espacio_necesario > $pdf->GetPageHeight() - 15) {
                $pdf->AddPage('L', 'Letter');
                $pdf->RedibujarEncabezadosTabla();
            }

            $posY = $pdf->GetY();
            $posX = $pdf->GetX();

            $pdf->SetFont('Arial', '', 9.5);
            $pdf->SetXY($posX, $posY);
            $pdf->MultiCell(74, 4.5, utf8_decode($row_servicio['detalle_servicio_principal']), 0, 'L');
            $altura_celda = max(9, $pdf->GetY() - $posY);
            $pdf->Rect($posX, $posY, 74, $altura_celda);

            $pdf->SetXY($posX + 74, $posY);
            $pdf->Cell(15, $altura_celda, date('d-m-y', strtotime($row_servicio['fecha_inicio_servicio'])), 1, 0, 'C');
            $pdf->Cell(15, $altura_celda, date('d-m-y', strtotime($row_servicio['fecha_fin_servicio'])), 1, 0, 'C');
            $pdf->Cell(25, $altura_celda, number_format($row_servicio['total_cantidad'], 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell(25, $altura_celda, number_format($row_servicio['total_precio'], 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell(25, $altura_celda, number_format($row_servicio['total_estadia'], 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell(25, $altura_celda, number_format($row_servicio['total_total'], 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell(25, $altura_celda, number_format($row_servicio['total_total'] * 0.19, 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell(30, $altura_celda, number_format($row_servicio['total_total'] * 1.19, 0, ',', '.'), 1, 1, 'C');

            $total_servicio_sum += $row_servicio['total_precio'];
            $total_estadia_sum += $row_servicio['total_estadia'];
            $total_neto_sum += $row_servicio['total_total'];
            $total_iva_sum += $row_servicio['total_total'] * 0.19;
            $total_valor_sum += $row_servicio['total_total'] * 1.19;

            // ✅ Mostrar Guía y Orden de Compra si existen
            if (!empty($row_servicio['guia']) || !empty($row_servicio['oc'])) {
                $pdf->SetFont('Arial', 'I', 9.5);
                $texto_guia_oc = '';
                if (!empty($row_servicio['guia'])) {
                    $texto_guia_oc .= utf8_decode('Guías Nº ' . $row_servicio['guia']);
                }
                if (!empty($row_servicio['oc'])) {
                    if ($texto_guia_oc != '') $texto_guia_oc .= '   |   ';
                    $texto_guia_oc .= utf8_decode('OC Nº ' . $row_servicio['oc']);
                }
                $pdf->MultiCell(0, 8, $texto_guia_oc, 1, 'L');
                $pdf->SetFont('Arial', '', 9.5);
            }
        }

        $pdf->SetFont('Arial', 'B', 9.5);
        $pdf->Cell(74 + 30 + 25, 8, 'TOTALES GENERALES EDP', 1, 0, 'R');
        $pdf->Cell(25, 8, number_format($total_servicio_sum, 0, ',', '.'), 1, 0, 'C');
        $pdf->Cell(25, 8, number_format($total_estadia_sum, 0, ',', '.'), 1, 0, 'C');
        $pdf->Cell(25, 8, number_format($total_neto_sum, 0, ',', '.'), 1, 0, 'C');
        $pdf->Cell(25, 8, number_format($total_iva_sum, 0, ',', '.'), 1, 0, 'C');
        $pdf->Cell(30, 8, number_format($total_valor_sum, 0, ',', '.'), 1, 1, 'C');

        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 9.5);
        $pdf->MultiCell(0, 8, utf8_decode("Esperamos atentos sus comentarios."), 0, 'L');
        $pdf->Ln(5);

        $posicionYFirma = $pdf->GetY();
        if ($posicionYFirma + 35 > $pdf->GetPageHeight() - 15) {
            $pdf->AddPage('L', 'Letter');
            $posicionYFirma = 15;
        }
        $pdf->Image('img/fima_fran.png', 115, $posicionYFirma, 50);

    } else {
        $pdf->Cell(0,10,'No se encontraron resultados para este EDP.',0,1);
    }
    $stmt_header->close();

} else {
    $pdf->Cell(0,10,'No se proporcionó un ID de EDP.',0,1);
}

$conn->close();

$nombre_archivo = 'FFYJ_EDP_';
if (isset($row_edp['id_edp'])) {
    $nombre_archivo .= $row_edp['id_edp'];
} elseif (isset($_GET['id_edp'])) {
    $nombre_archivo .= $_GET['id_edp'];
} else {
    $nombre_archivo .= 'SIN_ID';
}
$nombre_archivo .= '.pdf';

$pdf->Output('I', $nombre_archivo);
?>
