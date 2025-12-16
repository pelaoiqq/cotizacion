<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<?php
include 'content/header.php';
include 'content/connect.php';
require('fpdf/fpdf.php');

// Obtener parámetros de búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Consulta para obtener las órdenes de compra
$sql = "SELECT 
        oc.id_oc,
        p.nombre_proveedor,
        COUNT(ocd.item) AS cantidad_items,
        SUM(ocd.precio) AS suma_precio,
        SUM(ocd.valor_neto) AS suma_valor_neto,
        SUM(ocd.iva) AS suma_iva,
        SUM(ocd.total) AS suma_total,
        oc.created_at
    FROM oc
    INNER JOIN oc_detalle ocd ON ocd.orden_id = oc.id_oc
    INNER JOIN proveedor p ON oc.id_proveedor = p.id_proveedor
    GROUP BY oc.id_oc, p.nombre_proveedor, oc.created_at";

// Filtrar por proveedor si se proporciona un término de búsqueda
if (!empty($search)) {
    $sql .= " WHERE p.nombre_proveedor LIKE '%$search%'";
}

// Filtrar por rango de fechas
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    if (strpos($sql, "WHERE") === false) {
        $sql .= " WHERE oc.created_at BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } else {
        $sql .= " AND oc.created_at BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
}

$sql .= " ORDER BY oc.id_oc DESC";

$result = $conn->query($sql);

?>

    <div class="container my-5">
    <h2 class="text-center mb-4">Listado de Ordenes de Compra</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <table class="table table-bordered table-striped mt-3">
            <thead class="table-primary">
                <tr>
                    <th>Nº OC</th>
                    <th>Fecha Creación</th>
                    <th>Proveedor</th>
                    <!--<th>Descripción</th>-->
                    <th>Cantidad Items</th>
                    <!--<th>P. Unitario</th>-->
                    <th>Total Item</th>
                    <th>IVA</th>
                    <th>Total</th>
                    <th style="width: 200px;" colspan="3" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $total_oc = 0; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="text-center"><?php echo $row['id_oc']; ?></td>
                        <td class="text-center">
                            <?php 
                                $fecha = new DateTime($row['created_at']);
                                echo $fecha->format('d-m-Y');
                            ?>
                        </td>
                        <td><?php echo $row['nombre_proveedor']; ?></td>
                       <!-- <td><?php echo $row['descripcion']; ?></td>-->
                        <td class="text-center"><?php echo $row['cantidad_items']; ?></td>
                        <!--<td class="text-end">$<?php echo number_format($row['total_precio'], 0, ',', '.'); ?></td>-->
                        <td class="text-end">$<?php echo number_format($row['suma_valor_neto'], 0, ',', '.'); ?></td>
                        <td class="text-end">$<?php echo number_format($row['suma_iva'], 0, ',', '.'); ?></td>
                        <td class="text-end">$<?php echo number_format($row['suma_total'], 0, ',', '.'); ?></td>

                        <td class="text-center">
                            <a href="generar_pdf_oc.php?id_oc=<?php echo $row['id_oc']; ?>" target="_blank" class="btn btn-sm btn-success">
                                <i class="bi bi-printer"></i> Imprimir
                            </a>
                        </td>
                        <td class="text-center">
                        <a href="editar_oc.php?id_oc=<?php echo $row['id_oc']; ?>" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil"></i> Editar</a>

                        </td>
                        <td class="text-center">
                        <button class="btn btn-danger eliminar-btn btn-sm" data-id="<?php echo htmlspecialchars($row['id_oc']); ?>" data-bs-toggle="modal" data-bs-target="#confirmarEliminar"><i class="bi bi-trash"></i> Eliminar</button>
                        </td>
                    </tr>
                    <?php $total_oc += $row['suma_total']; ?>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <th colspan="6" class="text-end">Total:</th>
                    <th class="text-end">$<?php echo number_format($total_oc, 0, ',', '.'); ?></th>
                    <th style="width: 200px;" colspan="3" class="text-center"></th>
                </tr>
            </tfoot>
        </table>
    <?php else: ?>
        <p class="text-center">No se encontraron órdenes de compra en el rango de fechas seleccionado.</p>
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
        <form action="eliminar_oc.php" method="POST" id="eliminarForm">
          <input type="hidden" name="id_oc" id="idOcEliminar">
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
            const idOc = this.getAttribute("data-id");
            document.getElementById("idOcEliminar").value = idOc;
            document.getElementById("texto-confirmacion").innerText = `¿Estás seguro de que deseas eliminar la OC Nro. ${idOc}?`;
        });
    });
});

</script>
<?php $conn->close(); ?>
<?php include 'content/footer.php'; ?>