<?php

include_once("Locations.php");
include_once("Tools.php");

$admins = file_get_contents(adminFile);

if (str_replace('/', '\\', __FILE__) == str_replace('/', '\\', $_SERVER['SCRIPT_FILENAME'])) {

	include_once("CheckLoggedIn.php");

	$response = array();
	$response['result'] = null;
	$response['count'] = 0;
	$response['errors'] = array();
	
	$keywords = array('new', 'edit', 'delete', 'find', 'search');

	//To create a new admin with a given email
	if (onlyKeyword('new', $keywords)) {
		$response['result'] = createAdmin(
			getString('new'),
			getString('firstName')
		);
	}

	//To edit an existing admin with a given ID
	else if (onlyKeyword('edit', $keywords) &&
			 atLeastOne(array('frozen', 'email', 'password', 'firstName', 'surname'))) {
		$response['result'] = editAdmin(
			getString('edit'),
			getBool('frozen'),
			getString('email'),
			getEncrypted('password'),
			getString('firstName'),
			getString('surname')
		);
	}

	//To delete an admin with a given ID
	else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
		$response['result'] = deleteAdmin();
	}

	//To search for admins with a query
	else if (onlyKeyword('search', $keywords)) {
		$response['result'] = searchAdmin(
			getString('search'),
			getString('where')
		);
	}

	//Return a value
	if (!empty($response['result']))
		$response['count'] = is_array($response['result']) ? sizeof($response['result']) : 1;
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
	
	setYoungPerson($returnable);
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
	setYoungPerson($returnable);
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



function editAdmin($email, $password, $firstName, $surname) {
	//Check the user is an admin
	if (!isUserLevel('admin')) {
		$GLOBALS['response']['errors'][] = "You have to be an admin to use this command";
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
	$returnable = json_decode(file_get_contents(adminFile), true)[$id];
	
	if ($email !== null)
		$returnable->email = $email;
	if ($password !== null) {
		$returnable->password = $password;
		unset($returnable->tempPassword);
	}
	if ($firstName !== null)
		$returnable->firstName = $firstName;
	if ($surname !== null)
		$returnable->surname = $surname;
	
	setYoungPerson($returnable);
	return $returnable;
}

function freezeAdmin($id) {
	//Check the user is a god
	if (!isUserLevel('got')) {
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

function deleteAdmin() {
	//Check the user is an admin
	if (!isUserLevel('admin')) {
		$GLOBALS['response']['errors'][] = "You have to be an admin to use this command";
		return null;
	}
	
	$id = getCurrentuserID();
	$admins = json_decode(file_get_contents(adminFile), true);
	$returnable = $admins[$id];
	
	unset($admins[$id]);
	file_put_contents(adminFile, json_encode($admins));
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




function updateAdmin($updated) {
	$admins = json_decode(file_get_contents(adminFile), true);
	$admins[$updated->id] = $updated;
	file_put_contents(adminFile, json_encode($admins, JSON_PRETTY_PRINT));
}













?>