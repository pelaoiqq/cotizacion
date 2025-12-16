<?php include("content/header.php"); ?>
<div class="container mt-5">
  <h2 class="mb-4">Dashboard de Cotizaciones</h2>

  <div class="row mb-4">
    <div class="col-md-4">
      <label for="mesFiltro">Seleccionar Mes:</label>
      <input type="month" id="mesFiltro" class="form-control">
    </div>
    <div class="col-md-6 align-self-end">
      <button id="btnFiltrar" class="btn btn-primary mt-2">Filtrar</button>
      <button id="btnResetear" class="btn btn-secondary mt-2">Ver Todo</button>
      <button id="btnExportar" class="btn btn-danger mt-2" disabled style="display: none;">Exportar PDF</button>
    </div>
  </div>
  <br>

      <div class="row">
        <div class="col-4">
          <canvas id="graficoMeses" style="height: auto; max-width: 300px;"></canvas>
        </div>
        <div class="col-4">
          <canvas id="graficoServicios" style="height: auto; max-width: 300px;"></canvas>
        </div>
        <!--<div class="col-md-4 mb-4">
          <canvas id="graficoIngresos" style="height: 250px;"></canvas>
                      

        </div>-->
        <div class="col-4">
            <h6 class="text-center">Estado de Cotizaciones</h6>
            <canvas id="graficoEstados" style="max-height: 200px; max-width: 400px;"></canvas>
        </div>
      </div>
        <br>


<!-- Librerías -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
let charts = {};

function cargarGraficos(mes = null) {
  const url = mes ? `data_dashboard.php?mes=${mes}` : 'data_dashboard.php';

  fetch(url)
    .then(response => response.json())
    .then(data => {
      // Destruir los gráficos anteriores
      for (let key in charts) {
        if (charts[key]) {
          charts[key].destroy();
        }
      }

      // Gráfico: Cotizaciones por Mes
      charts.meses = new Chart(document.getElementById('graficoMeses'), {
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

      // Gráfico: Estados del Servicio
      charts.estados = new Chart(document.getElementById('graficoEstados'), {
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

      // Gráfico: Servicios Cotizados
      charts.servicios = new Chart(document.getElementById('graficoServicios'), {
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

      // Gráfico: Ingresos Mensuales
      charts.ingresos = new Chart(document.getElementById('graficoIngresos'), {
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

// Eventos
document.getElementById('btnFiltrar').addEventListener('click', () => {
  const mes = document.getElementById('mesFiltro').value;
  if (mes) {
    cargarGraficos(mes);
  }
});

document.getElementById('btnResetear').addEventListener('click', () => {
  document.getElementById('mesFiltro').value = '';
  cargarGraficos();
});

document.getElementById('btnExportar').addEventListener('click', () => {
  const { jsPDF } = window.jspdf;
  const pdf = new jsPDF('landscape', 'pt', 'a4'); // Usamos puntos para mayor precisión
  const container = document.querySelector('.container');

  // Cambia temporalmente el fondo para evitar blanco invisible
  container.style.backgroundColor = '#ffffff';

  html2canvas(container, {
    scale: 2, // Mejor resolución
    useCORS: true
  }).then(canvas => {
    const imgData = canvas.toDataURL('image/png');
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

    pdf.addImage(imgData, 'PNG', 20, 20, pdfWidth - 40, pdfHeight); // Agregamos margen
    pdf.save('dashboard-cotizaciones.pdf');

    // Restauramos el fondo
    container.style.backgroundColor = '';
  });
});


// Carga inicial
cargarGraficos();
</script>

<?php include("content/footer.php"); ?>
