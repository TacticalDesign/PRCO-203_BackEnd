<?php

include_once("Locations.php");
include_once("Tools.php");

$challengers = file_get_contents(challengerFile);

if (str_replace('/', '\\', __FILE__) == str_replace('/', '\\', $_SERVER['SCRIPT_FILENAME'])) {

	include_once("CheckLoggedIn.php");

	$response = array();
	$response['result'] = null;
	$response['count'] = 0;
	$response['errors'] = array();
	
	$keywords = array('new', 'edit', 'freeze', 'defrost', 'delete', 'search');

	//To create a new challenger with a given email
	if (onlyKeyword('new', $keywords)) {
		$response['result'] = createChallenger(
			getString('new'),
			getString('name')
		);
	}

	//To edit an existing challenger with a given ID
	else if (onlyKeyword('edit', $keywords) &&
			 atLeastOne(array('email', 'password', 'name', 'colour',
							  'contactEmail', 'contactPhone', 'about'))) {
		$response['result'] = editChallenger(
			getString('edit'),
			getString('email'),
			getEncrypted('password'),
			getString('name'),
			getString('colour'),
			getString('contactEmail'),
			getString('contactPhone'),
			getString('about')
		);
	}
	
	//To freeze a challenger
	else if (onlyKeyword('freeze', $keywords) &&
			 atLeastOne(array('id'))) {
		$response['result'] = freezeChallenger(
			getString('id')
		);
	}
	
	//To defrost a challenger
	else if (onlyKeyword('defrost', $keywords) &&
			 atLeastOne(array('id'))) {
		$response['result'] = defrostChallenger(
			getString('id')
		);
	}

	//To delete a challenger with a given ID
	else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
		$response['result'] = deleteChallenger();
	}

	//To search for challengers with a query
	else if (onlyKeyword('search', $keywords)) {
		$response['result'] = searchChallenger(
			getString('search'),
			getString('where')
		);
	}

	//Return a value if needed
	$response['count'] = is_array($response['result']) ? sizeof($response['result']) : 1;
	echo json_encode(getReturnReady($response, true));
}

//Functions
//=========

function createChallenger($email, $name) {
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
	
	//Create the new challenger
	$returnable = new stdClass();
	$returnable->id                 = date("zyHis");
	$returnable->frozen             = false;
	$returnable->email              = $email;
	$returnable->password           = null;
	$returnable->tempPassword       = $tempPassword;
	$returnable->name               = $name;
	$returnable->image              = profileFolder . "/" . $returnable->id . ".png";
	$returnable->cover              = coverPhotoFolder . "/" . $returnable->id . ".png";
	$returnable->colour             = null;
	$returnable->contactEmail       = null;
	$returnable->contactPhone       = null;
	$returnable->about              = null;
	$returnable->currentChallenges  = array();
	$returnable->archivedChallenges = array();
	
	updateChallenger($returnable);
	return $returnable;
}

function editChallenger($email, $password, $name, $colour,
						$contactEmail, $contactPhone, $about) {
	//Check the user is a challenger
	if (!isUserLevel('challenger')) {
		$GLOBALS['response']['errors'][] = "You have to be a challenger to use this command";
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
	$returnable = json_decode(file_get_contents(challengerFile), true)[$id];
	
	if ($email !== null)
		$returnable->email = $email;
	if ($password !== null) {
		$returnable->password = $password;
		unset($returnable->tempPassword);
	}
	if ($name !== null)
		$returnable->name = $name;
	if ($colour !== null)
		$returnable->colour = $colour;
	if ($contactEmail !== null)
		$returnable->contactEmail = $contactEmail;
	if ($contactPhone !== null)
		$returnable->contactPhone = $contactPhone;
	if ($about !== null)
		$returnable->about = $about;
	
	updateChallenger($returnable);
	return $returnable;	
}

function freezeChallenger($id) {
	//Check the user is an admin
	if (!isUserLevel('admin')) {
		$GLOBALS['response']['errors'][] = "You have to be an admin to use this command";
		return null;
	}
	
	//Find and update the challenger
	$returnable = json_decode(file_get_contents(challengerFile), true)[$id];
	$returnable->frozen = true;
	
	//Save the challenger
	updateChallenger($returnable);
	return $returnable;	
}

function defrostChallenger($id) {
	//Check the user is an admin
	if (!isUserLevel('admin')) {
		$GLOBALS['response']['errors'][] = "You have to be an admin to use this command";
		return null;
	}
	
	//Find and update the challenger
	$returnable = json_decode(file_get_contents(challengerFile), true)[$id];
	$returnable->frozen = false;
	
	//Save the challenger
	updateChallenger($returnable);
	return $returnable;
}

function deleteChallenger() {
	//Check the user is a challenger
	if (!isUserLevel('challenger')) {
		$GLOBALS['response']['errors'][] = "You have to be a challenger to use this command";
		return null;
	}
	
	$id = getCurrentuserID();
	$challengers = json_decode(file_get_contents(challengerFile), true);
	$returnable = $challengers[$id];
	
	unset($challengers[$id]);
	file_put_contents(challengerFile, json_encode($challengers));
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





function updateChallenger($updated) {
	$challengers = json_decode(file_get_contents(challengerFile), true);
	$challengers[$updated->id] = $updated;
	file_put_contents(challengerFile, json_encode($challengers, JSON_PRETTY_PRINT));
}



















?>