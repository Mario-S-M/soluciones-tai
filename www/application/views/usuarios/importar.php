	<h1>Importar usuarios</h1>

	<p><a href="<?= site_url('usuarios') ?>">&larr; Volver al listado</a></p>

	<p>
		El archivo debe ser un <strong>.csv</strong> con estas columnas, en este orden:
		<code>nombre, apellidos, correo, curp, rfc, telefono, sexo, contrasena</code>.
		<code>telefono</code> puede ir vacío; el resto son obligatorios y siguen las mismas
		reglas que el alta manual (formato de CURP y RFC, sexo <code>M</code>/<code>F</code>/<code>Otro</code>,
		contraseña de al menos 8 caracteres).
	</p>

	<p>
		<a href="<?= site_url('usuarios/plantilla') ?>">Descargar plantilla (.csv)</a>
		— trae una fila de ejemplo; bórrala o edítala antes de subir el archivo.
	</p>

	<?php if ($resultados !== NULL): ?>

		<?php if ($resultados['error_archivo']): ?>
		<p class="aviso" style="background:#ffebe9;border:1px solid #cf222e33;color:#cf222e">
			<?= html_escape($resultados['error_archivo']) ?>
		</p>
		<?php else: ?>

			<?php
				$total_ok = 0;
				foreach ($resultados['filas'] as $fila)
				{
					if ($fila['estado'] === 'ok') $total_ok++;
				}
			?>
			<p class="aviso aviso-ok">
				<?= $total_ok ?> de <?= count($resultados['filas']) ?>
				fila<?= count($resultados['filas']) === 1 ? '' : 's' ?> importada<?= $total_ok === 1 ? '' : 's' ?> correctamente.
			</p>

			<?php if (!empty($resultados['filas'])): ?>
			<table>
				<tr><th>Fila</th><th>Usuario</th><th>Resultado</th></tr>
				<?php foreach ($resultados['filas'] as $fila): ?>
				<tr>
					<td><?= html_escape($fila['numero']) ?></td>
					<td><?= html_escape($fila['nombre']) ?></td>
					<td class="<?= $fila['estado'] === 'ok' ? '' : 'error' ?>"><?= html_escape($fila['mensaje']) ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
			<?php endif; ?>

		<?php endif; ?>

	<?php endif; ?>

	<?= form_open_multipart('usuarios/importar') ?>

		<label for="archivo">Archivo CSV</label>
		<input type="file" id="archivo" name="archivo" accept=".csv">

		<button type="submit" class="boton">Importar</button>

	<?= form_close() ?>
