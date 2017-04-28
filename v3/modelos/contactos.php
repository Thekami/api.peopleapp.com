<?php  

	require_once 'utilidades/ExcepcionApi.php';
	require_once 'utilidades/ConexionBD.php';
	require_once 'modelos/usuarios.php';
	
	class contactos {

	    const NOMBRE_TABLA = "contacto";
	    const ID_CONTACTO = "idContacto";
	    const PRIMER_NOMBRE = "primerNombre";
	    const PRIMER_APELLIDO = "primerApellido";
	    const TELEFONO = "telefono";
	    const CORREO = "correo";
	    const ID_USUARIO = "idUsuario";

	    const CODIGO_EXITO = 1;
	    const CODIGO_ERROR = 2;
	    const CODIGO_ERROR_BD = 3;
	    const CODIGO_ERROR_PARAMETROS = 4;
	    const CODIGO_NO_ENCONTRADO = 5;

	    const MSG_ERROR_BD = "Error de base de datos";
	    const MSG_NO_ENCONTRADO = "El contacto al que intentas acceder no existe";
	    const MSG_ERROR_PARAMETROS = "Error en existencia o sintaxis de parámetros";
	    const MSG_ERROR = "Error";
	    const MSG_EXITO = "Success";

	    public static function get($peticion){

	    	$idUsuario = usuarios::autorizar();

		    if (empty($peticion[0]))
		        return self::obtenerContactos($idUsuario);
		    else
		        return self::obtenerContactos($idUsuario, $peticion[0]);  
    
	    }

	    public static function post($peticion){

	        $idUsuario = usuarios::autorizar();


	        $body = file_get_contents('php://input');
	        $contacto = json_decode($body);

	       	
	        if(self::validaEstructura($contacto)){

		        $idContacto = contactos::crear($idUsuario, $contacto);

	        	http_response_code(201);
		        return [
		            "estado" => self::CODIGO_EXITO,
		            "mensaje" => "Contacto creado",
		            "id" => $idContacto
		        ];

		    }
		    else
		    	throw new ExcepcionApi(self::CODIGO_ERROR_PARAMETROS, self::MSG_ERROR_PARAMETROS, 422);

	    }

	    public static function put($peticion){

	        $idUsuario = usuarios::autorizar();
	        
            $body = file_get_contents('php://input');
            $contacto = json_decode($body);

            if (!empty($peticion[0]) && self::validaEstructura($contacto) == TRUE) {

	            self::actualizar($idUsuario, $contacto, $peticion[0])) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro actualizado correctamente"
                ];
	            
	        }
	        else
	        	throw new ExcepcionApi(self::CODIGO_ERROR_PARAMETROS, self::MSG_ERROR_PARAMETROS, 422);     
	    
	    }

	    public static function delete($peticion){
	    	
	        $idUsuario = usuarios::autorizar();

	        if (!empty($peticion[0])) {
	            
	            self::eliminar($idUsuario, $peticion[0])
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro eliminado correctamente"
                ];
	            
	        }
	        else
	            throw new ExcepcionApi(self::CODIGO_ERROR_PARAMETROS, self::MSG_ERROR_PARAMETROS, 422);
	        
	    }


		/**
	     * Obtiene la colección de contactos o un solo contacto indicado por el identificador
	     * @param int $idUsuario identificador del usuario
	     * @param null $idContacto identificador del contacto (Opcional)
	     * @return array registros de la tabla contacto
	     * @throws Exception
	     */
	    private function obtenerContactos($idUsuario, $idContacto = NULL){

	        if (!$idContacto)
	            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_USUARIO . "=$idUsuario";
	        else
	            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_CONTACTO . "= $idContacto AND " . self::ID_USUARIO . "= $idUsuario";
	        	

	        if ($resultado = ConexionBD::query_assoc($comando)) {
	        	http_response_code(200);
	            return
	                [
	                    "estado" => self::CODIGO_EXITO,
	                    "datos" => $resultado
	                ];	
	        }
	        else
	        	throw new ExcepcionApi(self::CODIGO_NO_ENCONTRADO, self::MSG_NO_ENCONTRADO, 404);

		}


		/**
	     * Añade un nuevo contacto asociado a un usuario
	     * @param int $idUsuario identificador del usuario
	     * @param mixed $contacto datos del contacto
	     * @return string identificador del contacto
	     * @throws ExcepcionApi
	     */
	    private function crear($idUsuario, $contacto){

			$primerNombre   = $contacto->primerNombre;
			$primerApellido = $contacto->primerApellido;
			$telefono       = $contacto->telefono;
			$correo         = $contacto->correo;

            $comando = "CALL SP_CREATECONTACTO(
            	'$primerNombre','$primerApellido','$telefono','$correo',$idUsuario
            )";

            $resultado = ConexionBD::query_single_object($comando);
            
            if($resultado != 0)
	            return $resultado->id;
            else
            	throw new ExcepcionApi(self::CODIGO_ERROR, self::MSG_ERROR, 500);

		}


		/**
	     * Actualiza el contacto especificado por idUsuario
	     * @param int $idUsuario
	     * @param object $contacto objeto con los valores nuevos del contacto
	     * @param int $idContacto
	     * @return PDOStatement
	     * @throws Exception
	     */


	    private function actualizar($idUsuario, $contacto, $idContacto){

	
			$primerNombre   = $contacto->primerNombre;
			$primerApellido = $contacto->primerApellido;
			$telefono       = $contacto->telefono;
			$correo         = $contacto->correo;

            // Creando consulta UPDATE
            $consulta = "
            	UPDATE " . self::NOMBRE_TABLA .
                " SET " . 
				self::PRIMER_NOMBRE . "   = '$primerNombre'," .
				self::PRIMER_APELLIDO . " = '$primerApellido'," .
				self::TELEFONO . "        = '$telefono'," .
				self::CORREO . "          = '$correo' " .
                " WHEREs " . 
                self::ID_CONTACTO . "= $idContacto AND " . 
                self::ID_USUARIO . "= $idUsuario";


            if($resultado = ConexionBD::query($consulta))
            	return $resultado;
            else
				throw new ExcepcionApi(self::CODIGO_ERROR, self::MSG_ERROR, 500);
 
	    }


	    /**
	     * Elimina un contacto asociado a un usuario
	     * @param int $idUsuario identificador del usuario
	     * @param int $idContacto identificador del contacto
	     * @return bool true si la eliminación se pudo realizar, en caso contrario false
	     * @throws Exception excepcion por errores en la base de datos
	     */
	    private function eliminar($idUsuario, $idContacto){

            $comando = "DELETE FROM " . self::NOMBRE_TABLA . 
            			" WHERE " . 
						self::ID_CONTACTO . " = $idContacto AND " .
						self::ID_USUARIO . "  = $idUsuario";

           	return ConexionBD::query($comando);


           	if ($resultado = ConexionBD::query($comando))
           		return $resultado;
           	else
            	throw new ExcepcionApi(self::CODIGO_ERROR, self::MSG_ERROR, 500);

	    }


	    /**
	     * Valida la estructura de un contacto
	     *
	     * @param  object  $info  objeto con información de contacto
	     * @return bool    true si el objeto tiene la estructura esperada, en caso contrario false
	     */
	    private function validaEstructura($info){

	    	if ($info !== NULL && 
	    		isset($info->primerNombre) && isset($info->primerApellido) && 
	        	isset($info->telefono) && isset($info->correo))
	    		return TRUE;
	    	else
	    		return FALSE;

	    }


	   
	}

?>