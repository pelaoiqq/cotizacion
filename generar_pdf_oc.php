<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<?php
require('fpdf/fpdf.php');
include 'content/connect.php';

// Extendemos la clase FPDF
class PDF extends FPDF
{
    var $widths;
    var $aligns;
    var $lineHeight; 

    // Cabecera de página
    function Header()
    {
        // Logo 
        $this->Image('img/LOGO-FYJ.png', 15, 12, 30); 

        // Título del encabezado
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(79, 113, 190); 
        $this->Cell(0, 10, utf8_decode('Orden de Compra Nº ') . (isset($_GET['id_oc']) ? $_GET['id_oc'] : '') . '/' . date('Y'), 0, 1, 'C');
        $this->Ln(-1); 
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(0, 4, utf8_decode("Transporte de Carga, Arriendo de Maquinarias\nReparaciones SPA\nAltos del Pacífico 2848\nAlto Hospicio\nRut. 76.973.144-K\nfinanzas@ffyjservicios.cl"), 0, 'C');
        $this->Ln(8); 
    }

    // Pie de página (SOLO INFORMACIÓN DE CONTACTO)
    function Footer()
    {
        // Posición a 1.5 cm del final
        $this->SetY(-15);
        $this->SetTextColor(121, 125, 127); // Color #797d7f
        $this->SetFont('Arial','',10);
        $this->MultiCell(0, 5, utf8_decode("Altos del Pacífico 2848, Cond. Altos del Sur III, Alto Hospicio\nContacto: +569 963541816 - www.ffyjservicios.cl - ffyj.servicios@gmail.com"), 0, 'C');
    }

    // --- NUEVA FUNCIÓN PARA LA FIRMA ---
    // Esta función se llama manualmente al final del documento
    function imprimirFirma()
    {
        // Verificamos en qué posición Y quedó el puntero después de la tabla.
        // La firma empieza en Y=215 (según tu imagen). 
        // Si estamos más abajo de Y=200, agregamos página para no chocar.
        if ($this->GetY() > 200) {
            $this->AddPage();
        }

        // Dibujamos la firma en posiciones fijas (como lo tenías antes)
        $this->Image('img/fima_fran.png', 80, 215, 50); 
        
        $this->SetFont('Arial', '', 10); 
        $this->SetTextColor(0, 0, 0); 
        
        $this->SetXY(10, 245); 
        $this->MultiCell(0, 8, utf8_decode("____________________________."), 0, 'C');
        
        $this->SetFont('Arial', 'B', 10); 
        $this->SetXY(10, 250); 
        $this->MultiCell(0, 8, utf8_decode("VºBº Francisca Sanhueza Valenzuela."), 0, 'C');
    }

    // --- Funciones para tabla con altura automática ---

    function SetWidths($w) { $this->widths = $w; }
    function SetAligns($a) { $this->aligns = $a; }
    function SetLineHeight($h) { $this->lineHeight = $h; }

    function Row($data)
    {
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $h = $this->lineHeight * $nb;
        $this->CheckPageBreak($h);

        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, $this->lineHeight, $data[$i], 0, $a, false);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) { $w = $this->w - $this->rMargin - $this->x; }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") { $nb--; }
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue;
            }
            if ($c == ' ') { $sep = $i; }
            $char_width = isset($cw[$c]) ? $cw[$c] : $cw[ord('?')];
             $l += $char_width;
            if ($l > $wmax) {
                if ($sep == -1) { if ($i == $j) { $i++; } } else { $i = $sep + 1; }
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else { $i++; }
        }
        return $nl;
    }
}

// Crear nuevo objeto PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('P', 'Letter');
$pdf->SetFont('Arial','',12);
$pdf->SetLineHeight(6); 


// Obtener el ID
if (isset($_GET['id_oc'])) {
    $id_oc = $_GET['id_oc'];

    $sql = "SELECT
        oc.id_oc,
        oc.cotizacion,
        p.nombre_proveedor,
        p.rut_proveedor,
        p.direccion_proveedor,
        p.nombre_contacto,
        p.telefono_contacto,
        p.email_contacto,
        oc.forma_pago,
        ocd.item,
        ocd.cantidad,
        ocd.descripcion,
        ocd.precio,
        ocd.valor_neto,
        ocd.iva,
        ocd.total,
        oc.created_at
    FROM oc
    INNER JOIN oc_detalle ocd ON ocd.orden_id = oc.id_oc
    INNER JOIN proveedor p ON oc.id_proveedor = p.id_proveedor
    WHERE oc.id_oc = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_oc);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row_oc = $result->fetch_assoc();

        // --- DATOS DEL PROVEEDOR ---
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        // Fila 1
        $pdf->SetX(10); 
        $pdf->Cell(25, 5, 'Nombre', 0, 0); $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(25, 5, utf8_decode($row_oc['nombre_proveedor']), 0, 1); 

        // Fila 2
        $pdf->SetX(10); 
        $pdf->Cell(25, 5, 'Rut', 0, 0); $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(25, 5, utf8_decode($row_oc['rut_proveedor']), 0, 0); 

        $pdf->SetX(120); 
        $pdf->Cell(25, 5, 'Fecha', 0, 0); $pdf->Cell(5, 5, ':', 0, 0);
        $fechaTermino = new DateTime($row_oc['created_at']);
        $pdf->Cell(30, 5, utf8_decode($fechaTermino->format('d-m-Y')), 0, 1);

        // Fila 3
        $pdf->SetX(10); 
        $pdf->Cell(25, 5, utf8_decode('Dirección'), 0, 0); $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(25, 5, utf8_decode($row_oc['direccion_proveedor']), 0, 1); 

        // Fila 4
        $pdf->SetX(10); 
        $pdf->Cell(25, 5, utf8_decode('Teléfono'), 0, 0); $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(25, 5, utf8_decode($row_oc['telefono_contacto']), 0, 0); 

        $pdf->SetX(120); 
        $pdf->Cell(25, 5, 'Cond.Pago', 0, 0); $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(30, 5, utf8_decode($row_oc['forma_pago']), 0, 1);
        $pdf->SetFont('Arial', '', 10);

        // Fila 5
        $pdf->SetX(10); 
        $pdf->Cell(25, 5, 'Contacto', 0, 0); $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(30, 5, utf8_decode($row_oc['nombre_contacto']), 0, 0);

        $pdf->SetX(120); 
        $pdf->Cell(25, 5, 'Correo', 0, 0); $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(30, 5, utf8_decode($row_oc['email_contacto']), 0, 1);

        // Fila 6
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetX(10); 
        $pdf->Cell(25, 5, utf8_decode('Cotización Nº'), 0, 0); $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(25, 5, utf8_decode($row_oc['cotizacion']), 0, 0); 
        $pdf->SetFont('Arial', '', 10);

        $pdf->Ln(5);
        $pdf->SetLeftMargin(10);
        $pdf->Ln(5);

        // Título "DETALLE DE ITEMS"
        $pdf->SetFillColor(79, 113, 190);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 8, utf8_decode('DETALLE DE ITEMS'), 0, 1, 'C', true);
        $pdf->Ln(5);
        
        // --- TABLA ITEMS ---
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->SetFont('Arial', 'B', 10);

        $pdf->SetWidths(array(15, 100, 20, 30, 30)); 
        $pdf->SetAligns(array('C', 'L', 'C', 'R', 'R')); 

        $headerData = array('Item', utf8_decode('Descripción'), 'Cantidad', 'P. Unitario', 'TOTAL');
        $pdf->Row($headerData);

        $pdf->SetFont('Arial', '', 10); 

        $result->data_seek(0); 
        $total_neto_sum = 0;
        $total_iva_sum = 0;
        $total_valor_sum = 0;

        while ($row_servicio = $result->fetch_assoc()) {
             $rowData = array(
                utf8_decode($row_servicio['item']),
                utf8_decode($row_servicio['descripcion']), 
                utf8_decode($row_servicio['cantidad']),
                '$' . number_format($row_servicio['precio'], 0, ',', '.'). '.-',
                '$' . number_format($row_servicio['valor_neto'], 0, ',', '.'). '.-'
             );
            $pdf->Row($rowData);

            $total_neto_sum += $row_servicio['valor_neto'];
            $total_iva_sum += $row_servicio['iva'];
            $total_valor_sum += $row_servicio['total'];
        }

        // --- FILAS DE TOTALES ---
        $pdf->SetFont('Arial', 'B', 10); 

        $width_combined_neto = $pdf->widths[0] + $pdf->widths[1] + $pdf->widths[2] + $pdf->widths[3]; 
        $pdf->Cell($width_combined_neto, 8, 'TOTAL NETO', 1, 0, 'R');
        $pdf->Cell($pdf->widths[4], 8, '$' . number_format($total_neto_sum, 0, ',', '.') . '.-', 1, 1, 'R'); 

        $pdf->Cell($width_combined_neto, 8, 'I.V.A. (' . number_format(($total_iva_sum / ($total_neto_sum > 0 ? $total_neto_sum : 1) * 100), 0) . '%)', 1, 0, 'R');
        $pdf->Cell($pdf->widths[4], 8, '$' . number_format($total_iva_sum, 0, ',', '.') . '.-', 1, 1, 'R');

        $pdf->Cell($width_combined_neto, 8, 'TOTAL', 1, 0, 'R');
        $pdf->Cell($pdf->widths[4], 8, '$' . number_format($total_valor_sum, 0, ',', '.') . '.-', 1, 1, 'R');
        
        // --- MENSAJE FINAL Y FIRMA ---
        $pdf->Ln(10); 
        $pdf->SetFont('Arial', '', 9.5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(0, 8, utf8_decode("Atentamente."), 0, 'L');
        
        // ** AQUÍ LLAMAMOS A LA FIRMA PARA QUE APAREZCA SOLO AL FINAL **
        $pdf->imprimirFirma();

    } else {
        $pdf->SetFont('Arial', '', 12); 
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, 'No se encontraron resultados para este OC.', 0, 1);
    }

    $stmt->close();
} else {
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'No se proporcionó un ID de OC.', 0, 1);
}

$conn->close();

$fileName = 'FFYJ_OC_';
if (isset($row_oc['id_oc'])) {
    $fileName .= $row_oc['id_oc'];
} else if (isset($id_oc)) {
    $fileName .= $id_oc; 
} else {
    $fileName .= 'SIN_ID';
}
$fileName .= '.pdf';

$pdf->Output('I', $fileName); 
?>