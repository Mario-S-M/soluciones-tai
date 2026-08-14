<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Listado y alta de usuarios.
 *
 *   /usuarios        listado
 *   /usuarios/crear  formulario de alta
 */
class Usuarios extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('usuario_model');
	}

	public function index()
	{
		$data = array(
			'usuarios' => $this->usuario_model->listar(),
			// Se rellena tras un alta correcta para mostrar el aviso.
			'alta_id'  => $this->input->get('alta'),
		);

		$this->load->view('plantilla/cabecera', array('titulo' => 'Usuarios'));
		$this->load->view('usuarios/lista', $data);
		$this->load->view('plantilla/pie');
	}

	public function crear()
	{
		$this->load->library('form_validation');

		$this->form_validation->set_rules('nombre',    'nombre',    'trim|required|max_length[100]');
		$this->form_validation->set_rules('apellidos', 'apellidos', 'trim|required|max_length[150]');
		$this->form_validation->set_rules('correo',    'correo',    'trim|required|valid_email|max_length[150]|is_unique[usuarios.correo]');
		// CURP: 18 posiciones (4 letras, fecha AAMMDD, sexo H/M, entidad,
		// 3 consonantes internas, alfanumerico, digito verificador).
		$this->form_validation->set_rules('curp', 'CURP', 'trim|strtoupper|required|regex[/^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9][0-9]$/]|is_unique[usuarios.curp]');
		// RFC: acepta persona fisica (13: 4 letras + fecha + homoclave) y
		// persona moral (12: 3 letras + fecha + homoclave).
		$this->form_validation->set_rules('rfc', 'RFC', 'trim|strtoupper|required|regex[/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/]|is_unique[usuarios.rfc]');
		// Sin required: el telefono sigue siendo opcional. Cuando trae valor,
		// exige 10 digitos sin espacios ni guiones.
		$this->form_validation->set_rules('telefono', 'teléfono', 'trim|max_length[20]|regex[/^[0-9]{10}$/]');
		$this->form_validation->set_rules('sexo', 'sexo', 'trim|required|in_list[M,F,Otro]');
		// max_length[72]: password_hash() con PASSWORD_BCRYPT trunca en
		// silencio cualquier entrada mas alla de 72 bytes, asi que se limita
		// aqui de forma explicita en vez de dejar que ocurra sin avisar.
		$this->form_validation->set_rules('contrasena', 'contraseña', 'required|min_length[8]|max_length[72]');
		$this->form_validation->set_rules('contrasena_confirmar', 'confirmar contraseña', 'required|matches[contrasena]');

		// Los mensajes de CI vienen en ingles. Se traducen aqui, y no cambiando
		// $config['language'], porque eso obligaria a tener traducidos todos los
		// archivos de idioma del framework o el core falla al cargarlos.
		$this->form_validation->set_message('required',   'El campo %s es obligatorio.');
		$this->form_validation->set_message('valid_email', 'El campo %s debe ser una dirección de correo válida.');
		$this->form_validation->set_message('is_unique',  'Ya hay un usuario registrado con ese %s.');
		$this->form_validation->set_message('max_length', 'El campo %s no puede pasar de %s caracteres.');
		$this->form_validation->set_message('min_length', 'El campo %s debe tener al menos %s caracteres.');
		$this->form_validation->set_message('matches',    'El campo %s no coincide con %s.');
		$this->form_validation->set_message('regex',      'El campo %s no tiene un formato válido.');
		$this->form_validation->set_message('in_list',    'El campo %s debe ser uno de los valores permitidos.');

		// En la primera visita (GET) run() devuelve FALSE sin errores, asi que
		// esta misma rama sirve para pintar el formulario vacio y para
		// devolverlo con los mensajes cuando la validacion falla.
		if ($this->form_validation->run() === FALSE)
		{
			$this->load->view('plantilla/cabecera', array('titulo' => 'Nuevo usuario'));
			$this->load->view('usuarios/form');
			$this->load->view('plantilla/pie');
			return;
		}

		$id = $this->usuario_model->crear(array(
			'nombre'     => $this->input->post('nombre'),
			'apellidos'  => $this->input->post('apellidos'),
			'correo'     => $this->input->post('correo'),
			'curp'       => $this->input->post('curp'),
			'rfc'        => $this->input->post('rfc'),
			'telefono'   => $this->input->post('telefono'),
			'sexo'       => $this->input->post('sexo'),
			'contrasena' => $this->input->post('contrasena'),
		));

		redirect('usuarios?alta='.$id);
	}
}
