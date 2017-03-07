<?php  

	require_once('ConexionBD.class.php');
	require_once('ExcepcionApi.php');

	
	class Usuarios extends ConexionBD {
		
		const NOMBRE_TABLA = "usuario";
	    const ID_USUARIO = "idUsuario";
	    const NOMBRE = "nombre";
	    const CONTRASENA = "password";
	    const CORREO = "correo";
	    const CLAVE_API = "claveAPI";

	    public static function post($peticion){

	    	$consult = "SELECT * FROM usuario";

	    	echo 'prev';
	    	var_dump(self::query_assoc($consult));
	    	echo 'prev';
	    	exit;
	    	return self::query_assoc($consult);

	    	// if ($peticion[0] == 'registro')
		    //     return self::registrar();
		    // else if ($peticion[0] == 'login')
		    //     return self::loguear();
		    // else
		    //     throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);

	    }


	}

?>