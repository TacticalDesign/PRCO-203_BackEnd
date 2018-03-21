<?php

include_once("Locations.php");
include_once("Tools.php");
include_once("GetChallenges.php");

$youngPeople = file_get_contents(youngPeopleFile);

if (str_replace('/', '\\', __FILE__) == str_replace('/', '\\', $_SERVER['SCRIPT_FILENAME'])) {

	include_once("CheckLoggedIn.php");

	$response = array();
	$response['result'] = null;
	$response['count'] = 0;
	$response['errors'] = array();
	
	$keywords = array('new', 'edit', 'freeze', 'defrost', 'pay', 'charge',
					  'feedback', 'attend', 'delete', 'find', 'search');
	
	

	//To edit an existing young person
	if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
		$response['result'] = editYoungPerson(
			getString('email'),
			getEncrypted('password'),
			getString('firstName'),
			getString('surname'),
			getArray('skills'),
			getArray('interests')
		);
	}
	
	//To mark a young person as attending a challenge
	else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
		$response['result'] = attendYoungPerson(
			getString('challenge'),
			getBool('attending')
		);
	}

	//To delete a young person with a given ID
	else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
		$response['result'] = deleteYoungPerson();
	}

	//Return a value if needed
	$response['count'] = empty($response['result']) ? 0 : 
			is_array($response['result']) ? sizeof($response['result']) : 1;
	echo json_encode(getReturnReady($response, true));
}

//Functions
//=========

function editYoungPerson($email, $password, $firstName,
				         $surname, $skills, $interests) {
	//Check the user is a young person
	if (!isUserLevel('youngPerson')) {
		$GLOBALS['response']['errors'][] = "You have to be a young person to use this command";
		return null;
	}
	
	//Check the given email is valid
	if (!empty($email)) {
		$email = filter_var($email, FILTER_SANITIZE_EMAIL);
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$GLOBALS['response']['errors'][] = "$email is not a valid email address";
			return null;
		}
	}
	
	$id = getCurrentuserID();
	$returnable = getYoungPerson($id);
	
	if (!empty($email))
		$returnable->email = $email;
	if (!empty($password)) {
		$returnable->password = $password;
		unset($person->tempPassword);
	}
	if (!empty($firstName))
		$returnable->firstName = $firstName;
	if (!empty($surname))
		$returnable->surname = $surname;
	if (!empty($skills))
		$returnable->skills = $skills;
	if (!empty($interests))
		$returnable->interests = $interests;
	
	setYoungPerson($returnable);
	return $returnable;	
}

function chargeYoungPerson($debt) {
	//Check the user is a young person
	if (!isUserLevel('youngPerson')) {
		$GLOBALS['response']['errors'][] = "You have to be a young person to use this command";
		return null;
	}
	
	//Find and update the young person
	$returnable = getYoungPerson($id);
	$returnable->balance -= $debt;
	
	//Save and return the young person
	setYoungPerson($returnable);
	return $returnable;
}

function attendYoungPerson($challenge, $attending) {
	//Check the user is a young Person
	if (!isUserLevel('youngPerson')) {
		$GLOBALS['response']['errors'][] = "You have to be a young person to use this command";
		return null;
	}
	
	//Get and edit the young person
	$id = getCurrentuserID();
	$returnable = getYoungPerson($id);
	
	if ($attending)
		$returnable->currentChallenges[$challenge] = $challenge;
	else
		unset($returnable->currentChallenges[$challenge]);
	
	//Save and return the young person
	setYoungPerson($returnable);
	return $returnable;
}

function deleteYoungPerson() {
	//Check the user is a young Person
	if (!isUserLevel('youngPerson')) {
		$GLOBALS['response']['errors'][] = "You have to be a young person to use this command";
		return null;
	}
	
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