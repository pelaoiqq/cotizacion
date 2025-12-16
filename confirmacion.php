<?php
session_start();
$id_cotizacion = isset($_SESSION['id_cotizacion']) ? $_SESSION['id_cotizacion'] : null;
$mensaje = isset($_SESSION['success']) ? $_SESSION['success'] : null;

// Limpiar variables de sesión
unset($_SESSION['success']);
unset($_SESSION['id_cotizacion']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización Guardada</title>
    <meta http-equiv="refresh" content="4;url=listado_cotizaciones.php">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to bottom, #1D75CE, #ffffff);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .confirmation-box {
            padding: 30px;
            border-radius: 12px;
            background: white;
            box-shadow: 0 0 15px rgba(0,0,0,0.15);
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }
        .logo {
            max-width: 120px;
            margin-bottom: 20px;
        }
        .checkmark {
            font-size: 64px;
            color: #28a745;
            animation: pop 0.6s ease-in-out;
        }
        @keyframes pop {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(-20px);}
            to {opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body>
    <div class="confirmation-box col-md-6">
        <img src="img/LOGO-FYJ.png" alt="Logo FYJ" class="logo">
        <div class="checkmark">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h4 class="mt-3">¡Cotización guardada con éxito!</h4>
        <?php if ($id_cotizacion): ?>
            <p>Número de cotización: <strong>#<?= $id_cotizacion ?></strong></p>
        <?php endif; ?>
        <p class="text-muted">Serás redirigido automáticamente en unos segundos...</p>
        <a href="listado_cotizaciones.php" class="btn btn-primary mt-3">Ir ahora</a>
    </div>
</body>
</html>
