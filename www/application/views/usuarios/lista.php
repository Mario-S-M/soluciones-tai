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
			<th>Correo</th><th>CURP</th><th>RFC</th><th>Sexo</th>
			<th>Teléfono</th><th>Contraseña</th><th>Alta</th><th></th>
		</tr>
		<?php $sexo_etiqueta = array('M' => 'Masculino', 'F' => 'Femenino', 'Otro' => 'Otro'); ?>
		<?php foreach ($usuarios as $u): ?>
		<tr>
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
	</table>
	<p><?= count($usuarios) ?> usuario<?= count($usuarios) === 1 ? '' : 's' ?> en total.</p>
	<?php endif; ?>

	<a class="boton" href="<?= site_url('usuarios/crear') ?>">Nuevo usuario</a>
