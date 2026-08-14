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

		<label for="curp">CURP</label>
		<input type="text" id="curp" name="curp" maxlength="18" style="text-transform: uppercase" value="<?= set_value('curp') ?>">
		<?= form_error('curp', '<p class="error">', '</p>') ?>

		<label for="rfc">RFC</label>
		<input type="text" id="rfc" name="rfc" maxlength="13" style="text-transform: uppercase" value="<?= set_value('rfc') ?>">
		<?= form_error('rfc', '<p class="error">', '</p>') ?>

		<label for="telefono">Teléfono <span class="vacio">(opcional)</span></label>
		<input type="text" id="telefono" name="telefono" maxlength="20" value="<?= set_value('telefono') ?>">
		<?= form_error('telefono', '<p class="error">', '</p>') ?>

		<label for="sexo">Sexo</label>
		<select id="sexo" name="sexo">
			<option value="">Selecciona una opción</option>
			<option value="M" <?= set_select('sexo', 'M') ?>>Masculino</option>
			<option value="F" <?= set_select('sexo', 'F') ?>>Femenino</option>
			<option value="Otro" <?= set_select('sexo', 'Otro') ?>>Otro</option>
		</select>
		<?= form_error('sexo', '<p class="error">', '</p>') ?>

		<!-- Sin set_value(): repoblar un campo de contraseña tras un fallo de
		     validación dejaría el valor en texto plano en la respuesta HTML. -->
		<label for="contrasena">Contraseña</label>
		<input type="password" id="contrasena" name="contrasena" minlength="8" maxlength="72" autocomplete="new-password">
		<?= form_error('contrasena', '<p class="error">', '</p>') ?>

		<label for="contrasena_confirmar">Confirmar contraseña</label>
		<input type="password" id="contrasena_confirmar" name="contrasena_confirmar" minlength="8" maxlength="72" autocomplete="new-password">
		<?= form_error('contrasena_confirmar', '<p class="error">', '</p>') ?>

		<button type="submit" class="boton">Guardar</button>

	<?= form_close() ?>
