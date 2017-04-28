<?php  

	$vec = array(1,2,3,4,5);


	try {
		echo $vec[5]."fxsdfgsd"
	} catch (Exception $e) {
		// echo 'prev';
		// var_dump($e);
		// echo 'prev';
		// exit;
		echo $e->getMessage();
	}

?>