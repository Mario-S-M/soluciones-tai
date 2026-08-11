<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Acceso a la tabla usuarios.
 */
class Usuario_model extends CI_Model {

	protected $tabla = 'usuarios';

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * Todos los usuarios, del mas antiguo al mas reciente.
	 */
	public function listar()
	{
		return $this->db->order_by('id', 'ASC')->get($this->tabla)->result();
	}

	/**
	 * Da de alta un usuario. Devuelve el id asignado por la secuencia.
	 */
	public function crear($datos)
	{
		$this->db->insert($this->tabla, array(
			'nombre'    => $datos['nombre'],
			'apellidos' => $datos['apellidos'],
			'correo'    => $datos['correo'],
			// El telefono es opcional: NULL en vez de cadena vacia.
			'telefono'  => $datos['telefono'] !== '' ? $datos['telefono'] : NULL,
		));

		return $this->db->insert_id();
	}

	public function total()
	{
		return $this->db->count_all($this->tabla);
	}
}
