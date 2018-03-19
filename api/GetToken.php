<?php

include_once('Locations.php');

function getToken($id, $accountType) {
	$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
	$payload = json_encode(['user_id' => $id, 'user_typ' => $accountType]);

	// Encode header and payload to base64 and URL compliant
	$base64UrlHeader = URLReady(base64_encode($header));
	$base64UrlPayload = URLReady(base64_encode($payload));

	// Create Signature Hash
	$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, tokenSecret, true);

	// Encode signature to base64 and URL compliant
	$base64UrlSignature = URLReady(base64_encode($signature));

	return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function URLReady($value){
	return str_replace(['+', '/', '='], ['-', '_', ''], $value);
}

?>