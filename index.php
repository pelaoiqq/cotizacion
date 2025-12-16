<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<?php include 'content/header.php'; ?>
<div class="container my-5">
    <h2 class="text-center">Bienvenido al Sistema de Cotizaciones FFYJ</h2>

    <!-- Agregar la imagen aquí -->
<div class="text-center mt-4">
    <img src="img/ffyj03.jpeg" alt="FFYJ Servicios" class="img-fluid" width="800" />
</div>
</div>


<?php include 'content/footer.php'; ?>
