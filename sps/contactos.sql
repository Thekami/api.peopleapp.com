DROP PROCEDURE IF EXISTS SP_CREATECONTACTO;

DELIMITER $$

CREATE PROCEDURE SP_CREATECONTACTO(
	IN _primerNombre VARCHAR(50), IN _primerApellido VARCHAR(50), 
	IN _telefono VARCHAR(50), IN _correo VARCHAR(50), IN _idUsuario INT
)
BEGIN	

	INSERT INTO contacto (
		primerNombre, primerApellido, telefono, correo, idUsuario
	)
	VALUES(
		_primerNombre, _primerApellido, _telefono, _correo, _idUsuario
	);
	
	SELECT DISTINCT LAST_INSERT_ID() id FROM contacto;

END $$
DELIMITER ;