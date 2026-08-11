	<h1>Usuarios</h1>

	<?php if ($alta_id): ?>
	<p class="aviso aviso-ok">Usuario dado de alta con el id <?= html_escape($alta_id) ?>.</p>
	<?php endif; ?>

	<?php if (empty($usuarios)): ?>
	<p class="vacio">Todavía no hay usuarios registrados.</p>
	<?php else: ?>
	<table>
		<tr>
			<th>ID</th><th>Nombre</th><th>Apellidos</th>
			<th>Correo</th><th>Teléfono</th><th>Alta</th>
		</tr>
		<?php foreach ($usuarios as $u): ?>
		<tr>
			<td><?= html_escape($u->id) ?></td>
			<td><?= html_escape($u->nombre) ?></td>
			<td><?= html_escape($u->apellidos) ?></td>
			<td><?= html_escape($u->correo) ?></td>
			<td><?= $u->telefono === NULL ? '—' : html_escape($u->telefono) ?></td>
			<td><?= html_escape(date('d/m/Y', strtotime($u->creado_en))) ?></td>
		</tr>
		<?php endforeach; ?>
	</table>
	<p><?= count($usuarios) ?> usuario<?= count($usuarios) === 1 ? '' : 's' ?> en total.</p>
	<?php endif; ?>

	<a class="boton" href="<?= site_url('usuarios/crear') ?>">Nuevo usuario</a>
