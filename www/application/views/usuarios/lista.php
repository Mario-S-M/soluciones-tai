	<h1>Usuarios</h1>

	<?php if ($alta_id): ?>
	<p class="aviso aviso-ok">Usuario dado de alta con el id <?= html_escape($alta_id) ?>.</p>
	<?php endif; ?>

	<?php if (empty($usuarios)): ?>
	<p class="vacio">Todavía no hay usuarios registrados.</p>
	<?php else: ?>
	<div style="overflow-x: auto">
	<table id="tabla-usuarios" class="display" style="width: 100%">
		<thead>
		<tr>
			<th></th>
			<th>ID</th><th>Nombre</th><th>Apellidos</th>
			<th>Correo</th><th>CURP</th><th>RFC</th><th>Sexo</th>
			<th>Teléfono</th><th>Contraseña</th><th>Alta</th><th></th>
		</tr>
		</thead>
		<tbody>
		<?php $sexo_etiqueta = array('M' => 'Masculino', 'F' => 'Femenino', 'Otro' => 'Otro'); ?>
		<?php foreach ($usuarios as $u): ?>
		<tr>
			<td></td>
			<td><?= html_escape($u->id) ?></td>
			<td><?= html_escape($u->nombre) ?></td>
			<td><?= html_escape($u->apellidos) ?></td>
			<td><?= html_escape($u->correo) ?></td>
			<td><?= html_escape($u->curp) ?></td>
			<td><?= html_escape($u->rfc) ?></td>
			<td><?= html_escape($sexo_etiqueta[$u->sexo]) ?></td>
			<td><?= $u->telefono === NULL ? '—' : html_escape($u->telefono) ?></td>
			<td><?= html_escape($u->contrasena_estado) ?></td>
			<td><?= html_escape(date('d/m/Y', strtotime($u->creado_en))) ?></td>
			<td><a href="<?= site_url('usuarios/editar/'.$u->id) ?>">Editar</a></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	</div>
	<p><?= count($usuarios) ?> usuario<?= count($usuarios) === 1 ? '' : 's' ?> en total.</p>

	<!-- DataTables (con Buttons y Select) solo se carga aqui, no en
	     plantilla/cabecera.php: mismo criterio que Chart.js en
	     usuarios/graficas.php, para no pagar su peso en paginas que no lo
	     necesitan. La exportacion a Excel/PDF es 100% en el navegador
	     (JSZip + pdfmake), sin librerias del lado servidor: PhpSpreadsheet y
	     dompdf modernos requieren PHP >= 7.2, y este contenedor corre 5.6. -->
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.11/css/jquery.dataTables.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css">
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.11/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.9/pdfmake.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.9/vfs_fonts.js"></script>
	<script>
		// Los botones exportan solo las filas marcadas con el checkbox de la
		// columna 0; si no hay ninguna marcada, exportan la tabla completa
		// (respetando el buscador). La columna de "Editar" (la ultima) nunca
		// se exporta, no aporta nada fuera de la pagina.
		function opcionesExportacion()
		{
			return {
				columns: ':visible:not(:first-child):not(:last-child)',
				rows: function () {
					return tabla.rows({ selected: true }).count() > 0 ? { selected: true } : {};
				}
			};
		}

		var tabla = $('#tabla-usuarios').DataTable({
			columnDefs: [
				{ orderable: false, className: 'select-checkbox', targets: 0 },
				{ orderable: false, targets: -1 }
			],
			select: { style: 'multi', selector: 'td:first-child' },
			order: [[1, 'asc']],
			language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
			dom: 'Bfrtip',
			buttons: [
				{ extend: 'excelHtml5', text: 'Exportar a Excel', exportOptions: opcionesExportacion() },
				{ extend: 'pdfHtml5', text: 'Exportar a PDF', orientation: 'landscape', exportOptions: opcionesExportacion() }
			],
			// initComplete, no justo despues de DataTable(): con language.url la
			// carga del idioma es asincrona y termina de armar el thead despues,
			// asi que inyectar el checkbox antes de este callback se perdia.
			initComplete: function () {
				$('#tabla-usuarios thead th:first-child').html('<input type="checkbox" id="seleccionar-todos">');
				$('#seleccionar-todos').on('change', function () {
					if (this.checked) {
						tabla.rows({ search: 'applied' }).select();
					} else {
						tabla.rows().deselect();
					}
				});
			}
		});
	</script>
	<?php endif; ?>

	<a class="boton" href="<?= site_url('usuarios/crear') ?>">Nuevo usuario</a>
	<a href="<?= site_url('usuarios/importar') ?>">Importar usuarios</a>
	<a href="<?= site_url('usuarios/graficas') ?>">Ver gráficas</a>
