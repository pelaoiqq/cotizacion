<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<?php include 'content/header.php'; ?>
<div class="container my-5">
    <h2 class="text-center">Gestión de Clientes</h2>
    
    <form action="guardar_cliente.php" method="POST" class="mt-4">
        
            <h5 class="mt-4">Detalles de la Empresa</h5>
            <div class="form-floating row g-3"> 
                <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="rut_cliente" id="floatingInput" placeholder="name@example.com">
                        <label for="rut_cliente">Rut</label>
                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="nombre_cliente" id="floatingInput" placeholder="name@example.com" style="text-transform: uppercase;">
                        <label for="nombre_cliente">Razón Social / Nombre Completo</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating">
                        <input type="email" class="form-control" name="email_cliente" id="floatingInput" placeholder="name@example.com" style="text-transform: uppercase;">
                        <label for="email_cliente">Correo Electrónico</label>
                    </div>
                </div>
            </div>

            <div class="form-floating row g-3">
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="telefono_cliente" id="floatingInput" placeholder="name@example.com">
                        <label for="telefono_cliente">Teléfono Empresa</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="direccion_cliente" id="floatingInput" placeholder="name@example.com" style="text-transform: uppercase;">
                        <label for="direccion_cliente">Dirección</label>
                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                    </div>
                </div>
            </div>

            <h5 class="mt-4">Detalles del Contacto</h5>
            <div class="form-floating row g-3"> <!-- Clientes -->
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="nombre_contacto" id="floatingInput" placeholder="name@example.com" style="text-transform: uppercase;">
                        <label for="nombre_contacto">Nombre del Contacto</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="telefono_contacto" id="floatingInput" placeholder="name@example.com">
                        <label for="telefono_contacto">Teléfono del Contacto</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="email_contacto" id="floatingInput" placeholder="name@example.com" style="text-transform: uppercase;">
                        <label for="email_contacto">Correo del Contacto</label>
                    </div>
                </div>
            </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                </div>
    </form>

    <h3 class="text-center mt-5">Lista de Clientes</h3>
    <?php
    include 'content/connect.php';
    
    $registros_por_pagina = 20;
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $inicio = ($pagina_actual - 1) * $registros_por_pagina;

    $sql = "SELECT * FROM clientes LIMIT $inicio, $registros_por_pagina";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo '<table class="table table-bordered table-striped mt-3">'; 
        echo '<thead class="table-primary">';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>RUT</th>';
        echo '<th>Nombre</th>';
        echo '<th>Email</th>';
        echo '<th>Teléfono</th>';
        echo '<th>Dirección</th>';
        echo '<th>Contacto</th>';
        echo '<th>Acción</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['id_cliente'] . '</td>';
            echo '<td>' . $row['rut_cliente'] . '</td>';
            echo '<td>' . $row['nombre_cliente'] . '</td>';
            echo '<td>' . $row['email_cliente'] . '</td>';
            echo '<td>' . $row['telefono_cliente'] . '</td>';
            echo '<td>' . $row['direccion_cliente'] . '</td>';
            echo '<td>' . $row['nombre_contacto'] . '</td>';
            echo '<td><a href="editar_cliente.php?id_cliente=' . $row['id_cliente'] . '" class="btn btn-warning">Editar</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';

        $sql_total = "SELECT COUNT(*) AS total FROM clientes";
        $result_total = $conn->query($sql_total);
        $total_registros = $result_total->fetch_assoc()['total'];
        $total_paginas = ceil($total_registros / $registros_por_pagina);

        echo '<nav><ul class="pagination justify-content-center">';
        for ($i = 1; $i <= $total_paginas; $i++) {
            $active = $pagina_actual == $i ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='?pagina=$i'>$i</a></li>";
        }
        echo '</ul></nav>';
    } else {
        echo '<p class="text-center mt-4">No hay clientes registrados.</p>';
    }
    $conn->close();
    ?>
</div>
<?php include 'content/footer.php'; ?>
