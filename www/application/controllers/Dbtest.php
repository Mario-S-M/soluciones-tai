<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Comprobacion de que Apache, PHP 5.6 y PostgreSQL 9.5 hablan entre si.
 * Accesible en http://localhost:8080/dbtest
 */
class Dbtest extends CI_Controller {

	public function index()
	{
		$this->load->database();

		$data = array(
			'php_version'      => phpversion(),
			'extensiones'      => array_intersect(array('pgsql', 'pdo_pgsql'), get_loaded_extensions()),
			'servidor_web'     => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'desconocido',
			'version_postgres' => $this->db->query('SELECT version() AS v')->row()->v,
			'ci_version'       => CI_VERSION,
			'usuarios'         => $this->db->order_by('id')->get('usuarios')->result(),
		);

		$this->load->view('dbtest', $data);
	}
}
