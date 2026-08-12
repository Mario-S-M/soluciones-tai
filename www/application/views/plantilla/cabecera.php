<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= html_escape($titulo) ?> · Soluciones TAI</title>
	<link rel="stylesheet" href="<?= base_url('assets/css/estilos.css') ?>">
</head>
<body>
	<nav>
		<a href="<?= site_url('usuarios') ?>">Usuarios</a>
		<a href="<?= site_url('usuarios/crear') ?>">Nuevo usuario</a>
		<a href="<?= site_url('dbtest') ?>">Verificación del entorno</a>
	</nav>
