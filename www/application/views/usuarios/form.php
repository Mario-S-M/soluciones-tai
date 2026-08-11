	<h1>Nuevo usuario</h1>

	<?= form_open('usuarios/crear') ?>

		<label for="nombre">Nombre</label>
		<input type="text" id="nombre" name="nombre" maxlength="100" value="<?= set_value('nombre') ?>">
		<?= form_error('nombre', '<p class="error">', '</p>') ?>

		<label for="apellidos">Apellidos</label>
		<input type="text" id="apellidos" name="apellidos" maxlength="150" value="<?= set_value('apellidos') ?>">
		<?= form_error('apellidos', '<p class="error">', '</p>') ?>

		<label for="correo">Correo</label>
		<input type="email" id="correo" name="correo" maxlength="150" value="<?= set_value('correo') ?>">
		<?= form_error('correo', '<p class="error">', '</p>') ?>

		<label for="telefono">Teléfono <span class="vacio">(opcional)</span></label>
		<input type="text" id="telefono" name="telefono" maxlength="20" value="<?= set_value('telefono') ?>">
		<?= form_error('telefono', '<p class="error">', '</p>') ?>

		<button type="submit" class="boton">Guardar</button>

	<?= form_close() ?>
