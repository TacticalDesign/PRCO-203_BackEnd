<?php

include_once("GetToken.php");
include_once("Locations.php");
include_once("Tools.php");

//If no JWT is given
if (empty(apache_request_headers()['Authorization']))
	killAll("No JWT given!");

$data = apache_request_headers()['Authorization'];

//Ensure the JWT is two words
if (sizeof(explode(' ', $data)) === 2) {
	$token = explode(' ', $data)[1];
	
	//Ensure the JWT is in the header.payload.signature format
	if (sizeof(explode('.', $token)) === 3) {
		$tokenParts = explode('.', $token);
		
		//Work out what the signature should be
		$correctSig = URLReady(base64_encode(hash_hmac('sha256', $tokenParts[0] . "." . $tokenParts[1], tokenSecret, true)));
		
		if ($correctSig !== $tokenParts[2])
			killAll("Incorrect JWT given!");
		
		$checkPayload = str_replace(['-', '_', ''], ['+', '/', '='], $tokenParts[1]);
		
		if (json_decode(base64_decode($checkPayload))->user_typ !== 'god') {
		
			$userID = getCurrentUserID();
			
			$match = null;
			$match = getAdmin($userID);
			if ($match === null)
				$match = getChallenger($userID);
			if ($match === null)
				$match = getYoungPerson($userID);
			
			//Check account isn't frozen
			if (!empty($match)) {
				if ($match->frozen)
					killAll("Your account is frozen");
			}
			else
				killAll("Account cannot be found");
		}
	}
	else
		killAll("Incorrect JWT given!");
}
else 
	killAll("Incorrect JWT given!");
	


function killAll($message) {
	$GLOBALS['response']['errors'][] = $message;
	$GLOBALS['response']['count'] = empty($GLOBALS['response']['result']) ? 0 : 
		(is_array($GLOBALS['response']['result']) ? sizeof($GLOBALS['response']['result']) : 1);
	echo json_encode(getReturnReady($GLOBALS['response'], true));
	die();
	
}




?>