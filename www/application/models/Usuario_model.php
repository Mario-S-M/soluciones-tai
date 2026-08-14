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
	 * No selecciona contrasena_hash: el hash nunca debe salir de la base de
	 * datos hacia PHP/la vista, solo un indicador de si esta configurada.
	 */
	public function listar()
	{
		return $this->db
			->select(
				"id, nombre, apellidos, correo, telefono, curp, rfc, sexo, creado_en, " .
				"CASE WHEN contrasena_hash IS NOT NULL THEN 'Sí' ELSE 'No' END AS contrasena_estado",
				FALSE
			)
			->order_by('id', 'ASC')
			->get($this->tabla)
			->result();
	}

	/**
	 * Da de alta un usuario. Devuelve el id asignado por la secuencia.
	 */
	public function crear($datos)
	{
		$this->db->insert($this->tabla, array(
			'nombre'          => $datos['nombre'],
			'apellidos'       => $datos['apellidos'],
			'correo'          => $datos['correo'],
			'curp'            => $datos['curp'],
			'rfc'             => $datos['rfc'],
			'sexo'            => $datos['sexo'],
			// El telefono es opcional: NULL en vez de cadena vacia.
			'telefono'        => $datos['telefono'] !== '' ? $datos['telefono'] : NULL,
			// Bcrypt via password_hash(), nativo de PHP desde 5.5: salt
			// aleatorio incluido, sin libreria ni extension adicional.
			'contrasena_hash' => password_hash($datos['contrasena'], PASSWORD_BCRYPT),
		));

		return $this->db->insert_id();
	}

	/**
	 * Un usuario por id, para precargar el formulario de edicion. Mismo
	 * criterio que listar(): no selecciona contrasena_hash.
	 */
	public function obtener($id)
	{
		return $this->db
			->select('id, nombre, apellidos, correo, telefono, curp, rfc, sexo, creado_en')
			->where('id', $id)
			->get($this->tabla)
			->row();
	}

	/**
	 * Edita un usuario existente. La contraseña solo se rehashea si
	 * $datos['contrasena'] trae un valor; en blanco significa "no cambiarla".
	 */
	public function editar($id, $datos)
	{
		$campos = array(
			'nombre'    => $datos['nombre'],
			'apellidos' => $datos['apellidos'],
			'correo'    => $datos['correo'],
			'curp'      => $datos['curp'],
			'rfc'       => $datos['rfc'],
			'sexo'      => $datos['sexo'],
			'telefono'  => $datos['telefono'] !== '' ? $datos['telefono'] : NULL,
		);

		if ($datos['contrasena'] !== '')
		{
			$campos['contrasena_hash'] = password_hash($datos['contrasena'], PASSWORD_BCRYPT);
		}

		$this->db->where('id', $id)->update($this->tabla, $campos);
	}

	/**
	 * Si otro usuario (distinto de $id) ya tiene ese valor en $campo. Usada
	 * por el callback de unicidad en la edicion, porque is_unique[] de
	 * CodeIgniter 3 no soporta excluir el propio id.
	 */
	public function existeOtroCon($campo, $valor, $id)
	{
		return $this->db
			->where($campo, $valor)
			->where('id !=', $id)
			->from($this->tabla)
			->count_all_results() > 0;
	}

	public function total()
	{
		return $this->db->count_all($this->tabla);
	}
}
