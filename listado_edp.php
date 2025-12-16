<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include 'content/header.php';
include 'content/connect.php';

// Obtener lista de EDP agrupados por id_edp con cálculos
$query = "SELECT 
    e.id_edp, 
    c.nombre_cliente, 
    ds.detalle_servicio,
    e.fecha_inicio, 
    e.fecha_fin, 
    ds.total_neto,
    ds.id_cotizacion
FROM edp e
JOIN clientes c ON e.id_cliente = c.id_cliente
JOIN (
    SELECT 
        es.id_edp,
        GROUP_CONCAT(DISTINCT cs.detalle_servicio_principal SEPARATOR ', ') AS detalle_servicio,
        SUM(cs.total_valor) AS total_neto,
        GROUP_CONCAT(DISTINCT cs.id_cotizacion) AS id_cotizacion
    FROM edp_servicios es
    JOIN (
        SELECT 
            id_cotizacion,
            MIN(detalle_servicios) AS detalle_servicio_principal,
            SUM(total_servicio) * 1.19 AS total_valor
        FROM cotizacion_servicios
        GROUP BY id_cotizacion
    ) cs ON es.id_cotizacion = cs.id_cotizacion
    GROUP BY es.id_edp
) ds ON e.id_edp = ds.id_edp
ORDER BY e.id_edp DESC";

$result = $conn->query($query);
?>

<div class="container mt-4">
    <h2 class="text-center">Lista de Estados de Pago (EDP)</h2>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped mt-4">
            <thead class="table-primary">
                <tr>
                    <th style="width: 10px;">EDP</th>
                    <th>Cliente</th>
                    <th>Último Servicio</th>
                    <th style="width: 110px;">Fecha Inicio</th>
                    <th style="width: 110px;">Fecha Término</th>
                    <!--<th>Total Neto</th>                                                          
                    <th>IVA</th>
                    <th style="width: 110px;">Valor Total</th>-->
                    <th style="width: 100px;" colspan="2" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php 
                    $total_iva = $row['total_neto'] * 0.19;
                    $total_valor = $row['total_neto'] + $total_iva;
                    ?>
                    <tr>
                        <td><?= $row['id_edp'] ?></td>
                        <td><?= htmlspecialchars($row['nombre_cliente']) ?></td>
                        <td><?= htmlspecialchars($row['detalle_servicio']) ?></td>
                        <td class="text-end"><?= date('d-m-Y', strtotime($row['fecha_inicio'])) ?></td>
                        <td class="text-end"><?= date('d-m-Y', strtotime($row['fecha_fin'])) ?></td>
                        <!--<td class="text-end">$<?= number_format($row['total_neto'], 0, ',', '.') ?></td>
                        <td class="text-end">$<?= number_format($total_iva, 0, ',', '.') ?></td>
                        <td class="text-end">$<?= number_format($total_valor, 0, ',', '.') ?></td>-->
                        <td class="text-center"><a href="generar_pdf_edp.php?id_edp=<?= $row['id_edp'] ?>&id_cotizacion=<?= $row['id_cotizacion'] ?>" class="btn btn-success btn-sm" target="_blank">Imprimir</a></td>
                        <td class="text-center"><button class="btn btn-danger eliminar-btn btn-sm" data-id="<?php echo htmlspecialchars($row['id_edp']); ?>" data-bs-toggle="modal" data-bs-target="#confirmarEliminar">Eliminar</button></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center mt-3">No se encontraron registros de EDP.</p>
    <?php endif; ?>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmarEliminar" tabindex="-1" aria-labelledby="confirmarEliminarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmarEliminarLabel">Confirmar Eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="texto-confirmacion"></p>
      </div>
      <div class="modal-footer">
        <form action="eliminar_edp.php" method="POST" id="eliminarForm">
          <input type="hidden" name="id_edp" id="idEdpEliminar">
          <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
        </form>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancelar</button>
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function() {
    const eliminarButtons = document.querySelectorAll(".eliminar-btn");
    eliminarButtons.forEach(button => {
        button.addEventListener("click", function() {
            const idEdp = this.getAttribute("data-id");
            document.getElementById("idEdpEliminar").value = idEdp;
            document.getElementById("texto-confirmacion").innerText = `¿Estás seguro de que deseas eliminar el EDP  Nro. ${idEdp}?`;
        });
    });
});

</script>

<?php include 'content/footer.php'; ?>