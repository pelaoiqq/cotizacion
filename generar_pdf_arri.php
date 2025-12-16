<?php
require('fpdf/fpdf.php');
include 'content/connect.php'; 

class PDF extends FPDF
{
    function Header()
    {
        if(file_exists('img/LOGO-FYJ.png')){
             $this->Image('img/LOGO-FYJ.png', 15, 8, 25);
        }
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(79, 113, 190);
        $this->Cell(0, 10, utf8_decode('Cotización Nº ') . (isset($_GET['id_cotizacion']) ? $_GET['id_cotizacion'] : ''), 0, 1, 'R');
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-20);
        $this->SetTextColor(121, 125, 127);
        $this->SetFont('Arial','',10);
        $this->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1",
        "Altos del Pacífico 2848, Cond. Altos del Sur III, Alto Hospicio \n Contacto: +569 963541816 \n www.ffyjservicios.cl - ffyj.servicios@gmail.com"),0,'R',false);
    }

    function RedibujarEncabezadosTabla()
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(120, 8, 'Servicio', 1);
        $this->Cell(20, 8, 'Cantidad', 1, 0, 'C');
        $this->Cell(28, 8, 'Valor Unitario', 1, 0, 'C');
        $this->Cell(0, 8, 'Total Servicio', 1, 1, 'C');
        $this->SetFont('Arial', '', 10);
    }

    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb-1] == "\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if ($c == ' ') $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) { if ($i == $j) $i++; } else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }
}

// -------------------- LÓGICA PRINCIPAL --------------------------

if (isset($_GET['id_cotizacion'])) {
    $id_cotizacion = intval($_GET['id_cotizacion']);

    // --- CAMBIO 1: Agregamos c.moneda y c.valor_uf a la consulta ---
    $sql = "SELECT c.id_cotizacion, cl.nombre_cliente, cl.email_cliente, cl.rut_cliente,
            cl.direccion_cliente, cl.nombre_contacto, cl.email_contacto, cl.telefono_contacto,
            c.origen, c.destino, c.fecha_inicio, c.fecha_termino, c.estado_servicio, c.created_at,
            c.forma_pago, c.moneda, c.valor_uf,
            vs.id_camiones, cam.img_camion
            FROM cotizaciones c
            INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente
            LEFT JOIN varios_serv vs ON c.id_cotizacion = vs.id_cotizacion
            LEFT JOIN camiones cam ON vs.id_camiones = cam.id_camiones
            WHERE c.id_cotizacion = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_cotizacion);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        
        // --- CAMBIO 2: Lógica para determinar formato de moneda ---
        $es_uf = (isset($row['moneda']) && $row['moneda'] === 'UF');
        
        // Configuración de formato
        if ($es_uf) {
            $simbolo_moneda = 'UF ';
            $decimales = 2; // La UF usa 2 decimales
            $separador_decimal = ',';
            $separador_miles = '.';
            $texto_moneda = "VALORES EXPRESADOS EN UF (Valor referencial: $" . number_format($row['valor_uf'], 2, ',', '.') . ")";
        } else {
            $simbolo_moneda = '$';
            $decimales = 0; // El peso chileno no usa decimales visuales usualmente
            $separador_decimal = '';
            $separador_miles = '.';
            $texto_moneda = "Valor del Servicio:";
        }

        $pdf = new PDF();
        $pdf->AddPage('P', 'Letter');
        $pdf->Ln(-4);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(79,113, 190);
        $pdf->Cell(0, 10, utf8_decode('TRANSPORTE DE CARGA, ARRIENDO DE MAQUINARIAS, REPARACIONES SPA'), 0, 1, 'C');
        $pdf->Ln(5);

        // --- DATOS DEL CLIENTE (Igual que antes) ---
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetX(10); $pdf->Cell(20, 5, 'Cliente', 0, 0); $pdf->Cell(5, 5, ':', 0, 0); 
        $pdf->Cell(25, 5, utf8_decode(substr($row['nombre_cliente'], 0, 80)), 0, 0);
        $pdf->SetX(125); $pdf->Cell(25, 5, 'Email', 0, 0); $pdf->Cell(5, 5, ':', 0, 0); 
        $pdf->Cell(30, 5, utf8_decode($row['email_cliente']), 0, 1);

        $pdf->SetX(10); $pdf->Cell(20, 5, 'Rut', 0, 0); $pdf->Cell(5, 5, ':', 0, 0); 
        $pdf->Cell(25, 5, utf8_decode($row['rut_cliente']), 0, 0);
        $pdf->SetX(125); $pdf->Cell(25, 5, utf8_decode('Teléfono'), 0, 0); $pdf->Cell(5, 5, ':', 0, 0); 
        $pdf->Cell(30, 5, utf8_decode($row['telefono_contacto']), 0, 1);

        $pdf->SetX(10); $pdf->Cell(20, 5, utf8_decode('Dirección'), 0, 0); $pdf->Cell(5, 5, ':', 0, 0); 
        $pdf->Cell(25, 5, utf8_decode($row['direccion_cliente']), 0, 1);

        $pdf->SetX(10); $pdf->Cell(20, 5, 'Contacto', 0, 0); $pdf->Cell(5, 5, ':', 0, 0); 
        $pdf->Cell(25, 5, utf8_decode($row['nombre_contacto']), 0, 0);
        $pdf->SetX(125); $pdf->Cell(25, 5, 'Email Contacto', 0, 0); $pdf->Cell(5, 5, ':', 0, 0); 
        $pdf->Cell(30, 5, utf8_decode($row['email_contacto']), 0, 1);

        $pdf->SetX(10); $pdf->Cell(20, 5, 'Origen', 0, 0); $pdf->Cell(5, 5, ':', 0, 0); 
        $pdf->Cell(25, 5, utf8_decode($row['origen']), 0, 0);
        $pdf->SetX(125); $pdf->Cell(25, 5, 'Destino', 0, 0); $pdf->Cell(5, 5, ':', 0, 0); 
        $pdf->Cell(30, 5, utf8_decode($row['destino']), 0, 1);

        $pdf->SetX(10); $pdf->Cell(20, 5, 'Fecha Inicio', 0, 0); $pdf->Cell(5, 5, ':', 0, 0);
        $fecha_ini = new DateTime($row['fecha_inicio']);
        $pdf->Cell(25, 5, utf8_decode($fecha_ini->format('d-m-Y')), 0, 0);
        $pdf->SetX(125); $pdf->Cell(25, 5, 'Fecha Termino', 0, 0); $pdf->Cell(5, 5, ':', 0, 0);
        $fechaTermino = new DateTime($row['fecha_termino']);
        $pdf->Cell(30, 5, utf8_decode($fechaTermino->format('d-m-Y')), 0, 1);

        $pdf->SetX(10); $pdf->Cell(20, 5, 'Creado el', 0, 0); $pdf->Cell(5, 5, ':', 0, 0);
        $fecha = new DateTime($row['created_at']);
        $pdf->Cell(25, 5, utf8_decode($fecha->format('d-m-Y H:i')), 0, 0);
        $pdf->SetX(125); $pdf->Cell(25, 5, 'Estado', 0, 0); $pdf->Cell(5, 5, ':', 0, 0); 
        $pdf->Cell(30, 5, utf8_decode($row['estado_servicio']), 0, 1);

        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 8, utf8_decode("Estimados:\nDe acuerdo con lo solicitado, se envía cotización por el servicio de Arriendo:"), 0, 'L');
        $pdf->Ln(3);

        // --- IMAGEN CAMIÓN ---
        if (!empty($row['img_camion'])) {
            $pdf->SetFillColor(79, 113, 190);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 8, utf8_decode('ARRIENDO DE EQUIPO'), 0, 1, 'C', true);
            $pdf->Ln(5);

            $ruta_carpeta = __DIR__ . '/imagenes_camiones';
            if (!is_dir($ruta_carpeta)) mkdir($ruta_carpeta, 0777, true);
            $ruta_imagen = $ruta_carpeta . '/' . uniqid('temp_', true) . '.jpg';
            file_put_contents($ruta_imagen, $row['img_camion']);

            if (@getimagesize($ruta_imagen)) {
                $pdf->Image($ruta_imagen, 60, 115, 95);
                $pdf->Ln(65);
                unlink($ruta_imagen); 
            } else {
                 $pdf->Ln(10);
            }
        } else {
             $pdf->Ln(5);
        }

        // --- TABLA DE SERVICIOS ---
        $pdf->SetFillColor(79, 113, 190);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, utf8_decode('Condiciones del Servicio'), 0, 1, 'L', true);
        $pdf->Ln(3);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        
        // --- CAMBIO 3: Mostrar texto dinámico según moneda ---
        $pdf->Cell(0, 10, utf8_decode($texto_moneda), 0, 1);
        
        $pdf->RedibujarEncabezadosTabla();
        $pdf->SetFont('Arial', '', 9.5);

        $sql_servicios = "SELECT s.nombre_servicio, cs.cantidad, cs.total_servicio, cs.precio, cs.servicio_manual
                          FROM cotizacion_servicios cs
                          LEFT JOIN servicios s ON cs.id_servicio = s.id_servicios
                          WHERE cs.id_cotizacion = $id_cotizacion";
        $result_servicios = $conn->query($sql_servicios);

        $total_neto = 0;
        if($result_servicios){
            while ($servicio = $result_servicios->fetch_assoc()) {
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $ancho_servicio = 120;
                $line_height = 4;

                $nombre_mostrar = !empty($servicio['servicio_manual']) ? $servicio['servicio_manual'] : $servicio['nombre_servicio'];

                $alto_estimado = $pdf->NbLines($ancho_servicio, utf8_decode($nombre_mostrar)) * $line_height;
                $alto_fila = max(8, $alto_estimado);

                if ($y + $alto_fila > $pdf->GetPageHeight() - 30) {
                    $pdf->AddPage('P', 'Letter');
                    $pdf->RedibujarEncabezadosTabla();
                    $x = $pdf->GetX();
                    $y = $pdf->GetY();
                }

                $pdf->MultiCell($ancho_servicio, $line_height, utf8_decode($nombre_mostrar), 0, 'L');
                $altura_real = $pdf->GetY() - $y;

                $pdf->Rect($x, $y, $ancho_servicio, max(8, $altura_real));
                $pdf->SetXY($x + $ancho_servicio, $y);
                
                // --- CAMBIO 4: Aplicar formato UF/CLP a las celdas ---
                $precio_formateado = $simbolo_moneda . number_format($servicio['precio'], $decimales, $separador_decimal, $separador_miles);
                $total_formateado  = $simbolo_moneda . number_format($servicio['total_servicio'], $decimales, $separador_decimal, $separador_miles);

                $pdf->Cell(20, max(8, $altura_real), $servicio['cantidad'], 1, 0, 'C');
                $pdf->Cell(28, max(8, $altura_real), $precio_formateado, 1, 0, 'R');
                $pdf->Cell(0, max(8, $altura_real), $total_formateado, 1, 1, 'R');

                $total_neto += $servicio['total_servicio'];
            }
        }

        // --- CAMBIO 5: Aplicar formato UF/CLP al Total Neto ---
        $total_neto_formateado = $simbolo_moneda . number_format($total_neto, $decimales, $separador_decimal, $separador_miles);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(168, 8, 'Total Neto', 1);
        $pdf->Cell(0, 8, $total_neto_formateado, 1, 1, 'R');

        // --- RESTO DEL PDF (Sin cambios de lógica, solo de impresión) ---
        $pdf->SetLeftMargin(10);
        $pdf->Ln(10);

        $pdf->SetFillColor(79, 113, 190);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, utf8_decode('Costos por cuenta de FFYJ Servicios'), 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(2);
        $pdf->SetLeftMargin(10);
        $pdf->MultiCell(0, 5, utf8_decode("- Documentación vigente y seguros propios del equipo.\n- Certificación de mantenciones correspondientes al equipo.\n- Operador capacitado para el servicio,\n- Mantenciones preventivas y programadas."), 0, 'L');
        $pdf->Ln(5);

        $pdf->SetFillColor(79, 113, 190);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetLeftMargin(10);
        $pdf->Cell(0, 8, utf8_decode('Costos por cuenta del Cliente'), 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(2);
        $pdf->SetLeftMargin(10); 
        $pdf->MultiCell(0, 5, utf8_decode("- Combustible para el equipo.\n- Lubricación de uso diario.\n- Movilización y desmovilización del equipo por reparación, mantención, cambio o recertificación y revisión técnica.\n- Daños producidos por mala operación o falta oportuna de mantención.\n- Información oportuna de horómetro para programación de mantención.\n- Mantenciones y reparaciones en terreno.\n- Reparación y/o reposición de neumáticos pinchados, rajados y/o cortados.\n- Acreditación y permisos para el ingreso del equipo a faenas."), 0, 'L');
        $pdf->Ln(5);

        $pdf->SetFillColor(79, 113, 190);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetLeftMargin(10);
        $pdf->Cell(0, 8, utf8_decode('Condiciones Comerciales'), 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(2);

        $texto_base = "- Disponibilidad del equipo sujeta a coordinación.\n- Cuenta: Cuenta Corriente N° 21984808 - 76.973.144-K - Banco BCI.\n- Cotización válida por 15 días a contar de la fecha de emisión.\n- A la aprobación de nuestra cotización se puede dar inicio al servicio enviando Orden de Compra a nombre de FYJ Servicios SPA - Rut. 76.973.144-K, Dirección Altos del Pacífico 2848, Cond. Altos del Sur III, Alto Hospicio, indicando número de cotización.\n";

        $forma_pago = strtolower(trim($row['forma_pago']));
        if ($forma_pago === 'crédito') {
            $texto_pago = "- Se espera el estado de pago un máximo de 30 días, de lo contrario se da automáticamente término al arriendo del equipo.";
        } elseif ($forma_pago === 'contado') {
            $texto_pago = "- Se requiere el 50% del pago adelantado al inicio del servicio y el 50% restante al término del servicio.";
        } else {
            $texto_pago = "";
        }

        $pdf->MultiCell(0, 5, utf8_decode($texto_base . $texto_pago), 0, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->MultiCell(0, 5, '- Forma de Pago : ' . utf8_decode($row['forma_pago']), 0, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 8, utf8_decode("Esperamos atentos sus comentarios."), 0, 'L');
        $pdf->Ln(5);

        if(file_exists('img/fima_fran.png')){
             $pdf->Image('img/fima_fran.png', 80, $pdf->GetY(), 50); 
        }

        $pdf->Output('I', 'FFYJ_Cotizacion_' . $row['id_cotizacion'] . '.pdf');

    } else {
        echo "<h2 style='color:red; text-align:center;'>Error: Cotización no encontrada.</h2>";
    }

} else {
    echo "ID no proporcionado.";
}
?>