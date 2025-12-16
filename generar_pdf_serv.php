<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
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
        $this->Image('img/logo.jpg', 10, 5, 30); // Ajusta la ruta del logo

        // Título del encabezado: Cotización Nº
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(79, 113, 190); // Establece el color del texto en RGB
        $this->Cell(0, 10, utf8_decode('Cotización Nº ') . $_GET['id_cotizacion'], 0, 1, 'R'); // Se muestra el número de cotización a la derecha
        $this->Ln(5); // Espacio después del encabezado
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

        // Consultar la cotización en la base de datos 
        $sql = "SELECT c.id_cotizacion, 
        cl.nombre_cliente, 
        cl.email_cliente, 
        cl.rut_cliente,
        cl.direccion_cliente,
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
        cs.servicio_ffyj, 
        c.forma_pago, 
        s.imagen_servicios
    FROM cotizaciones c
    INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente
    INNER JOIN cotizacion_servicios cs ON c.id_cotizacion = cs.id_cotizacion
    INNER JOIN servicios s ON cs.id_servicio = s.id_servicios
    WHERE c.id_cotizacion = $id_cotizacion";


    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $cotizacion = $result->fetch_assoc();

        // Obtener los servicios asociados a la cotización
        $sql_servicios = "SELECT s.nombre_servicio, s.imagen_servicios, cs.cantidad, cs.total_servicio, cs.precio, cs.detalle_servicios, cs.servicio_manual, cs.servicio_ffyj
                        FROM cotizacion_servicios cs
                        INNER JOIN servicios s ON cs.id_servicio = s.id_servicios
                        WHERE cs.id_cotizacion = $id_cotizacion";
        $result_servicios = $conn->query($sql_servicios);

        // Crear el PDF
        $pdf = new PDF();
        $pdf->AddPage('P', 'Letter');
        
         // Título principal
         $pdf->Ln(5); // Espaciado adicional después del título
         $pdf->SetFont('Arial', 'B', 12); // Cambiar tamaño y peso de la fuente
         $pdf->SetTextColor(79,113, 190);   // Cambiar el color a azul
         $pdf->Cell(0, 10, utf8_decode('TRANSPORTE DE CARGA, ARRIENDO DE MAQUINARIAS, REPARACIONES SPA'), 0, 1, 'C');
         $pdf->Ln(5); // Espaciado adicional después del título

        // Información de la cotización en dos columnas
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0); // Color de texto Negro

        // Primera columna
        $pdf->SetX(10); // Posición inicial de la primera columna
        $pdf->Cell(20, 5, 'Cliente', 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(25, 5, utf8_decode($cotizacion['nombre_cliente']), 0, 0); // Mantener en la misma línea

        $pdf->SetX(125); // Posición inicial de la segunda columna
        $pdf->Cell(25, 5, 'Email', 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(30, 5, utf8_decode($cotizacion['email_cliente']), 0, 1);
        // Segunda columna
        $pdf->SetX(10); // Posición inicial de la primera columna
        $pdf->Cell(20, 5, 'Rut', 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(25, 5, utf8_decode($cotizacion['rut_cliente']), 0, 0); // Mantener en la misma línea

        $pdf->SetX(125); // Posición inicial de la segunda columna
        $pdf->Cell(25, 5, utf8_decode('Teléfono'), 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(30, 5, utf8_decode($cotizacion['telefono_contacto']), 0, 1);

            // Primera columna
        $pdf->SetX(10); // Posición inicial de la primera columna
        $pdf->Cell(20, 5, utf8_decode('Dirección'), 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(25, 5, utf8_decode($cotizacion['direccion_cliente']), 0, 1);

         // Segunda columna
         $pdf->SetX(10); // Posición inicial de la primera columna
         $pdf->Cell(20, 5, 'Contacto', 0, 0);
         $pdf->Cell(5, 5, ':', 0, 0);
         $pdf->Cell(25, 5, utf8_decode($cotizacion['nombre_contacto']), 0, 0); // Mantener en la misma línea
 
         $pdf->SetX(125); // Posición inicial de la segunda columna
         $pdf->Cell(25, 5, 'Email Contacto', 0, 0);
         $pdf->Cell(5, 5, ':', 0, 0);
         $pdf->Cell(30, 5, utf8_decode($cotizacion['email_contacto']), 0, 1);

        // Cuarta fila
        $pdf->SetX(10);
        $pdf->Cell(20, 5, 'Fecha Inicio', 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        // Formatear la fecha de inicio
        $fecha_ini = new DateTime($cotizacion['fecha_inicio']);
        $hora_ini = $fecha_ini->format('H:i');
        $fecha_formateada_ini = $fecha_ini->format('d-m-Y');
        // Concatenar hora y fecha con salto de línea
        $fecha_completa_ini = $fecha_formateada_ini;
        // Imprimir la fecha formateada en el PDF
        $pdf->Cell(25, 5, utf8_decode($fecha_completa_ini), 0, 0);

        $pdf->SetX(125);
        $pdf->Cell(25, 5, 'Fecha Termino', 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        // Formatear la fecha de término
        $fechaTermino = new DateTime($cotizacion['fecha_termino']);
        $fechaTerminoFormateada = $fechaTermino->format('d-m-Y');
        $pdf->Cell(30, 5, utf8_decode($fechaTerminoFormateada), 0, 1);

        // Quinta fila
        $pdf->SetX(10);
        $pdf->Cell(20, 5, 'Creado el', 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        // Formatear la fecha
        $fecha = new DateTime($cotizacion['created_at']);
        $hora = $fecha->format('H:i');
        $fecha_formateada = $fecha->format('d-m-Y');
        // Concatenar hora y fecha con salto de línea
        $fecha_completa = $fecha_formateada . "\n a las " . $hora;
        // Imprimir la fecha formateada en el PDF
        $pdf->Cell(25, 5, utf8_decode($fecha_completa), 0, 0);


        $pdf->SetX(125);
        $pdf->Cell(25, 5, 'Estado', 0, 0);
        $pdf->Cell(5, 5, ':', 0, 0);
        $pdf->Cell(30, 5, utf8_decode($cotizacion['estado_servicio']), 0, 1);

        $pdf->Ln(5); // Espacio adicional después de las columnas

        // Texto adicional
        $pdf->SetFont('Arial', '', 10); // Cambia la fuente si es necesario
        $pdf->MultiCell(0, 8, utf8_decode("Estimados:\nDe acuerdo con lo solicitado, se envía cotización por el siguiente servicio:"), 0, 'L');
        $pdf->Ln(3); // Espacio después del texto

        // Texto con fondo y color de texto personalizados
        $pdf->SetFillColor(79, 113, 190); // Color de fondo
        $pdf->SetTextColor(255, 255, 255); // Color de texto blanco
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, utf8_decode('Descripción del Servicio'), 0, 1, 'L', true);
        $pdf->Ln(2); // Espacio después del bloque

        // Ajustar el margen izquierdo para el texto con viñetas
        $pdf->SetLeftMargin(0); // Reduce el margen izquierdo a 10 (o cualquier valor menor)
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0); // Texto negro
        $pdf->SetLeftMargin(18); // Vuelve al margen predeterminado (20 por defecto en FPDF)
        $pdf->Ln(2); // Espacio después del bloque

        // Contenido con viñetas
        $pdf->MultiCell(0, 5, utf8_decode($cotizacion['detalle_servicios']), 0, 'L');

        // Restaurar el margen izquierdo si es necesario para las siguientes secciones
        $pdf->SetLeftMargin(10); // Vuelve al margen predeterminado (20 por defecto en FPDF)
        $pdf->Ln(2); // Espacio después del bloque
        $pdf->Ln(2); // Espacio después del bloque

         // Texto con fondo y color de texto personalizados
         $pdf->SetFillColor(79, 113, 190); // Color de fondo
         $pdf->SetTextColor(255, 255, 255); // Color de texto blanco
         $pdf->SetFont('Arial', 'B', 12);
         $pdf->Cell(0, 8, utf8_decode('Condiciones Generales del Servicio'), 0, 1, 'L', true);
         
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
        // Servicios
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 0, 0); // Color de texto Negro
        $pdf->Cell(0, 10, 'Valores Neto', 0, 1);
        $pdf->SetFont('Arial', '', 10);

        // Restaurar el margen izquierdo si es necesario para las siguientes secciones
        $pdf->SetLeftMargin(10); // Vuelve al margen predeterminado (20 por defecto en FPDF)
        $pdf->Ln(4); // Espacio después del bloque

        // Texto con fondo y color de texto personalizados
        $pdf->SetFillColor(79, 113, 190); // Color de fondo
        $pdf->SetTextColor(255, 255, 255); // Color de texto blanco
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, utf8_decode('Condiciones Comerciales'), 0, 1, 'L', true);

        // Ajustar el margen izquierdo para el texto con viñetas
        $pdf->SetLeftMargin(0); // Reduce el margen izquierdo a 10 (o cualquier valor menor)
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0); // Texto negro
        // Contenido con viñetas
        $pdf->MultiCell(0, 5, utf8_decode("
        -	Disponibilidad sujeta a coordinación.
        -	Cuenta: Cuenta Corriente N° 21984808 - 76.973.144-K - Banco BCI.
        -	Cotización válida por 15 días a contar de la fecha de emisión.
        -	A la aprobación de nuestra cotización se puede dar inicio al servicio enviando Orden de Compra a nombre\n          de FYJ Servicios SPA - Rut. 76.973.144-K, Dirección Altos del Pacífico 2848, Cond. Altos del Sur III, Alto Hospicio,\n          indicando número de cotización.
        "), 0, 'L');
        $pdf->Ln(-5); // Espacio después del bloque

        // Restaurar el margen izquierdo si es necesario para las siguientes secciones
        $pdf->SetLeftMargin(18); // Vuelve al margen predeterminado (20 por defecto en FPDF)
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->MultiCell(0, 5, '- Forma de Pago : ' . utf8_decode($cotizacion['forma_pago']), 0, 'L');
        $pdf->Ln(5); // Espacio después del bloque


         // Texto adicional
         $pdf->SetFont('Arial', '', 10); // Cambia la fuente si es necesario
         $pdf->SetTextColor(0, 0, 0); // Color de texto Negro
         $pdf->MultiCell(0, 8, utf8_decode("Esperamos atentos sus comentarios."), 0, 'L');
         $pdf->Ln(5); // Espacio después del texto

         $pdf->Image('img/fima_fran.png', 70,225, 50); // X = 10mm, Y = 50mm, ancho = 50mm



        // Guardar y mostrar el PDF
        $pdf->Output('I', 'FFYJ_Cotizacion_' . $cotizacion['id_cotizacion'] . '.pdf');
    } else {
        echo 'No se encontró la cotización.';
    }
} else {
    echo 'ID de cotización no proporcionado.';
}
?>