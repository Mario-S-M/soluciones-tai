<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= html_escape($titulo) ?> · Soluciones TAI</title>
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
		       max-width: 46rem; margin: 3rem auto; padding: 0 1.5rem; line-height: 1.6;
		       color: #1f2328; }
		h1 { font-size: 1.5rem; }
		table { border-collapse: collapse; width: 100%; margin-top: .5rem; }
		th, td { text-align: left; padding: .5rem .75rem; border-bottom: 1px solid #d8dee4; }
		th { background: #f6f8fa; font-weight: 600; }
		a { color: #0969da; }
		nav { margin-bottom: 2rem; font-size: .9rem; }
		nav a { margin-right: 1rem; }
		label { display: block; margin-top: 1rem; font-weight: 600; font-size: .9rem; }
		input[type=text], input[type=email] { width: 100%; padding: .45rem .6rem; font-size: 1rem;
		       border: 1px solid #d8dee4; border-radius: 6px; box-sizing: border-box; }
		.boton { display: inline-block; margin-top: 1.5rem; padding: .5rem 1rem; border: 0;
		       border-radius: 6px; background: #1f883d; color: #fff; font-size: 1rem;
		       cursor: pointer; text-decoration: none; }
		.aviso { padding: .75rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
		.aviso-ok { background: #dafbe1; border: 1px solid #1f883d33; color: #1a7f37; }
		.error { color: #cf222e; font-size: .85rem; }
		.vacio { color: #656d76; font-style: italic; }
	</style>
</head>
<body>
	<nav>
		<a href="<?= site_url('usuarios') ?>">Usuarios</a>
		<a href="<?= site_url('usuarios/crear') ?>">Nuevo usuario</a>
		<a href="<?= site_url('dbtest') ?>">Verificación del entorno</a>
	</nav>
