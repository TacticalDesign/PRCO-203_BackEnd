<?php

include_once("Locations.php");
include_once("Tools.php");

if (str_replace('/', '\\', __FILE__) == str_replace('/', '\\', $_SERVER['SCRIPT_FILENAME'])) {
	//Create the response
	$response = array();
	$response['result'] = null;
	$response['count'] = 0;
	$response['errors'] = array();
	
	//Check the user is an god
	if (!isUserLevel('god')) {
		$GLOBALS['response']['errors'][] = "You have to be a god account to use this command";
		return null;
	}
	
	//To create a new admin
	else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$response['result'] = createAdmin();
	}
	
	//Return a value
	$response['count'] = empty($response['result']) ? 0 : 
		(is_array($response['result']) ? sizeof($response['result']) : 1);
	echo json_encode(getReturnReady($response, true));
}

//Functions
//=========

//Admin

function createAdmin() {
	$email = forceString($_POST['email']);
	$firstName = forceString($_POST['firstName']);
	
	//Detect possible errors
	$validKeys = array('email', 'firstName');
	foreach (array_diff(array_keys($_POST), $validKeys) as $i => $wrongProp) {
		$GLOBALS['response']['errors'][] = "$wrongProp is not a valid property of a young person";
	}
	
	if (sizeof(array_intersect(array_keys($_POST), $validKeys)) === 0) {
		$GLOBALS['response']['errors'][] = 'No valid properties of a young person were given';
	}
	
	//Check the given email is valid
	$email = filter_var($email, FILTER_SANITIZE_EMAIL);
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$GLOBALS['response']['errors'][] = "$email is not a valid email address";
		return null;
	}
	
	//Generate a new temporary password 
	$tempPassword = bin2hex(openssl_random_pseudo_bytes(4));
	
	//Create and send an email with login details
	$subject = "Welcome to the Dead Pencil's App!";
	$props = array(
		'{$email}' => $email,
		'{$tempPassword}' => $tempPassword,
		'{$name}' => $firstName
	);
	$message = strtr(file_get_contents(newAccountEmail), $props);
	$headers  = "From: noreply@realideas.org;" . "\r\n";
	$headers .= "MIME-Version: 1.0;" . "\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
	
	if(in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1','::1'))) {		
		///////////////////////////////////////////////
		// MAJOR DEBUG CODE - PASSWORDS BEING LEAKED
		   echo $tempPassword;
		///////////////////////////////////////////////
	}
	else if(!mail($email, $subject, $message, $headers)) {
		$GLOBALS['response']['errors'][] = "Unable to send email to $email";
		die();
	}
	
	$returnable = new stdClass();
	$returnable->id           = date("zyHis");
	$returnable->frozen       = false;
	$returnable->email        = $email;
	$returnable->password     = null;
	$returnable->tempPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
	$returnable->firstName    = $firstName;
	$returnable->surname      = null;
	$returnable->image        = profileFolder . "/" . $returnable->id . ".png";
	
	setAdmin($returnable);
	return $returnable;
}

function freezeAdmin($id) {
	//Check the user is a god
	if (!isUserLevel('god')) {
		$GLOBALS['response']['errors'][] = "You have to be a god account to use this command";
		return null;
	}
	
	//Find and update the admin
	$returnable = json_decode(file_get_contents(adminFile), true)[$id];
	$returnable->frozen = true;
	
	//Save the admin
	updateAdmin($returnable);
	return $returnable;	
}

function defrostAdmin($id) {
	//Check the user is a god
	if (!isUserLevel('god')) {
		$GLOBALS['response']['errors'][] = "You have to be a god account to use this command";
		return null;
	}
	
	//Find and update the admin
	$returnable = json_decode(file_get_contents(adminFile), true)[$id];
	$returnable->frozen = false;
	
	//Save the admin
	updateAdmin($returnable);
	return $returnable;
}

function searchAdmin($searchPhrase, $where) {
	$searchPhrase = strtolower($searchPhrase);
	
	//Find all the different sub-search terms
	$searchTerms = array();
	for ($i = strlen($searchPhrase); $i > 1; $i--) {
		for ($ii = 0; $ii < strlen($searchPhrase) - $i + 1; $ii++) {
			array_push($searchTerms, substr($searchPhrase, $ii, $i));
		}
	}
	
	//Find all the fixed parameters
	$params = array();
	if (!empty($where)) {
		$params = explode(';', $where);
		for	($iii = 0; $iii < count($params); $iii++) {
			$params[$iii] = explode(':', $params[$iii], 2);
		}
	}
	
	$admins = json_decode(file_get_contents(adminFile), true);
	
	$matches = array();
	$matchedIDs = array();
	
	//For every search term
	foreach ($searchTerms as $i => $term) {
		if (!empty($term)) {
			//For every young person
			foreach ($_admins as $ii => $person) {
				$skip = false;
				foreach	($params as $param) {
					if (is_bool($person->{$param[0]})) {
						if (json_decode($person->{$param[0]}) != json_decode($param[1])) {
							$skip = true;
							break;
						}
					} else {
						if ($person->{$param[0]} != $param[1]) {
							$skip = true;
							break;
						}
					}
				}
				
				if ($skip)
					continue;
				
				if ((strpos(strtolower($person->firstName), $term) !== false
							  || strpos(strtolower($person->surname), $term) !== false)
							  && !in_array($person->id, $matchedIDs)) {
					array_push($matches, $person);
					array_push($matchedIDs, $person->id);
				}
			}
		}
	}
	
	return $matches;
}
	
?>