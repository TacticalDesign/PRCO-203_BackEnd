<?php

include_once("GetToken.php");

//If no JWT is given
if (empty(apache_request_headers()['Authorization']))
	killAll();

$data = apache_request_headers()['Authorization'];

//Ensure the JWT is two words
if (sizeof(explode(' ', $data)) === 2) {
	$token = explode(' ', $data)[1];
	
	//Ensure the JWT is a header.payload.signature
	if (sizeof(explode('.', $token)) === 3) {
		$tokenParts = explode('.', $token);
		
		//Work out what the signature should be
		$correctSig = URLReady(base64_encode(hash_hmac('sha256', $tokenParts[0] . "." . $tokenParts[1], 'abC123!', true)));
		
		if ($correctSig !== $tokenParts[2])
			killAll();
	}
	else
		killAll();
}
else 
	killAll();
	


function killAll() {
	echo json_encode(array('errors' => array("Incorrect JWT given!")));
	die();
}




?>