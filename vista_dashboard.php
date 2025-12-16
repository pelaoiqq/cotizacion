<?php include("content/header.php"); ?>
<div class="container mt-5">
  <h2>Dashboard de Cotizaciones</h2>

  <div class="row mt-3 mb-4">
    <div class="col-md-4">
      <label for="mesFiltro">Seleccionar Mes:</label>
      <input type="month" id="mesFiltro" class="form-control">
    </div>
    <div class="col-md-4 align-self-end">
      <button id="btnFiltrar" class="btn btn-primary">Filtrar</button>
      <button id="btnResetear" class="btn btn-secondary">Ver Todo</button>
      <button id="btnExportar" class="btn btn-danger">Exportar PDF</button>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <canvas id="graficoMeses"></canvas>
    </div>
    <div class="col-md-6">
      <canvas id="graficoEstados" style="max-height: 350px;"></canvas>
    </div>
  </div>
  <br>
  <div class="row mt-4">
    <div class="col-md-6">
      <canvas id="graficoServicios"></canvas>
    </div>
    <div class="col-md-6">
      <canvas id="graficoIngresos"></canvas>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chartMeses, chartEstados, chartServicios, chartIngresos;

function cargarGraficos(mes = '') {
  fetch('data_dashboard.php' + (mes ? `?mes=${mes}` : ''))
    .then(response => response.json())
    .then(data => {
      if (chartMeses) chartMeses.destroy();
      chartMeses = new Chart(document.getElementById('graficoMeses'), {
        type: 'bar',
        data: {
          labels: data.meses,
          datasets: [{
            label: 'Cotizaciones por Mes',
            data: data.totales_mes,
            backgroundColor: '#1D75CE'
          }]
        }
      });

      if (chartEstados) chartEstados.destroy();
      chartEstados = new Chart(document.getElementById('graficoEstados'), {
        type: 'doughnut',
        data: {
          labels: data.estados,
          datasets: [{
            label: 'Estados de Servicio',
            data: data.totales_estado,
            backgroundColor: ['#f39c12', '#27ae60', '#e74c3c', '#3498db']
          }]
        }
      });

      if (chartServicios) chartServicios.destroy();
      chartServicios = new Chart(document.getElementById('graficoServicios'), {
        type: 'bar',
        data: {
          labels: data.servicios,
          datasets: [{
            label: 'Servicios Cotizados',
            data: data.totales_servicio,
            backgroundColor: '#8e44ad'
          }]
        }
      });

      if (chartIngresos) chartIngresos.destroy();
      chartIngresos = new Chart(document.getElementById('graficoIngresos'), {
        type: 'line',
        data: {
          labels: data.meses_ingresos,
          datasets: [{
            label: 'Ingresos Mensuales ($)',
            data: data.totales_ingresos,
            backgroundColor: 'rgba(46, 204, 113, 0.2)',
            borderColor: '#27ae60',
            borderWidth: 2,
            fill: true
          }]
        }
      });
    });
}

document.getElementById('btnFiltrar').addEventListener('click', () => {
  const mesSeleccionado = document.getElementById('mesFiltro').value;
  if (mesSeleccionado) {
    cargarGraficos(mesSeleccionado);
  }
});

document.getElementById('btnResetear').addEventListener('click', () => {
  document.getElementById('mesFiltro').value = '';
  cargarGraficos();
});

window.onload = () => {
  cargarGraficos(); // carga inicial sin filtro
};
</script>

<?php include("content/footer.php"); ?>
