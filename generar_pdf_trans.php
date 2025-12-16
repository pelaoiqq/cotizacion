<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php
require('fpdf/fpdf.php');
include 'content/connect.php'; // Conexión a la base de datos

class PDF extends FPDF
{
    // Encabezado
    function Header()
    {
        // Logo (ajustar la ruta de la imagen)
        $this->Image('img/LOGO-FYJ.png', 15, 8, 25); // xx margen iz - xx margen sup - xx Tamaño // Ajusta la ruta del logo

        // Título del encabezado: Cotización Nº
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(79, 113, 190); // Establece el color del texto en RGB
        $this->Cell(0, 10, utf8_decode('Cotización Nº ') . $_GET['id_cotizacion'], 0, 1, 'R'); // Se muestra el número de cotización a la derecha
        $this->Ln(10); // Espacio después del encabezado
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-20);
        $this->SetTextColor(121, 125, 127); // Color #797d7f
        $this->SetFont('Arial','',10);
        $this->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1","Altos del Pacífico 2848, Cond. Altos del Sur III, Alto Hospicio \n Contacto: +569 963541816 \n www.ffyjservicios.cl - ffyj.servicios@gmail.com"),0,'R',false);
    }
}

// Verificar si se ha enviado el ID de la cotización
if (isset($_GET['id_cotizacion'])) {
    $id_cotizacion = intval($_GET['id_cotizacion']);

    // Consultar la cotización en la base de datos usando mysqli
    $sql = "SELECT c.id_cotizacion, 
            cl.nombre_cliente, 
            cl.rut_cliente,
            cl.direccion_cliente,
            cl.email_cliente, 
            cl.nombre_contacto, 
            cl.email_contacto, 
            cl.telefono_contacto, 
            c.origen, 
            c.destino, 
            c.fecha_inicio, 
            c.fecha_termino, 
            c.estado_servicio,
            c.detalle_servicios,
            c.created_at, 
            c.forma_pago, 
            vs.id_camiones, 
            cam.img_camion
    FROM cotizaciones c
    INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente
    INNER JOIN cotizacion_servicios cs ON c.id_cotizacion = cs.id_cotizacion
    INNER JOIN servicios s ON cs.id_servicio = s.id_servicios
    INNER JOIN varios_serv vs ON c.id_cotizacion = vs.id_cotizacion
    INNER JOIN camiones cam ON vs.id_camiones = cam.id_camiones
    WHERE c.id_cotizacion = ?";

    // Preparar la consulta
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_cotizacion);  // "i" indica que el parámetro es un entero
    $stmt->execute();

    // Obtener los resultados
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && !empty($row['img_camion'])) {
        $imagen_binaria = $row['img_camion'];

                // Obtener los servicios asociados a la cotización
                $sql_servicios = "SELECT s.nombre_servicio, cs.cantidad, cs.total_servicio, cs.precio, cs.servicio_manual, cs.detalle_servicios
                FROM cotizacion_servicios cs
                INNER JOIN servicios s ON cs.id_servicio = s.id_servicios
                WHERE cs.id_cotizacion = $id_cotizacion";
                $result_servicios = $conn->query($sql_servicios);


        // Crear la carpeta si no existe
        $ruta_carpeta = __DIR__ . '/imagenes_camiones';
        if (!is_dir($ruta_carpeta)) {
            mkdir($ruta_carpeta, 0777, true);
        }

        // Generar un nombre temporal para la imagen
        $nombre_imagen_temporal = uniqid('imagen_', true) . '.jpg';
        $ruta_imagen = $ruta_carpeta . '/' . $nombre_imagen_temporal;

        // Guardar la imagen en la carpeta
        file_put_contents($ruta_imagen, $imagen_binaria);

        // Verificar si la imagen es válida
        if (@getimagesize($ruta_imagen)) {
            // Crear el PDF
            $pdf = new PDF();
            $pdf->AddPage('P', 'Letter');

            // Título principal
            $pdf->Ln(-4); // Espaciado adicional después del título
            $pdf->SetFont('Arial', 'B', 12); // Cambiar tamaño y peso de la fuente
            $pdf->SetTextColor(79,113, 190);   // Cambiar el color a azul
            $pdf->Cell(0, 10, utf8_decode('TRANSPORTE DE CARGA, ARRIENDO DE MAQUINARIAS, REPARACIONES SPA'), 0, 1, 'C');
            $pdf->Ln(5); // Espaciado adicional después del título

            function truncarTexto($texto, $max, $sufijo = '...') {
    return (strlen($texto) > $max) ? substr($texto, 0, $max - strlen($sufijo)) . $sufijo : $texto;
}

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);

// === PRIMERA FILA: Cliente / Email ===
$pdf->SetX(10);
$pdf->Cell(20, 5, 'Cliente', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$cliente = utf8_decode($row['nombre_cliente']);
$pdf->Cell(85, 5, truncarTexto($cliente, 43), 0, 0); // Limitar a ~55 caracteres visibles

$pdf->SetX(125);
$pdf->Cell(25, 5, 'Email', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$email = utf8_decode($row['email_cliente']);
$pdf->Cell(60, 5, truncarTexto($email, 25), 0, 1); // Limitar a ~45 caracteres

// === SEGUNDA FILA: Rut / Teléfono ===
$pdf->SetX(10);
$pdf->Cell(20, 5, 'Rut', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(60, 5, utf8_decode($row['rut_cliente']), 0, 0);

$pdf->SetX(125);
$pdf->Cell(25, 5, utf8_decode('Teléfono'), 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(45, 5, utf8_decode($row['telefono_contacto']), 0, 1);

// === TERCERA FILA: Dirección ===
$pdf->SetX(10);
$pdf->Cell(20, 5, utf8_decode('Dirección'), 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$direccion = utf8_decode($row['direccion_cliente']);
$pdf->Cell(160, 5, truncarTexto($direccion, 95), 0, 1); // Limitar a ~95 caracteres

// === CUARTA FILA: Contacto / Email contacto ===
$pdf->SetX(10);
$pdf->Cell(20, 5, 'Contacto', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(70, 5, truncarTexto(utf8_decode($row['nombre_contacto']), 50), 0, 0);

$pdf->SetX(125);
$pdf->Cell(25, 5, 'Email Contacto', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$email_contacto = utf8_decode($row['email_contacto']);
$pdf->Cell(60, 5, truncarTexto($email_contacto, 25), 0, 1);

// === QUINTA FILA: Origen / Destino ===
$pdf->SetX(10);
$pdf->Cell(20, 5, 'Origen', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(70, 5, truncarTexto(utf8_decode($row['origen']), 50), 0, 0);

$pdf->SetX(125);
$pdf->Cell(25, 5, 'Destino', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(60, 5, truncarTexto(utf8_decode($row['destino']), 25), 0, 1);

// === SEXTA FILA: Fecha inicio / Fecha término ===
$pdf->SetX(10);
$pdf->Cell(20, 5, 'Fecha Inicio', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$fecha_ini = (new DateTime($row['fecha_inicio']))->format('d-m-Y');
$pdf->Cell(60, 5, $fecha_ini, 0, 0);

$pdf->SetX(125);
$pdf->Cell(25, 5, utf8_decode('Fecha Término'), 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$fecha_termino = (new DateTime($row['fecha_termino']))->format('d-m-Y');
$pdf->Cell(60, 5, $fecha_termino, 0, 1);

// === SÉPTIMA FILA: Creado el / Estado ===
$pdf->SetX(10);
$pdf->Cell(20, 5, 'Creado el', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$creado = new DateTime($row['created_at']);
$pdf->Cell(60, 5, $creado->format('d-m-Y') . ' a las ' . $creado->format('H:i'), 0, 0);

$pdf->SetX(125);
$pdf->Cell(25, 5, 'Estado', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(60, 5, truncarTexto(utf8_decode($row['estado_servicio']), 40), 0, 1);

$pdf->Ln(5);

        // Texto adicional
        $pdf->SetFont('Arial', '', 10); // Cambia la fuente si es necesario
        $pdf->MultiCell(0, 8, utf8_decode("Estimados:\nDe acuerdo con lo solicitado, se envía cotización por el servicio de Transporte:"), 0, 'L');
        $pdf->Ln(3); // Espacio después del texto

         // Texto con fondo y color de texto personalizados
        $pdf->SetFillColor(79, 113, 190); // Color de fondo
        $pdf->SetTextColor(255, 255, 255); // Color de texto blanco
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, utf8_decode('SERVICIO DE TRANSPORTE'), 0, 1, 'C', true);
        $pdf->Ln(5); // Espacio después del bloque

       // inicio imagen

            // Insertar la imagen del camión en el PDF
            //$pdf->Image($ruta_imagen, 60, 115, 95); // Aquí se muestra la imagen en el PDF
            $pdf->Image($ruta_imagen, 60, 113, 0, 55); // Ancho automático, altura fija
            $pdf->Ln(55); // Ajustar la posición vertical de los siguientes textos

            // Aquí puedes agregar más detalles de la cotización si lo deseas

             // Texto adicional (después de la imagen o del mensaje de advertencia)
        $pdf->SetFont('Arial', '', 10); // Cambia la fuente si es necesario
        $pdf->SetTextColor(0, 0, 0); // Color de texto negro
        $pdf->Ln(5); // Espacio adicional si es necesario


        // Texto con fondo y color de texto personalizados
        $pdf->SetFillColor(79, 113, 190); // Color de fondo
        $pdf->SetTextColor(255, 255, 255); // Color de texto blanco
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, utf8_decode('Descripción del Servicio'), 0, 1, 'L', true);
        $pdf->Ln(5); // Espacio después del bloque

        // Ajustar el margen izquierdo para el texto con viñetas
        $pdf->SetLeftMargin(0); // Reduce el margen izquierdo a 10 (o cualquier valor menor)
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0); // Texto negro
        // Contenido con viñetas
        $pdf->MultiCell(0, 5, utf8_decode($row['detalle_servicios']), 0, 'L');
     

        // Restaurar el margen izquierdo si es necesario para las siguientes secciones
        $pdf->SetLeftMargin(10); // Vuelve al margen predeterminado (20 por defecto en FPDF)
        $pdf->Ln(5); // Espacio después del bloque

         // Texto con fondo y color de texto personalizados
         $pdf->SetFillColor(79, 113, 190); // Color de fondo
         $pdf->SetTextColor(255, 255, 255); // Color de texto blanco
         $pdf->SetFont('Arial', 'B', 12);
         $pdf->Cell(0, 8, utf8_decode('Condiciones del Servicio'), 0, 1, 'L', true);
         $pdf->Ln(-1); // Espacio después del bloque
         
          // Servicios
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 0, 0); // Color de texto Negro
        $pdf->Cell(0, 10, 'Valor del Servicio: ', 0, 1);
        $pdf->SetFont('Arial', '', 10);

        if ($result_servicios->num_rows > 0) {
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(120, 8, 'Servicio', 1);
            $pdf->Cell(20, 8, 'Cantidad', 1, 0, 'C');
            $pdf->Cell(28, 8, 'Valor Unitario', 1, 0, 'C');
            $pdf->Cell(0, 8, 'Total Servicio', 1, 1, 'C');
        
            // Inicializar el total neto
            $total_neto = 0;
        
            // Recorrer los servicios
            while ($servicio = $result_servicios->fetch_assoc()) {
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(120, 8, utf8_decode($servicio['servicio_manual']), 1);
                $pdf->Cell(20, 8, utf8_decode($servicio['cantidad']), 1, 0, 'C');
                $pdf->Cell(28, 8, '$' . number_format($servicio['precio'], 0, '', '.') . '.-', 1, 0, 'R');
                $pdf->Cell(0, 8, '$' . number_format($servicio['total_servicio'], 0, '', '.') . '.-', 1, 1, 'R');
        
                // Acumular el precio en el total neto
                $total_neto += $servicio['total_servicio'];
            }
        
            // Mostrar el total neto al final
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(168, 8, 'Total Neto', 1);
            $pdf->Cell(0, 8, '$' . number_format($total_neto, 0, '', '.') . '.-', 1, 1, 'R');
        } else {
            $pdf->Cell(0, 10, 'No hay servicios asociados a esta cotizacion.', 0, 1);
        }
        


        // Ajustar el margen izquierdo para el texto con viñetas
        $pdf->SetLeftMargin(0); // Reduce el margen izquierdo a 10 (o cualquier valor menor)
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0); // Texto negro
        // Contenido con viñetas
        $pdf->MultiCell(0, 5, utf8_decode("
        -   Los valores indicados son NETOS.
        -   Seguro de carga como referencia 3.000 / 5.000 UF.
        -   No incluye carga y descarga de la mercadería en origen y destino.
        -   Cotización en base a peso y medidas entregadas por el cliente. Las variaciones pueden modificar los valores\n            (Hasta 25 TN).
        -   La sobreestadía se cobrará pasadas las 8 horas de recepcionados los camiones en la ciudad de origen destino\n            o aduana, y su valor es de $350.000 en rampla y $350.000 en cama baja, más IVA.
        -   El valor diario de la sobreestadía en faena minera es de $350.000 más Iva para rampla y cama baja.
        -   El valor diario de la sobreestadía de Modulo es de $ 600.000 más IVA.
        -   Los fletes falsos tienen un costo del 60% del valor cotizado.
        -   Conductores y vehículos autorizados para trabajar en altura geográfica.
            -   NOTA 1: Los servicios en cama baja o rampla sobredimensionada debe ser confirmados, vía mail, con a lo\n                               menos 48 horas de anticipación, a fin de solicitar los correspondientes permisos viales.
            -   NOTA 2: Valores no incluyen gastos de sobrepeso, los que, si fuere necesario, serán facturados una vez\n                               obtenida la correspondiente boleta de pago de Vialidad.

        "), 0, 'L');
        $pdf->Ln(-6); // Espacio después del bloque

        // Restaurar el margen izquierdo si es necesario para las siguientes secciones
        $pdf->SetLeftMargin(10); // Vuelve al margen predeterminado (20 por defecto en FPDF)


        // Texto con fondo y color de texto personalizados
        $pdf->SetFillColor(79, 113, 190); // Color de fondo
        $pdf->SetTextColor(255, 255, 255); // Color de texto blanco
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, utf8_decode('Costos por cuenta de FFYJ Servicios'), 0, 1, 'L', true);
        $pdf->Ln(-2); // Espacio después del bloque
        
        // Ajustar el margen izquierdo para el texto con viñetas
        $pdf->SetLeftMargin(0); // Reduce el margen izquierdo a 10 (o cualquier valor menor)
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0); // Texto negro
        // Contenido con viñetas
        $pdf->MultiCell(0, 5, utf8_decode("
        -	Equipo con operador certificado.
        -	Combustible .
        "), 0, 'L');
        $pdf->Ln(-2); // Espacio después del bloque

        // Restaurar el margen izquierdo si es necesario para las siguientes secciones
        $pdf->SetLeftMargin(10); // Vuelve al margen predeterminado (20 por defecto en FPDF)


        // Texto con fondo y color de texto personalizados
        $pdf->SetFillColor(79, 113, 190); // Color de fondo
        $pdf->SetTextColor(255, 255, 255); // Color de texto blanco
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, utf8_decode('Condiciones Comerciales'), 0, 1, 'L', true);
        $pdf->Ln(-2); // Espacio después del bloque

        // Ajustar el margen izquierdo para el texto con viñetas
        $pdf->SetLeftMargin(0); // Reduce el margen izquierdo
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0); // Texto negro

        // Contenido base con viñetas
        $texto_base = "
        -	Disponibilidad del equipo sujeta a coordinación.
        -	Cuenta: Cuenta Corriente N° 21984808 - 76.973.144-K - Banco BCI.
        -	Cotización válida por 15 días a contar de la fecha de emisión.
        -	A la aprobación de nuestra cotización se puede dar inicio al servicio enviando Orden de Compra a nombre
        de FYJ Servicios SPA - Rut. 76.973.144-K, Dirección Altos del Pacífico 2848, Cond. Altos del Sur III, Alto Hospicio,
        indicando número de cotización.
        ";

        // Obtener y limpiar forma de pago
        $forma_pago = strtolower(trim($row['forma_pago'])); // Para evitar errores por mayúsculas o espacios

        // Texto adicional según la forma de pago
        if ($forma_pago === 'crédito') {
            $texto_pago = "-	Se espera el estado de pago un máximo de 30 días, de lo contrario se da automáticamente término al arriendo\n          del equipo.";
        } elseif ($forma_pago === 'contado') {
            $texto_pago = "-	Se requiere el 50% del pago adelantado al inicio del servicio y el 50% restante al término del servicio.";
        } else {
            $texto_pago = "-	Forma de pago no especificada correctamente.";
        }

        // Combinar todo
        $pdf->MultiCell(0, 5, utf8_decode($texto_base . $texto_pago), 0, 'L');
        $pdf->Ln(2); // Espacio después del bloque

        // Restaurar margen para la siguiente sección
        $pdf->SetLeftMargin(18);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->MultiCell(0, 5, '- Forma de Pago : ' . utf8_decode($row['forma_pago']), 0, 'L');
        $pdf->Ln(5);


         // Texto adicional
         $pdf->SetFont('Arial', '', 10); // Cambia la fuente si es necesario
         $pdf->SetTextColor(0, 0, 0); // Color de texto Negro
         $pdf->MultiCell(0, 8, utf8_decode("Esperamos atentos sus comentarios."), 0, 'L');
         $pdf->Ln(5); // Espacio después del texto

         $pdf->Image('img/fima_fran.png', 80, 220, 50); // X = 10mm, Y = 50mm, ancho = 50mm

            // Guardar y mostrar el PDF
            $pdf->Output('I', 'FFYJ_Cotizacion_' . $row['id_cotizacion'] . '.pdf');
        } else {
            // Evita cualquier salida antes de este punto
            // echo "❌ La imagen no es válida.";
        }
    } else {
        // Evita cualquier salida antes de este punto
        // echo "❌ No se encontró la imagen del camión para esta cotización.";
    }
} else {
    // Evita cualquier salida antes de este punto
    // echo "❌ No se ha proporcionado el ID de la cotización.";
}
?>
