<?php
	
	require_once 'vistas/VistaJson.php';	
	require_once 'vistas/VistaXML.php';
	require_once 'modelos/usuarios.php';
	require_once 'modelos/contactos.php';
	require_once 'utilidades/ExcepcionApi.php';

	// Constantes de estado
	const ESTADO_URL_INCORRECTA = 2;
	const ESTADO_EXISTENCIA_RECURSO = 3;
	const ESTADO_METODO_NO_PERMITIDO = 4;

	// Preparar manejo de excepciones
	$formato = isset($_GET['formato']) ? $_GET['formato'] : 'json';

	switch ($formato) {
	    case 'xml':
	        $vista = new VistaXML();
	        break;
	    case 'json':
	    default:
	        $vista = new VistaJson();
	}


	set_exception_handler(function ($exception) use ($vista) {
	    $cuerpo = array(
	        "estado" => $exception->estado,
	        "mensaje" => $exception->getMessage()
	    );
	    if ($exception->getCode())
	        $vista->estado = $exception->getCode();
	    else
	        $vista->estado = 500;
	    
	    $vista->imprimir($cuerpo);
	});
	
	// Extraer segmento de la url
	if (isset($_GET['PATH_INFO']))
	    $parametro = explode('/', $_GET['PATH_INFO']);
	else
	    throw new ExcepcionApi(ESTADO_URL_INCORRECTA, utf8_encode("No se reconoce la petición"));

	// Obtener recurso
	$recurso = array_shift($parametro);
	$recursos_existentes = array('contactos', 'usuarios');

	// Comprobar si existe el recurso
	if (!in_array($recurso, $recursos_existentes)) {
		throw new ExcepcionApi(
			ESTADO_EXISTENCIA_RECURSO,
        	"No se reconoce el recurso al que intentas acceder"
        );
	}

	$metodo = strtolower($_SERVER['REQUEST_METHOD']);

	// Filtrar método
	switch ($metodo) {
	    case 'get':
	    case 'post':
	    case 'put':
	    case 'delete':
	        if (method_exists($recurso, $metodo)) {
	            $respuesta = call_user_func(array($recurso, $metodo), $parametro);
	        //  $respuesta = call_user_func(array($nombreclase, 'nombrefuncion'), 'parametro');

	            $vista->imprimir($respuesta);
	            break;
	        }
	    default:
	        // Método no aceptado
	        $vista->estado = 405;
	        $cuerpo = [
	            "estado" => ESTADO_METODO_NO_PERMITIDO,
	            "mensaje" => utf8_encode("Método no permitido")
	        ];
	        $vista->imprimir($cuerpo);

	}


	// switch ($metodo) {
	//     case 'get':
	//     	$vista->imprimir(contactos::get($peticion));
	//         break;

	//     case 'post':
	//     	$vista->imprimir(usuarios::post($pathinfo));
	//         break;
	//     case 'put':
	//     	echo "put";
	//         break;

	//     case 'delete':
	//     	echo "delete";
	//         break;
	//     default:
	//         // Método no aceptado
	// }



?>