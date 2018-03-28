<?php

include_once('Locations.php');
include_once('Tools.php');

if (str_replace('/', '\\', __FILE__) == str_replace('/', '\\', $_SERVER['SCRIPT_FILENAME'])) {
	//Create the response
	$response = array();
	$response['result'] = null;
	$response['count'] = 0;
	$response['errors'] = array();
	
	//Check the user has valid login details
	include_once('CheckLoggedIn.php');
	
	//Check the user is an admin
	if (!isUserLevel('admin')) {
		$response['errors'][] = 'You have to be an admin to use this command';
	}
	
	//To get an existing admin
	else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$id = getCurrentUserID();
		$response['result'] = getAdmin($id);
	}

	//To edit an existing admin
	else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
		$response['result'] = editAdmin();
	}

	//To create a new challenger/young person
	else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if ($_POST['type'] === 'challenger')
			$response['result'] = createChallenger();
		else if ($_POST['type'] === 'young person')
			$response['result'] = createYoungPerson();
		else
			$response['errors'][] = "$_POST[type] is not a correct user type";
	}

	//To delete an admin with a given ID
	else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
		$response['result'] = deleteAdmin();
	}

	//To freeze/defrost a challenger/young person
	else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
		parse_str(file_get_contents('php://input'), $patchVars);
		
		//Detect possible errors
		if ($patchVars['action'] !== 'freeze' && $patchVars['action'] !== 'defrost')
			$response['errors'][] = "$patchVars[action] is not a correct action";
		
		$isChallenger = $isYoungPerson = false;
		$user = getChallenger($patchVars['id']);
		if (!empty($user))
			$isChallenger = true;
		else {
			$user = getYoungPerson($patchVars['id']);
		}
		
		if (!empty($user))
			$isYoungPerson = true;
		else
			$response['errors'][] = "$patchVars[id] is not a valid user";
		
		//Decide which method to call
		if ($isChallenger) {
			if ($patchVars['action'] === 'freeze')
				$response['result'] = freezeChallenger($patchVars['id']);
			else if ($patchVars['action'] === 'defrost')
				$response['result'] = defrostChallenger($patchVars['id']);
		}
		else if ($isYoungPerson){
			if ($patchVars['action'] === 'freeze')
				$response['result'] = freezeYoungPerson($patchVars['id']);
			else if ($patchVars['action'] === 'defrost')
				$response['result'] = defrostYoungPerson($patchVars['id']);
		}
	}

	//Return a value
	$response['count'] = empty($response['result']) ? 0 : 
		(is_array($response['result']) ? sizeof($response['result']) : 1);
	echo json_encode(getReturnReady($response, true));
}

//Functions
//=========

//Young People

function createYoungPerson() {
	$email = forceString($_POST['email']);
	$firstName = forceString($_POST['firstName']);
	
	//Detect possible errors
	$validKeys = array('type', 'email', 'firstName');
	foreach (array_diff(array_keys($_POST), $validKeys) as $i => $wrongProp) {
		$GLOBALS['response']['errors'][] = "$wrongProp is not a valid property of an admin";
	}
	
	if (sizeof(array_intersect(array_keys($_POST), $validKeys)) === 0)
		$GLOBALS['response']['errors'][] = 'No valid properties of an admin were given';
	
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
		   file_put_contents('PASSWORD_LEAK.txt', $tempPassword);
		///////////////////////////////////////////////
	}
	else if(!mail($email, $subject, $message, $headers)) {
		$GLOBALS['response']['errors'][] = "Unable to send email to $email";
		die();
	}
	
	//Create the new young person
	$returnable = new stdClass();
	$returnable->id                 = getNewID();
	$returnable->frozen				= false;
	$returnable->email              = $email;
	$returnable->password           = null;
	$returnable->tempPassword       = password_hash($tempPassword, PASSWORD_BCRYPT);
	$returnable->firstName          = $firstName;
	$returnable->surname            = null;
	$returnable->balance			= 0;
	$returnable->image              = profileFolder . '/' . $returnable->id . '.png';
	$returnable->skills             = array();
	$returnable->interests          = array();
	$returnable->currentChallenges  = array();
	$returnable->archivedChallenges = array();
	$returnable->feedbacks			= array();
	
	setYoungPerson($returnable);
	return $returnable;
}

function freezeYoungPerson($id) {
	//Find and update the young person
	$returnable = getYoungPerson($id);
	$returnable->frozen = true;
	
	//Save the young person
	setYoungPerson($returnable);
	return $returnable;	
}

function defrostYoungPerson($id) {	
	//Find and update the young person
	$returnable = getYoungPerson($id);
	$returnable->frozen = false;
	
	//Save the young person
	setYoungPerson($returnable);
	return $returnable;
}

function searchYoungPerson($searchPhrase, $where) {
	$searchPhrase = strtolower($searchPhrase);
	
	//Find all the different sub-search terms
	$searchTerms = array();
	for ($i = strlen($searchPhrase); $i > 1; $i--) {
		for ($ii = 0; $ii < strlen($searchPhrase) - $i + 1; $ii++) {
			$searchTerms[] = substr($searchPhrase, $ii, $i);
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
	
	$youngPeople = json_decode(file_get_contents(youngPeopleFile), true);
	
	$matches = array();
	$matchedIDs = array();
	
	//For every search term
	foreach ($searchTerms as $i => $term) {
		if (!empty($term)) {
			//For every young person
			foreach ($youngPeople as $ii => $person) {
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
							  || strpos(strtolower($person->surname), $term) !== false
							  || strpos(strtolower(implode("|", $person->skills)), $term) !== false
							  || strpos(strtolower(implode("|", $person->interests)), $term) !== false)
							  && !in_array($person->id, $matchedIDs)) {
					array_push($matches, $person);
					array_push($matchedIDs, $person->id);
				}
			}
		}
	}
	
	return $matches;
}

//Challengers

function createChallenger() {
	$email = forceString($_POST['email']);
	$name = forceString($_POST['name']);
	
	//Check the given email is valid
	$email = filter_var($email, FILTER_SANITIZE_EMAIL);
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$GLOBALS['response']['errors'][] = "$email is not a valid email address";
		return null;
	}
	
	//Detect possible errors
	$validKeys = array('type', 'email', 'name');
	foreach (array_diff(array_keys($_POST), $validKeys) as $i => $wrongProp) {
		$GLOBALS['response']['errors'][] = "$wrongProp is not a valid property of a challenger";
	}
	
	if (sizeof(array_intersect(array_keys($_POST), $validKeys)) === 0)
		$GLOBALS['response']['errors'][] = 'No valid properties of a young person were given';
	
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
		'{$name}' => $name
	);
	$message = strtr(file_get_contents(newAccountEmail), $props);
	$headers  = "From: noreply@realideas.org;" . "\r\n";
	$headers .= "MIME-Version: 1.0;" . "\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
	
	if(in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1','::1'))) {		
		///////////////////////////////////////////////
		// MAJOR DEBUG CODE - PASSWORDS BEING LEAKED
		//   echo $tempPassword;
		///////////////////////////////////////////////
	}
	else if(!mail($email, $subject, $message, $headers)) {
		$GLOBALS['response']['errors'][] = "Unable to send email to $email";
		die();
	}
	
	//Create the new challenger
	$returnable = new stdClass();
	$returnable->id                 = getNewID();
	$returnable->frozen             = false;
	$returnable->email              = $email;
	$returnable->password           = null;
	$returnable->tempPassword       = password_hash($tempPassword, PASSWORD_BCRYPT);
	$returnable->name               = $name;
	$returnable->image              = profileFolder . '/' . $returnable->id . '.png';
	$returnable->cover              = coverPhotoFolder . '/' . $returnable->id . '.png';
	$returnable->colour             = null;
	$returnable->contactEmail       = null;
	$returnable->contactPhone       = null;
	$returnable->about              = null;
	$returnable->currentChallenges  = array();
	$returnable->archivedChallenges = array();
	
	setChallenger($returnable);
	return $returnable;
}

function freezeChallenger($id) {	
	//Find and update the challenger
	$returnable = getChallenger($id);
	$returnable->frozen = true;
	
	//Save the challenger
	setChallenger($returnable);
	return $returnable;	
}

function defrostChallenger($id) {	
	//Find and update the challenger
	$returnable = getChallenger($id);
	$returnable->frozen = false;
	
	//Save the challenger
	setChallenger($returnable);
	return $returnable;
}

function searchChallenger($searchPhrase, $where) {
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
	
	$challengers = json_decode(file_get_contents(youngPeopleFile), true);
	
	$matches = array();
	$matchedIDs = array();
	
	//For every search term
	foreach ($searchTerms as $i => $term) {
		if (!empty($term)) {
			//For every challenger
			foreach ($challengers as $ii => $person) {
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
				
				if ((strpos(strtolower($person->name), $term) !== false
							  || strpos(strtolower($person->about), $term) !== false)
							  && !in_array($person->id, $matchedIDs)) {
					array_push($matches, $person);
					array_push($matchedIDs, $person->id);
				}
			}
		}
	}
	
	return $matches;
}

//Admins

function editAdmin() {
	parse_str(file_get_contents('php://input'), $putVars);
	
	//Check the given email is valid
	if (!empty($putVars['email'])) {
		$putVars['email'] = filter_var($email, FILTER_SANITIZE_EMAIL);
		if (!filter_var($putVars['email'], FILTER_VALIDATE_EMAIL)) {
			$GLOBALS['response']['errors'][] = "$putVars[email] is not a valid email address";
			return null;
		}
	}
	
	//Detect possible errors
	$validKeys = array('email', 'password', 'firstName', 'surname');
	foreach (array_diff(array_keys($putVars), $validKeys) as $i => $wrongProp) {
		$GLOBALS['response']['errors'][] = "$wrongProp is not a valid property of an admin";
	}
	
	if (sizeof(array_intersect(array_keys($putVars), $validKeys)) === 0)
		$GLOBALS['response']['errors'][] = 'No valid properties of a young person were given';
	
	//Get the admin
	$id = getCurrentuserID();
	$returnable = getAdmin($id);
	
	//Edit the admin
	if (!empty($putVars['email']))
		$returnable->email = forceString($putVars['email']);
	if (!empty($putVars['password'])) {
		$returnable->password = password_hash(forceString($putVars['password']), PASSWORD_BCRYPT);
		unset($person->tempPassword);
	}
	if (!empty($putVars['firstName']))
		$returnable->firstName = forceString($putVars['firstName']);
	if (!empty($putVars['surname']))
		$returnable->surname = forceString($putVars['surname']);
	
	setAdmin($returnable);
	return $returnable;
}

function deleteAdmin() {
	//Get and delete the admin
	$id = getCurrentuserID();
	$admins = json_decode(file_get_contents(adminFile), true);
	$returnable = $admins[$id];
	unset($admins[$id]);
	
	//Save and return the admin
	file_put_contents(adminFile, json_encode($admins));
	return $returnable;
}

















?>