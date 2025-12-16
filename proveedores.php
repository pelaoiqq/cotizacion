<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<?php include 'content/header.php'; ?>
<div class="container my-5">
    <h2 class="text-center">Gestión Proveedores</h2>
    
    <!-- Formulario para agregar proveedores -->
    <form action="guardar_proveedores.php" method="POST" class="mt-4">
        
            <h5 class="mt-4">Detalles de la Empresa</h5>
            <div class="form-floating row g-3"> 
                <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="rut_proveedor" id="floatingInput" placeholder="name@example.com">
                        <label for="rut_proveedor">Rut</label>
                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="nombre_proveedor" id="floatingInput" placeholder="name@example.com">
                        <label for="nombre_proveedor">Razón Social / Nombre Completo</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating">
                        <input type="email" class="form-control" name="correo_proveedor" id="floatingInput" placeholder="name@example.com">
                        <label for="correo_proveedor">Correo Electrónico</label>
                    </div>
                </div>
            </div>

            <div class="form-floating row g-3">
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="telefono_proveedor" id="floatingInput" placeholder="name@example.com">
                        <label for="telefono_proveedor">Teléfono Empresa</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="direccion_proveedor" id="floatingInput" placeholder="name@example.com">
                        <label for="direccion_proveedor">Dirección</label>
                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">

                    </div>
                </div>
            </div>
            <h5 class="mt-4">Detalles del Contacto</h5>
            <div class="form-floating row g-3"> 
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="nombre_contacto" id="floatingInput" placeholder="name@example.com">
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
                        <input type="text" class="form-control" name="email_contacto" id="floatingInput" placeholder="name@example.com">
                        <label for="email_contacto">Correo del Contacto</label>
                    </div>
                </div>
            </div>
            <br>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Guardar Proveedor</button>
                </div>
    </form>

    <!-- Listado de proveedores -->
    <h3 class="text-center mt-5">Lista de Proveedores</h3>
    <?php
    include 'content/connect.php';
    $sql = "SELECT * FROM proveedor";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo '<table class="table table-bordered table-striped mt-3">'; 
        echo '<thead class="table-primary">';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>RUT</th>';
        echo '<th>Nombre</th>';
        echo '<th>Email</th>';
        /*echo '<th>Teléfono</th>';
        echo '<th>Dirección</th>';*/
        echo '<th>Contacto</th>';
        echo '<th>Fono Contacto</th>';
        echo '<th>Correo Contacto</th>';
        echo '<th>Acción</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['id_proveedor'] . '</td>';
            echo '<td>' . $row['rut_proveedor'] . '</td>';
            echo '<td>' . $row['nombre_proveedor'] . '</td>';
            echo '<td>' . $row['correo_proveedor'] . '</td>';
            /*echo '<td>' . $row['telefono_proveedor'] . '</td>';
            echo '<td>' . $row['direccion_proveedor'] . '</td>';*/
            echo "<td>" . $row['nombre_contacto'] . "</td>";
            echo "<td>" . $row['telefono_contacto'] . "</td>";
            echo "<td>" . $row['email_contacto'] . "</td>";

            // Botón de editar
            echo '<td><a href="editar_proveedores.php?id_proveedor=' . $row['id_proveedor'] . '" class="btn btn-warning">Editar</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p class="text-center mt-4">No hay proveedor registrados.</p>';
    }
    $conn->close();
    ?>
    <script> // valida rut
    function validarRut(rut) {
        rut = rut.replace(/\./g, '').replace('-', ''); // Elimina puntos y guion
        if (rut.length < 8) return false; // Longitud mínima

        let cuerpo = rut.slice(0, -1);
        let dv = rut.slice(-1).toUpperCase();

        let suma = 0;
        let multiplicador = 2;

        for (let i = cuerpo.length - 1; i >= 0; i--) {
            suma += parseInt(cuerpo.charAt(i)) * multiplicador;
            multiplicador = multiplicador < 7 ? multiplicador + 1 : 2;
        }

        let dvEsperado = 11 - (suma % 11);
        dvEsperado = dvEsperado === 11 ? '0' : dvEsperado === 10 ? 'K' : dvEsperado.toString();

        return dv === dvEsperado;
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelector("form").addEventListener("submit", function (e) {
            let rutInput = document.querySelector("input[name='rut_proveedor']");
            if (!validarRut(rutInput.value)) {
                e.preventDefault(); // Evita el envío del formulario
                alert("RUT inválido. Verifica el formato y el dígito verificador.");
                rutInput.focus();
            }
        });
    });
    </script>
</div>
<?php include 'content/footer.php'; ?>
