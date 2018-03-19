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
	
	//To create a new young person with a given email
	if (onlyKeyword('new', $keywords)) {
		$response['result'] = createYoungPerson(
			getString('new'),
			getString('firstName')
		);
	}

	//To edit an existing young person at a given ID
	else if (onlyKeyword('edit', $keywords) && 
			 atLeastOne(array('email', 'password', 'firstName', 'surname',
							  'skills', 'interests'))) {
		$response['result'] = editYoungPerson(
			getString('edit'),
			getString('email'),
			getEncrypted('password'),
			getString('firstName'),
			getString('surname'),
			getArray('skills'),
			getArray('interests')
		);
	}
	
	//To freeze a young person
	else if (onlyKeyword('freeze', $keywords) &&
			 atLeastOne(array('id'))) {
		$response['result'] = freezeYoungPerson(
			getString('id')
		);
	}
	
	//To defrost a young person
	else if (onlyKeyword('defrost', $keywords) &&
			 atLeastOne(array('id'))) {
		$response['result'] = defrostYoungPerson(
			getString('id')
		);
	}
	
	//To pay a young person
	else if (onlyKeyword('pay', $keywords) &&
			 atLeastAll(array('id', 'pay'))) {
		$response['result'] = payYoungPerson(
			getString('id'),
			getInt('pay')
		);
	}
	
	//To charge a young person
	else if (onlyKeyword('charge', $keywords) &&
			 atLeastOne(array('id'))) {
		$response['result'] = chargeYoungPerson(
			getString('id')
		);
	}

	//To add a new feedback to a young person with a given ID
	else if (onlyKeyword('feedback', $keywords) &&
			 atLeastAll(array('challenge', 'rating'))) {
		$response['result'] = feedbackYoungPerson( 
			getString('feedback'),
			getString('challenge'),
			getInt('rating'),
			getString('comment')
		);
	}
	
	//To mark a young person with a given ID as attending a challenge
	else if (onlyKeyword('attend', $keywords) &&
			 atLeastAll(array('challenge', 'attending'))) {
		$response['result'] = attendYoungPerson(
			getString('attend'),
			getString('challenge'),
			getBool('attending')
		);
	}

	//To delete a young person with a given ID
	else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
		$response['result'] = deleteYoungPerson();
	}

	//To search all young people for a query
	else if (onlyKeyword('search', $keywords)) {
		$response['result'] = searchYoungPerson(
			getString('search'),
			getString('where')
		);
	}

	//Return a value if needed
	$response['count'] = empty($response['result']) ? 0 : 
			is_array($response['result']) ? sizeof($response['result']) : 1;
	echo json_encode(getReturnReady($response, true));
}

//Functions
//=========

function createYoungPerson($email, $firstName) {
	//Check the user is an admin
	if (!isUserLevel('admin')) {
		$GLOBALS['response']['errors'][] = "You have to be an admin to use this command";
		return null;
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
	$headers  = "From: NoReply@realideas.org;" . "\r\n";
	$headers .= "MIME-Version: 1.0;" . "\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
	
	if(!mail($email, $subject, $message, $headers)) {
		echo "Unable to email new address";
		/////////////////////////////////////////////////
		// MAJOR DEBUG CODE - PASSWORDS BEING LEAKED
		if(in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1','::1')))
			echo $tempPassword;
		/////////////////////////////////////////////////
		else 
			die();
	}
	else
		echo "Sent Email";
	
	//Create the new young person
	$returnable = new stdClass();
	$returnable->id                 = date("zyHis");
	$returnable->frozen				= false;
	$returnable->email              = $email;
	$returnable->password           = null;
	$returnable->tempPassword       = $tempPassword;
	$returnable->firstName          = $firstName;
	$returnable->surname            = null;
	$returnable->balance			= 0;
	$returnable->image              = profileFolder . "/" . $returnable->id . ".png";
	$returnable->skills             = array();
	$returnable->interests          = array();
	$returnable->currentChallenges  = array();
	$returnable->archivedChallenges = array();
	$returnable->feedbacks			= array();
	
	updateYoungPerson($returnable);
	return $returnable;
}

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
	$returnable = json_decode(file_get_contents(youngPeopleFile), true)[$id];
	
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
	
	updateYoungPerson($returnable);
	return $returnable;	
}

function freezeYoungPerson($id) {
	//Check the user is an admin
	if (!isUserLevel('admin')) {
		$GLOBALS['response']['errors'][] = "You have to be an admin to use this command";
		return null;
	}
	
	//Find and update the young person
	$returnable = json_decode(file_get_contents(youngPeopleFile), true)[$id];
	$returnable->frozen = true;
	
	//Save the young person
	updateYoungPerson($returnable);
	return $returnable;	
}

function defrostYoungPerson($id) {
	//Check the user is an admin
	if (!isUserLevel('admin')) {
		$GLOBALS['response']['errors'][] = "You have to be an admin to use this command";
		return null;
	}
	
	//Find and update the young person
	$returnable = json_decode(file_get_contents(youngPeopleFile), true)[$id];
	$returnable->frozen = false;
	
	//Save the young person
	updateYoungPerson($returnable);
	return $returnable;
}

function payYoungPerson($id, $pay) {
	//Check the user is a challenger
	if (!isUserLevel('challenger')) {
		$GLOBALS['response']['errors'][] = "You have to be a challenger to use this command";
		return null;
	}
	
	//Find and update the young person
	$id = getCurrentuserID();
	$returnable = json_decode(file_get_contents(youngPeopleFile), true)[$id];
	$returnable->balance -= $debt;
	
	//Save the young person
	updateYoungPerson($returnable);
	return $returnable;
}

function chargeYoungPerson($debt) {
	//Check the user is a challenger
	if (!isUserLevel('challenger')) {
		$GLOBALS['response']['errors'][] = "You have to be a challenger to use this command";
		return null;
	}
	
	//Find and update the young person
	$returnable = json_decode(file_get_contents(youngPeopleFile), true)[$id];
	$returnable->balance .= $pay;
	
	//Save the young person
	updateYoungPerson($returnable);
	return $returnable;
}

function feedbackYoungPerson($id, $challenge, $rating, $comment) {
	//Check the user is a challenger
	if (!isUserLevel('challenger')) {
		$GLOBALS['response']['errors'][] = "You have to be a challenger to use this command";
		return null;
	}
	
	//Create the feedback object
	$feedback = new stdClass();
	$feedback->challenge = $challenge;
	$feedback->rating = $rating;
	$feedback->comment = $comment;
	
	//Find and update the young person
	$returnable = json_decode(file_get_contents(youngPeopleFile), true)[$id];
	$returnable->feedbacks[] = $feedback;
	
	//Save the young person
	updateYoungPerson($returnable);
	return $returnable;
}

function attendYoungPerson($id, $challenge, $attending) {
	//Check the user is a young Person
	if (!isUserLevel('youngPerson')) {
		$GLOBALS['response']['errors'][] = "You have to be a young person to use this command";
		return null;
	}
	
	$id = getCurrentuserID();	
	$returnable = json_decode(file_get_contents(youngPeopleFile), true)[$id];
	
	if ($attending) {
		$returnable->currentChallenges[] = $challenge;
	} else {
		if (($key = array_search($challenge, $returnable->currentChallenges)) !== false) {
			unset($returnable->currentChallenges[$key]);
		}
	}
	
	//Save the young person
	updateYoungPerson($returnable);
	return $returnable;
}

function deleteYoungPerson() {
	//Check the user is a young Person
	if (!isUserLevel('youngPerson')) {
		$GLOBALS['response']['errors'][] = "You have to be a young person to use this command";
		return null;
	}
	
	$id = getCurrentuserID();
	$youngPeople = json_decode(file_get_contents(youngPeopleFile), true);
	$returnable = $youngPeople[$id];
	
	unset($youngPeople[$id]);
	file_put_contents(youngPeopleFile, json_encode($youngPeople));
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




function updateYoungPerson($updated) {
	$youngPeople = json_decode(file_get_contents(youngPeopleFile), true);
	$youngPeople[$updated->id] = $updated;
	file_put_contents(youngPeopleFile, json_encode($youngPeople, JSON_PRETTY_PRINT));
}


















?>