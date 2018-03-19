<?php

include_once("GetToken.php");
include_once("Locations.php");
include_once("GetAdmins.php");
include_once("GetChallengers.php");
include_once("GetYoungPeople.php");

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
		
			$userID = json_decode(base64_decode($checkPayload))->user_id;
			
			if (array_key_exists($userID, json_decode(file_get_contents(adminFile), true)))
				$match = json_decode(file_get_contents(adminFile), true)[$userID];
			if (array_key_exists($userID, json_decode(file_get_contents(challengerFile), true)))
				$match = json_decode(file_get_contents(challengerFile), true)[$userID];
			if (array_key_exists($userID, json_decode(file_get_contents(youngPeopleFile), true)))
				$match = json_decode(file_get_contents(youngPeopleFile), true)[$userID];
			
			
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
	echo json_encode(array('errors' => array($message)));
	die();
}




?>