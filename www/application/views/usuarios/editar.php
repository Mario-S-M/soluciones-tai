	<h1>Editar usuario</h1>

	<?= form_open('usuarios/editar/'.$usuario->id) ?>

		<label for="nombre">Nombre</label>
		<input type="text" id="nombre" name="nombre" maxlength="100" value="<?= set_value('nombre', $usuario->nombre) ?>">
		<?= form_error('nombre', '<p class="error">', '</p>') ?>

		<label for="apellidos">Apellidos</label>
		<input type="text" id="apellidos" name="apellidos" maxlength="150" value="<?= set_value('apellidos', $usuario->apellidos) ?>">
		<?= form_error('apellidos', '<p class="error">', '</p>') ?>

		<label for="correo">Correo</label>
		<input type="email" id="correo" name="correo" maxlength="150" value="<?= set_value('correo', $usuario->correo) ?>">
		<?= form_error('correo', '<p class="error">', '</p>') ?>

		<label for="curp">CURP</label>
		<input type="text" id="curp" name="curp" maxlength="18" style="text-transform: uppercase" value="<?= set_value('curp', $usuario->curp) ?>">
		<?= form_error('curp', '<p class="error">', '</p>') ?>

		<label for="rfc">RFC</label>
		<input type="text" id="rfc" name="rfc" maxlength="13" style="text-transform: uppercase" value="<?= set_value('rfc', $usuario->rfc) ?>">
		<?= form_error('rfc', '<p class="error">', '</p>') ?>

		<label for="telefono">Teléfono <span class="vacio">(opcional)</span></label>
		<input type="text" id="telefono" name="telefono" maxlength="20" value="<?= set_value('telefono', $usuario->telefono) ?>">
		<?= form_error('telefono', '<p class="error">', '</p>') ?>

		<!-- set_select() con un tercer argumento solo respeta el default cuando
		     el formulario no tiene reglas registradas; aqui siempre las hay,
		     asi que el "selected" se decide a mano con set_value(). -->
		<?php $sexo_actual = set_value('sexo', $usuario->sexo, FALSE) ?>
		<label for="sexo">Sexo</label>
		<select id="sexo" name="sexo">
			<option value="">Selecciona una opción</option>
			<option value="M" <?= $sexo_actual === 'M' ? 'selected="selected"' : '' ?>>Masculino</option>
			<option value="F" <?= $sexo_actual === 'F' ? 'selected="selected"' : '' ?>>Femenino</option>
			<option value="Otro" <?= $sexo_actual === 'Otro' ? 'selected="selected"' : '' ?>>Otro</option>
		</select>
		<?= form_error('sexo', '<p class="error">', '</p>') ?>

		<!-- Sin set_value(): igual que en el alta, un campo de contraseña
		     nunca se repuebla con el valor tecleado. En blanco = no cambiarla. -->
		<label for="contrasena">Contraseña <span class="vacio">(dejar en blanco para no cambiarla)</span></label>
		<input type="password" id="contrasena" name="contrasena" minlength="8" maxlength="72" autocomplete="new-password">
		<?= form_error('contrasena', '<p class="error">', '</p>') ?>

		<label for="contrasena_confirmar">Confirmar contraseña</label>
		<input type="password" id="contrasena_confirmar" name="contrasena_confirmar" minlength="8" maxlength="72" autocomplete="new-password">
		<?= form_error('contrasena_confirmar', '<p class="error">', '</p>') ?>

		<button type="submit" class="boton">Guardar cambios</button>

	<?= form_close() ?>
