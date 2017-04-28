<?php 
	
	require_once 'utilidades/ExcepcionApi.php';
	require_once 'utilidades/ConexionBD.php';
	
	class usuarios{
	    // Datos de la tabla "usuario"
	    const NOMBRE_TABLA = "usuario";
	    const ID_USUARIO = "idUsuario";
	    const NOMBRE = "nombre";
	    const CONTRASENA = "password";
	    const CORREO = "correo";
	    const CLAVE_API = "claveAPI";

	    const ESTADO_CREACION_EXITOSA = 1;
	    const ESTADO_CREACION_FALLIDA = 2;
	    const ESTADO_ERROR_BD = 3;
	    const ESTADO_AUSENCIA_CLAVE_API = 4;
	    const ESTADO_CLAVE_NO_AUTORIZADA = 5;
	    const ESTADO_URL_INCORRECTA = 6;
	    const ESTADO_FALLA_DESCONOCIDA = 7;
	    const ESTADO_PARAMETROS_INCORRECTOS = 8;

	    const MSG_CLAVE_NO_AUTORIZADA = "API Key no autorizada";

	    public static function post($peticion){

		    if ($peticion[0] == 'registro')
		        return self::registrar();
		    else if ($peticion[0] == 'login')
		        return self::loguear();
		    else
		        throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
		    
		}  

		private function registrar(){

		    $cuerpo = file_get_contents('php://input');
		    $usuario = json_decode($cuerpo);

		    $resultado = self::crear($usuario);

		    switch ($resultado) {
		        case self::ESTADO_CREACION_EXITOSA:
		            http_response_code(200);
		            return
		                [
		                    "estado" => self::ESTADO_CREACION_EXITOSA,
		                    "mensaje" => "¡Registro con éxito!"
		                ];
		            break;
		        case self::ESTADO_CREACION_FALLIDA:
		            throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
		            break;
		        default:
		            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
		    }
		
		}

		private function crear($datosUsuario){

		    $nombre = $datosUsuario->nombre;

		    $contrasena = $datosUsuario->password;
		    $contrasenaEncriptada = self::encriptarContrasena($contrasena);

		    $correo = $datosUsuario->correo;

		    $claveApi = self::generarClaveApi();

		    try {

		        // Sentencia INSERT
		        $comando = "
		        	INSERT INTO " . self::NOMBRE_TABLA . " ( " .
		            self::NOMBRE . "," . self::CONTRASENA . "," .
		            self::CLAVE_API . "," . self::CORREO . ")" .
		            " VALUES('$nombre','$contrasenaEncriptada','$claveApi','$correo')";

		        $resultado = ConexionBD::query($comando);

		        if ($resultado)
		            return self::ESTADO_CREACION_EXITOSA;
		        else
		            return self::ESTADO_CREACION_FALLIDA;
		        
		    }
		    catch (PDOException $e) {
		        throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
		    }

		}


		private function loguear(){
		    
		    $respuesta = array();

		    $body = file_get_contents('php://input');
		    $usuario = json_decode($body);

		    $correo = $usuario->correo;
		    $contrasena = $usuario->password;


		    if (self::autenticar($correo, $contrasena)) {
		        $usuarioBD = self::obtenerUsuarioPorCorreo($correo);

		        if ($usuarioBD != NULL) {
		        	http_response_code(200);
		            $respuesta["nombre"] = $usuarioBD["nombre"];
		            $respuesta["correo"] = $usuarioBD["correo"];
		            $respuesta["claveAPI"] = $usuarioBD["claveAPI"];
		            return ["estado" => 1, "usuario" => $respuesta];
		        }
		        else {
		            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
		                "Ha ocurrido un error");
		        }
		    }
		    else {
		        throw new ExcepcionApi(
		        	self::ESTADO_PARAMETROS_INCORRECTOS,
		            "Correo o contraseña inválidos"
		        );
		    }

		}


		private function autenticar($correo, $contrasena){

		    $comando = "SELECT password FROM " . self::NOMBRE_TABLA .
		        " WHERE " . self::CORREO . "=?";

		    try{

		        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

		        $sentencia->bindParam(1, $correo);

		        $sentencia->execute();

		        if ($sentencia) {
		            $resultado = $sentencia->fetch();

		            if (self::validarContrasena($contrasena, $resultado['password']))
		                return true;
		            else 
		            	return false;
		        }
		        else
		            return false;
		        
		    }
		    catch (PDOException $e){
		        throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
		    }
		    

		}


		private function obtenerUsuarioPorCorreo($correo){

		    $comando = "SELECT " .
		        self::NOMBRE . "," .
		        self::CONTRASENA . "," .
		        self::CORREO . "," .
		        self::CLAVE_API .
		        " FROM " . self::NOMBRE_TABLA .
		        " WHERE " . self::CORREO . "=?";

		    $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

		    $sentencia->bindParam(1, $correo);

		    if ($sentencia->execute())
		        return $sentencia->fetch(PDO::FETCH_ASSOC);
		    else
		        return null;

		}


		private function validarContrasena($contrasenaPlana, $contrasenaHash){

		    return password_verify($contrasenaPlana, $contrasenaHash);

		}


		private function encriptarContrasena($contrasenaPlana){
		    if ($contrasenaPlana)
		        return password_hash($contrasenaPlana, PASSWORD_DEFAULT);
		    else 
		    	return null;
		
		}


		private function generarClaveApi(){

		    return md5(microtime().rand());

		}


		public static function autorizar(){

		    $cabeceras = apache_request_headers();

		    if (isset($cabeceras["authorization"])) {

		        $claveApi = $cabeceras["authorization"];

		        $resultado = usuarios::validarClaveApi($claveApi);

		        if ($resultado != 0 && $resultado != NULL)
		        	return usuarios::obtenerIdUsuario($claveApi);
		        else
		        	throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, self::MSG_CLAVE_NO_AUTORIZADA, 300);


		    } else {
		        throw new ExcepcionApi(self::ESTADO_AUSENCIA_CLAVE_API, utf8_encode("Se requiere Clave del API para autenticación"));
		    }

		}
		

		private function validarClaveApi($claveApi){

			$comando = "SELECT COUNT(" . self::ID_USUARIO . ") count" .
		        " FROM " . self::NOMBRE_TABLA .
		        " WHERE " . self::CLAVE_API . "='$claveApi'";

		    $resultado = ConexionBD::query_single_object($comando);

		    return $resultado->count;

		}


		private function obtenerIdUsuario($claveApi){

		    $comando = "SELECT " . self::ID_USUARIO . " FROM " . self::NOMBRE_TABLA . " WHERE " . self::CLAVE_API . "= '$claveApi'";

			if($resultado = ConexionBD::query_single_object($comando))
				return $resultado->idUsuario;
			else
				throw new ExcepcionApi(self::CODIGO_NO_ENCONTRADO, self::MSG_NO_ENCONTRADO, 404);

				

		}

	   
	}
?>