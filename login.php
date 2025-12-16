<?php
$mensaje = '';

// 1. Mensaje de Timeout (Sesión expirada)
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $mensaje = '<div class="alert alert-warning text-center" role="alert">
                    <i class="bi bi-clock-history"></i> Tu sesión ha expirado por inactividad.<br>Ingresa nuevamente.
                </div>';
}

// 2. Mensajes de Error de Login
if (isset($_GET['error'])) {
    if ($_GET['error'] == 1) {
        // Datos incorrectos
        $mensaje = '<div class="alert alert-danger text-center" role="alert">
                        <i class="bi bi-exclamation-circle-fill"></i> Usuario o contraseña incorrectos.
                    </div>';
    } elseif ($_GET['error'] == 2) {
        // Cuenta suspendida (Nueva funcionalidad)
        $mensaje = '<div class="alert alert-danger text-center" role="alert">
                        <i class="bi bi-person-x-fill"></i> <strong>Cuenta Suspendida</strong><br>
                        Por favor, contacte al administrador.
                    </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="img/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="img/favicon/favicon-16x16.png">
    
    <title>Login FFYJ Servicios</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e9ecef;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 350px;
            text-align: center;
        }
        .login-container img {
            width: 120px; /* Ajustado */
            height: auto;
            margin-bottom: 20px;
            display: block; /* Centrado correcto */
            margin-left: auto;
            margin-right: auto;
        }
        .login-container h5 {
            color: #495057;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #1D75CE;
            border: none;
            padding: 12px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #155b99;
        }
        .version {
            margin-top: 25px;
            font-size: 12px;
            color: #adb5bd;
        }
        .alert {
            font-size: 14px;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        
        <!-- Logo -->
        <img src="img/LOGO-FYJ.png" alt="Logo FFYJ">
        
        <!-- Título -->
        <h5>Sistema de Cotizaciones<br>FFYJ Servicios</h5>

        <!-- Mensajes de Alerta -->
        <?php echo $mensaje; ?>

        <!-- Formulario -->
        <form action="validate_login.php" method="POST">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" name="nombre_user" id="nombre_user" placeholder="Usuario" required autocomplete="username">
                <label for="nombre_user"><i class="bi bi-person"></i> Usuario</label>
            </div>
            
            <div class="form-floating mb-4">
                <input type="password" class="form-control" name="pass_user" id="pass_user" placeholder="Contraseña" required autocomplete="current-password">
                <label for="pass_user"><i class="bi bi-lock"></i> Contraseña</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">
                Ingresar <i class="bi bi-box-arrow-in-right"></i>
            </button>
        </form>

        <!-- Versión -->
        <div class="version">Versión 2.4 / 2025 VSC</div>

    </div>

    <!-- Scripts Bootstrap (Opcional si usas alertas dinámicas, pero bueno tenerlo) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>