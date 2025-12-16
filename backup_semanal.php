<?php
// Token de seguridad
$clave_secreta = "2f03dbe33320d0b8fd60fd339347c01704f30852"; // Inventa una clave difícil

if (!isset($_GET['token']) || $_GET['token'] !== $clave_secreta) {
    die("Acceso denegado.");
}

// Cargar las clases de PHPMailer manualmente
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Asegúrate de que la carpeta se llame 'PHPMailer' y esté junto a este archivo
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// --- CONFIGURACIÓN BD ---
$host = 'localhost';
$user = 'ffy28_coti';
$pass = 'pAgj)6nk%H';
$name = 'ffy28_cotizacionffyj';
$ruta = __DIR__ . '/backups/'; // Asegúrate de crear esta carpeta

if (!is_dir($ruta)) {
    mkdir($ruta, 0755, true);
}

$fecha = date("Y-m-d_H-i-s");
$archivo = $ruta . "respaldo_$fecha.sql.gz";

// Agregamos --hex-blob para las imágenes y --default-character-set=utf8mb4 para los textos
$comando = "mysqldump --opt --hex-blob --default-character-set=utf8mb4 -h $host -u $user -p'$pass' $name | gzip > $archivo";
system($comando, $output);

// --- ENVÍO POR SMTP ---
if (file_exists($archivo)) {
    echo "Respaldo creado correctamente: $archivo<br>";

    $mail = new PHPMailer(true);

    try {
        // 1. Configuración del Servidor
        $mail->isSMTP();
        $mail->Host       = 'olivillo.tchile.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'andres.angulo@ffyjservicios.cl'; 
        $mail->Password   = 'pAgj)6nk%H';
        $mail->SMTPSecure = 'TLS'; 
        $mail->Port       = 587;

        // --- CORRECCIÓN CRÍTICA PARA PHP 5.6 ---
        // Esto evita errores de certificado SSL en servidores antiguos
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        // ----------------------------------------

        // 2. Destinatarios
        $mail->setFrom('aandresangulo@gmail.com', 'Respaldo Cotización FFYJ');
        $mail->addAddress('andres.angulo@ffyjservicios.cl'); 

        // 3. Contenido
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8'; // Para que se vean bien las tildes
        $mail->Subject = 'Respaldo BD Cotización Semanal - ' . date("d/m/Y");
        $mail->Body    = 'Hola, adjunto el respaldo de la base de datos del <b>' . date("d/m/Y") . '</b>.';

        // 4. Adjuntar archivo
        $mail->addAttachment($archivo);

        $mail->send();
        echo 'Correo enviado exitosamente.';
        
    } catch (Exception $e) {
        echo "Error al enviar correo: {$mail->ErrorInfo}";
    }

} else {
    echo "Error: El archivo de respaldo no se generó.";
}
?>
