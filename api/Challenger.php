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
	
	//Check the user is a challenger
	if (!isUserLevel('challenger')) {
		$response['errors'][] = 'You have to be a challenger to use this command';
	}
	
	//To get an existing challenger
	else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$id = getCurrentUserID();
		$response['result'] = getChallenger($id);
	}

	//To edit an existing challenger
	else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
		$response['result'] = editChallenger();
	}

	//To delete a challenger
	else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
		$response['result'] = deleteChallenger();
	}
	
	//To give feedback to and pay a young person
	else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
		$response['result'] = feedbackYoungPerson();
	}
	
	//To give feedback to a young Person

	//Return a value
	$response['count'] = empty($response['result']) ? 0 : 
		(is_array($response['result']) ? sizeof($response['result']) : 1);
	echo json_encode(getReturnReady($response, true));
}

//Functions
//=========

//Young People

function feedbackYoungPerson() {
	parse_str(file_get_contents('php://input'), $patchVars);
	
	//Detect possible errors
	$validKeys = array('id', 'challenge', 'rating', 'comment', 'pay');
	foreach (array_diff(array_keys($postVars), $validKeys) as $i => $wrongProp) {
		$GLOBALS['response']['errors'][] = "$wrongProp is not a valid property of a challenger";
	}
	
	if (sizeof(array_intersect(array_keys($postVars), $validKeys)) === 0)
		$GLOBALS['response']['errors'][] = 'No valid properties of a challenger were given';
	
	//Get the challenge
	$patchVars['challenge'] = getChallenge($patchVars['challenge']);
	
	//Create the feedback object
	$feedback = new stdClass();
	$feedback->challenge = $patchVars['challenge'];
	$feedback->rating = forceInt($patchVars['rating']);
	$feedback->comment = forceString($patchVars['comment']);
	
	//Find and give the feedback to the young person
	$returnable = getYoungPerson($patchVars['id']);
	$returnable->feedbacks[$patchVars['challenge']] = $feedback;
	
	//Pay the young person for the challenge
	$returnable->balance += $patchVars['pay'];
	
	//Save and return the young person
	getChallenge($returnable);
	return $returnable;
}

//Challengers

function editChallenger() {
	
	parse_str(file_get_contents('php://input'), $postVars);
	
	//Check the given email is valid
	if (!empty($email)) {
		$email = filter_var($email, FILTER_SANITIZE_EMAIL);
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$GLOBALS['response']['errors'][] = "$email is not a valid email address";
			return null;
		}
	}
	
	//Detect possible errors
	$validKeys = array('email', 'password', 'name', 'colour', 'contactEmail', 'contactPhone', 'about');
	foreach (array_diff(array_keys($postVars), $validKeys) as $i => $wrongProp) {
		$GLOBALS['response']['errors'][] = "$wrongProp is not a valid property of a young person";
	}
	
	if (sizeof(array_intersect(array_keys($postVars), $validKeys)) === 0)
		$GLOBALS['response']['errors'][] = 'No valid properties of a young person were given';
	
	//Get the challenger
	$id = getCurrentuserID();
	$returnable = getChallenger($id);
	
	//Edit the challenger
	if (!empty($postVars['email']))
		$returnable->email = forceString($postVars['email']);
	if (!empty($postVars['password'])) {
		$returnable->password = forceString($postVars['password']);
		unset($returnable->tempPassword);
	}
	if (!empty($postVars['name']))
		$returnable->name = forceString($postVars['name']);
	if (!empty($postVars['colour']))
		$returnable->colour = forceString($postVars['colour']);
	if (!empty($postVars['contactEmail']))
		$returnable->contactEmail = forceString($postVars['contactEmail']);
	if (!empty($postVars['contactPhone']))
		$returnable->contactPhone = forceString($postVars['contactPhone']);
	if (!empty($postVars['about']))
		$returnable->about = forceString($postVars['about']);
	
	//Save and return the challenger
	setChallenger($returnable);
	return $returnable;	
}

function deleteChallenger() {
	//Get and delete the challenger
	$id = getCurrentuserID();
	$challengers = json_decode(file_get_contents(challengerFile), true);
	$returnable = $challengers[$id];
	unset($challengers[$id]);
	
	//Save and return the challenger
	file_put_contents(challengerFile, json_encode($challengers));
	return $returnable;
}






















?>