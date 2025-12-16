<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<?php include 'content/header.php'; ?>
<div class="container my-5">
    <h2 class="text-center">Gestión de Conductores</h2>
    
    <!-- Formulario para agregar conductores -->
    <form action="guardar_conductores.php" method="POST" class="mt-4">
        
            <h5 class="mt-4">Datos Personales</h5>
            <div class="form-floating row g-3">
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="rut_conductor" id="floatingInput" placeholder="12345678-9">
                        <label for="rut_conductor">Rut</label>
                    </div>
                </div>
                <div class="col-md">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="nombre_conductor" id="floatingInput" placeholder="Juan Perez">
                        <label for="nombre_conductor">Nombre Completo</label>
                    </div>
                </div>
                <div class="col-md"> 
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="telefono_conductor" id="floatingInput" placeholder="name@example.com">
                        <label for="telefono_conductor">Teléfono</label>
                    </div>
                </div>
            </div>

            <br>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Guardar Conductor</button>
                </div>
    </form>

    <!-- Listado de Conductores -->
    <h3 class="text-center mt-5">Listado de Conductores</h3>
    <?php
    include 'content/connect.php';
    $sql = "SELECT * FROM conductores";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo '<table class="table table-bordered table-striped mt-3">'; 
        echo '<thead class="table-primary">';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>RUT</th>';
        echo '<th>Nombre</th>';
        echo '<th>Celular</th>';
        echo '<th>Acción</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['id_conductor'] . '</td>';
            echo '<td>' . $row['rut_conductor'] . '</td>';
            echo '<td>' . $row['nombre_conductor'] . '</td>';
            echo '<td>' . $row['telefono_conductor'] . '</td>';

            // Botón de editar
            echo '<td><a href="editar_conductores.php?id_conductor=' . $row['id_conductor'] . '" class="btn btn-warning">Editar</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p class="text-center mt-4">No hay conducrores registrados.</p>';
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
            let rutInput = document.querySelector("input[name='rut_conductor']");
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
