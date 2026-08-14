	<h1>Gráficas de usuarios</h1>

	<p><a href="<?= site_url('usuarios') ?>">&larr; Volver al listado</a></p>

	<h2>Total general</h2>
	<canvas id="grafica-total"></canvas>

	<h2>Por sexo</h2>
	<canvas id="grafica-sexo"></canvas>

	<!-- Chart.js solo se carga aqui, no en plantilla/cabecera.php: el
	     contenedor de PHP 5.6 no tiene extension de graficas, y no vale la
	     pena pagar el peso del script en paginas que no lo usan. -->
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
	<script>
		var total = <?= json_encode($total) ?>;
		var conteoSexo = <?= json_encode($conteo_sexo) ?>;

		new Chart(document.getElementById('grafica-total'), {
			type: 'bar',
			data: {
				labels: ['Usuarios registrados'],
				datasets: [{
					label: 'Total',
					data: [total],
					backgroundColor: '#0969da'
				}]
			},
			options: {
				scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
				plugins: { legend: { display: false } }
			}
		});

		new Chart(document.getElementById('grafica-sexo'), {
			type: 'pie',
			data: {
				labels: ['Masculino', 'Femenino', 'Otro'],
				datasets: [{
					data: [conteoSexo.M, conteoSexo.F, conteoSexo.Otro],
					backgroundColor: ['#0969da', '#bf3989', '#9a6700']
				}]
			}
		});
	</script>
