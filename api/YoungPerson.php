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
	
	//Check the user is a young person
	if (!isUserLevel('youngPerson')) {
		$response['errors'][] = 'You have to be a young person to use this command';
	}
	
	//To get an existing young person
	else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$id = getCurrentUserID();
		$response['result'] = getYoungPerson($id);
	}
	
	//To edit an existing young person
	else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
		$response['result'] = editYoungPerson();
	}
	
	//To mark a young person as attending a challenge
	else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
		$response['result'] = attendYoungPerson();
	}
	
	//To delete a young person
	else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
		$response['result'] = deleteYoungPerson();
	}
	
	//Return a value if needed
	$response['count'] = empty($response['result']) ? 0 : 
		(is_array($response['result']) ? sizeof($response['result']) : 1);
	echo json_encode(getReturnReady($response, true));
}

//Functions
//=========

//Young People

function editYoungPerson() {
	parse_str(file_get_contents('php://input'), $putVars);
	
	//Check the given email is valid
	if (!empty($putVars['firstName'])) {
		$putVars['firstName'] = filter_var($putVars['firstName'], FILTER_SANITIZE_EMAIL);
		if (!filter_var($putVars['firstName'], FILTER_VALIDATE_EMAIL)) {
			$GLOBALS['response']['errors'][] = "$email is not a valid email address";
			return null;
		}
	}
	
	//Detect possible errors
	$validKeys = array('email', 'password', 'firstName', 'surname', 'skills', 'interests');
	foreach (array_diff(array_keys($putVars), $validKeys) as $i => $wrongProp) {
		$GLOBALS['response']['errors'][] = "$wrongProp is not a valid property of a young person";
	}
	
	if (sizeof(array_intersect(array_keys($putVars), $validKeys)) === 0)
		$GLOBALS['response']['errors'][] = 'No valid properties of a young person were given';
	
	//Get the young person
	$id = getCurrentuserID();
	$returnable = getYoungPerson($id);
	
	//Edit the young person
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
	if (!empty($putVars['skills']))
		$returnable->skills = forceStringArray($putVars['skills']);
	if (!empty($putVars['interests']))
		$returnable->interests = forceStringArray($putVars['interests']);
	
	//Save and return the young person
	setYoungPerson($returnable);
	return $returnable;	
}

function chargeYoungPerson($debt) {
	//Check the input is a number format
	$debt = forceInt($debt);
	
	//Find and update the young person
	$id = getCurrentUserID();
	$returnable = getYoungPerson($id);
	
	if ($returnable->balance >= $debt)
		$returnable->balance -= $debt;
	else
		$GLOBALS['response']['errors'][] = "$returnable->firstName only has $returnable->balance. This is less than $debt";
	
	//Save and return the young person
	setYoungPerson($returnable);
	return $returnable;
}

function attendYoungPerson() {
	parse_str(file_get_contents('php://input'), $patchVars);
	
	//Detect possible errors
	$validKeys = array('challenge', 'attending');
	foreach (array_diff(array_keys($patchVars), $validKeys) as $i => $wrongProp) {
		$GLOBALS['response']['errors'][] = "$wrongProp is not a valid property of a young person";
	}
	
	if (sizeof(array_diff(array_keys($patchVars), $validKeys)) !== 0)
		$GLOBALS['response']['errors'][] = 'Not all required values were given';
	
	//Get the young person
	$id = getCurrentuserID();
	$returnable = getYoungPerson($id);
	
	//Get the challenge
	$challenge = getChallenge($patchVars['challenge']);
	
	//Set the attending value
	if (forceBool($patchVars['attending'])) {
		$returnable->currentChallenges[$patchVars['challenge']] = $patchVars['challenge'];
		$challenge->attendees[$id] = $id;
	}
	else {
		unset($returnable->currentChallenges[$patchVars['challenge']]);
		unset($challenge->attendees[$id]);
	}
	
	//Save the challenge
	setChallenge($challenge);
	//Save and return the young person
	setYoungPerson($returnable);
	return $returnable;
}

function deleteYoungPerson() {
	//Get and delete the young person
	$id = getCurrentuserID();
	$youngPeople = json_decode(file_get_contents(youngPeopleFile), true);
	$returnable = $youngPeople[$id];
	unset($youngPeople[$id]);
	
	//Save and return the young person
	file_put_contents(youngPeopleFile, json_encode($youngPeople));
	return $returnable;
}




















?>