<?php

function getToken($id, $accountType) {
	$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
	$payload = json_encode(['user_id' => $id, 'user_typ' => $accountType]);

	// Encode Header to Base64Url String
	$base64UrlHeader = URLReady(base64_encode($header));

	// Encode Payload to Base64Url String
	$base64UrlPayload = URLReady(base64_encode($payload));

	// Create Signature Hash
	$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'abC123!', true);

	// Encode Signature to Base64Url String
	$base64UrlSignature = URLReady(base64_encode($signature));

	// Create JWT
	$jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

	return $jwt;
}

function URLReady($value){
	return str_replace(['+', '/', '='], ['-', '_', ''], $value);
}

?>