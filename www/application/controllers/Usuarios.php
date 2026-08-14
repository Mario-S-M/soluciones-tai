<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Listado, alta y edicion de usuarios.
 *
 *   /usuarios              listado
 *   /usuarios/crear        formulario de alta
 *   /usuarios/editar/:id   formulario de edicion
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
		$this->form_validation->set_rules('curp', 'CURP', 'trim|strtoupper|required|regex_match[/^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9][0-9]$/]|is_unique[usuarios.curp]');
		// RFC: acepta persona fisica (13: 4 letras + fecha + homoclave) y
		// persona moral (12: 3 letras + fecha + homoclave).
		$this->form_validation->set_rules('rfc', 'RFC', 'trim|strtoupper|required|regex_match[/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/]|is_unique[usuarios.rfc]');
		// Sin required: el telefono sigue siendo opcional. Cuando trae valor,
		// exige 10 digitos sin espacios ni guiones.
		$this->form_validation->set_rules('telefono', 'teléfono', 'trim|max_length[20]|regex_match[/^[0-9]{10}$/]');
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
		$this->form_validation->set_message('regex_match', 'El campo %s no tiene un formato válido.');
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

	public function editar($id)
	{
		$usuario = $this->usuario_model->obtener($id);

		if ($usuario === NULL)
		{
			show_404();
		}

		$this->load->library('form_validation');

		$this->form_validation->set_rules('nombre',    'nombre',    'trim|required|max_length[100]');
		$this->form_validation->set_rules('apellidos', 'apellidos', 'trim|required|max_length[150]');
		$this->form_validation->set_rules('correo',    'correo',    'trim|required|valid_email|max_length[150]|callback_unico_excepto[correo.'.$id.']');
		$this->form_validation->set_rules('curp', 'CURP', 'trim|strtoupper|required|regex_match[/^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9][0-9]$/]|callback_unico_excepto[curp.'.$id.']');
		$this->form_validation->set_rules('rfc', 'RFC', 'trim|strtoupper|required|regex_match[/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/]|callback_unico_excepto[rfc.'.$id.']');
		$this->form_validation->set_rules('telefono', 'teléfono', 'trim|max_length[20]|regex_match[/^[0-9]{10}$/]');
		$this->form_validation->set_rules('sexo', 'sexo', 'trim|required|in_list[M,F,Otro]');
		// Sin required en ninguna de las dos: dejar la contraseña en blanco
		// significa "no cambiarla". Si se llena una, matches sigue
		// ejecutandose aunque la confirmacion quede vacia (ver Form_validation
		// ::_execute, matches esta en la lista de reglas que no se saltan).
		$this->form_validation->set_rules('contrasena', 'contraseña', 'min_length[8]|max_length[72]');
		$this->form_validation->set_rules('contrasena_confirmar', 'confirmar contraseña', 'matches[contrasena]');

		$this->form_validation->set_message('required',   'El campo %s es obligatorio.');
		$this->form_validation->set_message('valid_email', 'El campo %s debe ser una dirección de correo válida.');
		$this->form_validation->set_message('unico_excepto', 'Ya hay un usuario registrado con ese %s.');
		$this->form_validation->set_message('max_length', 'El campo %s no puede pasar de %s caracteres.');
		$this->form_validation->set_message('min_length', 'El campo %s debe tener al menos %s caracteres.');
		$this->form_validation->set_message('matches',    'El campo %s no coincide con %s.');
		$this->form_validation->set_message('regex_match', 'El campo %s no tiene un formato válido.');
		$this->form_validation->set_message('in_list',    'El campo %s debe ser uno de los valores permitidos.');

		if ($this->form_validation->run() === FALSE)
		{
			$this->load->view('plantilla/cabecera', array('titulo' => 'Editar usuario'));
			$this->load->view('usuarios/editar', array('usuario' => $usuario));
			$this->load->view('plantilla/pie');
			return;
		}

		$this->usuario_model->editar($id, array(
			'nombre'     => $this->input->post('nombre'),
			'apellidos'  => $this->input->post('apellidos'),
			'correo'     => $this->input->post('correo'),
			'curp'       => $this->input->post('curp'),
			'rfc'        => $this->input->post('rfc'),
			'telefono'   => $this->input->post('telefono'),
			'sexo'       => $this->input->post('sexo'),
			'contrasena' => $this->input->post('contrasena'),
		));

		redirect('usuarios');
	}

	/**
	 * Callback de form_validation: valida que ningun OTRO usuario tenga ya
	 * ese valor en $campo. is_unique[] de CodeIgniter 3 no soporta excluir el
	 * propio id, asi que en la edicion se usa este callback en su lugar.
	 * Parametro esperado: "campo.id", p. ej. callback_unico_excepto[correo.5]
	 */
	public function unico_excepto($valor, $parametro)
	{
		list($campo, $id) = explode('.', $parametro, 2);

		if ($this->usuario_model->existeOtroCon($campo, $valor, $id))
		{
			$this->form_validation->set_message('unico_excepto', 'Ya hay un usuario registrado con ese %s.');
			return FALSE;
		}

		return TRUE;
	}
}
