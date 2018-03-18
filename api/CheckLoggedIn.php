<?php

include_once("GetToken.php");

if (empty(apache_request_headers()['Authorization']))
	killAll();

$data = apache_request_headers()['Authorization'];

if (sizeof(explode(' ', $data)) === 2) {
	$token = explode(' ', $data)[1];
	if (sizeof(explode('.', $token)) === 3) {
		$tokenParts = explode('.', $token);
		
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
	echo "Incorrect JWT given!";
	die();
}



























?>