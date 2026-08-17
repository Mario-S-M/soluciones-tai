<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Listado, alta, edicion, graficas, exportacion e importacion de usuarios.
 *
 *   /usuarios              listado (DataTable con seleccion y exportacion a Excel/PDF)
 *   /usuarios/crear        formulario de alta
 *   /usuarios/editar/:id   formulario de edicion
 *   /usuarios/graficas     grafica de usuarios registrados
 *   /usuarios/plantilla    descarga la plantilla CSV para importar
 *   /usuarios/importar     formulario de importacion masiva desde CSV
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
		$this->reglas_alta();

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

	public function graficas()
	{
		$data = array(
			'total'       => $this->usuario_model->total(),
			'conteo_sexo' => $this->usuario_model->conteoPorSexo(),
		);

		$this->load->view('plantilla/cabecera', array('titulo' => 'Gráficas de usuarios'));
		$this->load->view('usuarios/graficas', $data);
		$this->load->view('plantilla/pie');
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

	/**
	 * Descarga la plantilla CSV para la importacion masiva: encabezado con
	 * las columnas esperadas, mas una fila de ejemplo con datos ficticios
	 * pero validos segun las mismas reglas que el alta manual.
	 */
	public function plantilla()
	{
		$this->load->helper('download');

		$filas = array(
			array('nombre', 'apellidos', 'correo', 'curp', 'rfc', 'telefono', 'sexo', 'contrasena'),
			array('Juana', 'Pérez López', 'juana.perez@example.com', 'PELJ900101MDFRPZ90', 'PELJ900101AB1', '5512345678', 'F', 'ContraseñaSegura1'),
		);

		force_download('plantilla_usuarios.csv', $this->arreglo_a_csv($filas));
	}

	/**
	 * Formulario de importacion masiva (GET) y su procesamiento (POST).
	 */
	public function importar()
	{
		$resultados = NULL;

		if ($this->input->method() === 'post')
		{
			$resultados = $this->procesar_importacion();
		}

		$this->load->view('plantilla/cabecera', array('titulo' => 'Importar usuarios'));
		$this->load->view('usuarios/importar', array('resultados' => $resultados));
		$this->load->view('plantilla/pie');
	}

	/**
	 * Lee el CSV subido fila por fila y valida cada una con las mismas
	 * reglas que el alta manual (reglas_alta()), via
	 * form_validation->set_data() en vez de $_POST: es la forma que ofrece
	 * CodeIgniter 3 para correr el mismo pipeline de validacion sobre datos
	 * que no vienen del formulario. Las filas validas se insertan de
	 * inmediato, asi que is_unique[] tambien detecta duplicados entre filas
	 * del propio archivo (la segunda fila con el mismo correo choca contra
	 * la primera, ya insertada). Las filas invalidas no detienen el resto.
	 */
	private function procesar_importacion()
	{
		if (empty($_FILES['archivo']['tmp_name']) OR $_FILES['archivo']['error'] !== UPLOAD_ERR_OK)
		{
			return array('error_archivo' => 'Selecciona un archivo CSV válido.', 'filas' => array());
		}

		if (strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION)) !== 'csv')
		{
			return array('error_archivo' => 'El archivo debe tener extensión .csv.', 'filas' => array());
		}

		$manejador = fopen($_FILES['archivo']['tmp_name'], 'r');

		if ($manejador === FALSE)
		{
			return array('error_archivo' => 'No se pudo leer el archivo.', 'filas' => array());
		}

		fgetcsv($manejador); // descarta la fila de encabezado

		$this->load->library('form_validation');

		$columnas = array('nombre', 'apellidos', 'correo', 'curp', 'rfc', 'telefono', 'sexo', 'contrasena');
		$filas = array();
		$numero_fila = 1;

		while (($datos = fgetcsv($manejador)) !== FALSE)
		{
			$numero_fila++;

			// Linea en blanco (comun al final del archivo): se ignora sin
			// contar como fila procesada.
			if (count($datos) === 1 && trim((string) $datos[0]) === '')
			{
				continue;
			}

			$fila = array_combine(
				$columnas,
				array_pad(array_slice($datos, 0, count($columnas)), count($columnas), '')
			);
			// No hay doble captura en un archivo como la hay en el formulario
			// de alta; se rellena aqui para poder reusar las mismas reglas de
			// reglas_alta() sin duplicarlas para la importacion.
			$fila['contrasena_confirmar'] = $fila['contrasena'];

			// reset_validation() antes de cada fila: CodeIgniter 3 acumula los
			// errores de validate en _error_array y nunca los limpia entre
			// llamadas a run() sobre el mismo objeto (solo se agregan, nunca se
			// quitan). Sin este reset, el error de una fila invalida se queda
			// pegado ahi y "envenena" tambien el resultado de filas
			// posteriores validas para ese mismo campo. reglas_alta() se vuelve
			// a llamar despues porque reset_validation() tambien borra las
			// reglas y los mensajes personalizados.
			$this->form_validation->reset_validation();
			$this->reglas_alta();
			$this->form_validation->set_data($fila);

			if ($this->form_validation->run() === TRUE)
			{
				// set_value() en vez de $fila directamente: recoge el valor ya
				// procesado por las reglas (trim, strtoupper en CURP/RFC), que
				// es el mismo criterio que ya sigue crear() con $this->input->post().
				$id = $this->usuario_model->crear(array(
					'nombre'     => $this->form_validation->set_value('nombre'),
					'apellidos'  => $this->form_validation->set_value('apellidos'),
					'correo'     => $this->form_validation->set_value('correo'),
					'curp'       => $this->form_validation->set_value('curp'),
					'rfc'        => $this->form_validation->set_value('rfc'),
					'telefono'   => $this->form_validation->set_value('telefono'),
					'sexo'       => $this->form_validation->set_value('sexo'),
					'contrasena' => $this->form_validation->set_value('contrasena'),
				));

				$filas[] = array(
					'numero'  => $numero_fila,
					'nombre'  => trim($fila['nombre'].' '.$fila['apellidos']),
					'estado'  => 'ok',
					'mensaje' => 'Creado con el id '.$id.'.',
				);
			}
			else
			{
				$filas[] = array(
					'numero'  => $numero_fila,
					'nombre'  => trim($fila['nombre'].' '.$fila['apellidos']),
					'estado'  => 'error',
					'mensaje' => strip_tags(validation_errors('', ' ')),
				);
			}
		}

		fclose($manejador);

		return array('error_archivo' => NULL, 'filas' => $filas);
	}

	/**
	 * Arma un CSV en memoria a partir de un arreglo de filas. Usa
	 * php://temp (no un archivo real) porque el contenido solo se necesita
	 * como cadena, para pasarlo a force_download().
	 */
	private function arreglo_a_csv($filas)
	{
		$buffer = fopen('php://temp', 'r+');

		foreach ($filas as $fila)
		{
			fputcsv($buffer, $fila);
		}

		rewind($buffer);
		$csv = stream_get_contents($buffer);
		fclose($buffer);

		return $csv;
	}

	/**
	 * Reglas y mensajes de validacion del alta manual, extraidas de crear()
	 * para reusarlas tal cual en la importacion masiva (procesar_importacion()
	 * valida cada fila del CSV con este mismo conjunto de reglas via
	 * form_validation->set_data()).
	 */
	private function reglas_alta()
	{
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
	}
}
