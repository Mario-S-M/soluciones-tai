<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Verificación del entorno</title>
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
		       max-width: 46rem; margin: 3rem auto; padding: 0 1.5rem; line-height: 1.6;
		       color: #1f2328; }
		h1 { font-size: 1.5rem; }
		h2 { font-size: 1.1rem; margin-top: 2rem; }
		table { border-collapse: collapse; width: 100%; margin-top: .5rem; }
		th, td { text-align: left; padding: .5rem .75rem; border-bottom: 1px solid #d8dee4; }
		th { background: #f6f8fa; font-weight: 600; }
		code { background: #f6f8fa; padding: .1rem .35rem; border-radius: 4px; font-size: .9em; }
		.ok { color: #1a7f37; font-weight: 600; }
	</style>
</head>
<body>
	<h1><span class="ok">✓</span> El entorno responde</h1>

	<h2>Pila</h2>
	<table>
		<tr><th>Servidor web</th><td><?= html_escape($servidor_web) ?></td></tr>
		<tr><th>PHP</th><td><?= html_escape($php_version) ?></td></tr>
		<tr><th>CodeIgniter</th><td><?= html_escape($ci_version) ?></td></tr>
		<tr><th>PostgreSQL</th><td><?= html_escape($version_postgres) ?></td></tr>
		<tr><th>Extensiones</th><td><?= implode(' ', array_map(function ($e) {
			return '<code>'.html_escape($e).'</code>';
		}, $extensiones)) ?></td></tr>
	</table>

	<h2>Tabla <code>usuarios</code> (<?= count($usuarios) ?> filas)</h2>
	<table>
		<tr><th>ID</th><th>Nombre</th><th>Apellidos</th><th>Correo</th><th>Teléfono</th><th>Creado</th></tr>
		<?php foreach ($usuarios as $u): ?>
		<tr>
			<td><?= html_escape($u->id) ?></td>
			<td><?= html_escape($u->nombre) ?></td>
			<td><?= html_escape($u->apellidos) ?></td>
			<td><?= html_escape($u->correo) ?></td>
			<td><?= html_escape($u->telefono) ?></td>
			<td><?= html_escape($u->creado_en) ?></td>
		</tr>
		<?php endforeach; ?>
	</table>
</body>
</html>
