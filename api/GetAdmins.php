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
	else if (onlyKeyword('delete', $keywords)) {
		$response['result'] = deleteAdmin(
			getString('delete')
		);
	}

	//To find only specific admins with given IDs
	else if (onlyKeyword('find', $keywords)) {
		$response['result'] = findAdmin(
			getString('find'),
			getString('where')
		);
	}

	//To search for admins with a query
	else if (onlyKeyword('search', $keywords)) {
		$response['result'] = searchAdmin(
			getString('search'),
			getString('where')
		);
	}

	else if (onlyKeyword('test', array('test'))) {
		$response['result'] = json_encode(array('thing' => getBool('test')));
	}

	//Return a value
	$response['count'] = is_array($response['result']) ? sizeof($response['result']) : 1;
	echo json_encode(getReturnReady($response, true));
}

//Functions
//=========

function createAdmin($email, $firstName) {
	$email = filter_var($email, FILTER_SANITIZE_EMAIL);
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$GLOBALS['response']['errors'][] = "$email is not a valid email address";
		return null;
	}
	
	$tempPassword = bin2hex(openssl_random_pseudo_bytes(4));
	
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
		$GLOBALS['response']['errors'][] = "Unable to email new address";
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
	
	$_admins = json_decode($GLOBALS['admins']);
	array_push($_admins, $returnable);
	$GLOBALS['admins'] = json_encode($_admins);
	file_put_contents(adminFile, $GLOBALS['admins']);
	return $returnable;
}

function editAdmin($id, $frozen, $email, $password, $firstName, $surname) {
	$_admins = json_decode($GLOBALS['admins']);
	
	$returnable = false;
	foreach($_admins as $i => $person) {
		if ($person->id == $id) {
			if ($frozen !== null)
				$person->frozen = $frozen;
			if ($email !== null)
				$person->email = $email;
			if ($password !== null) {
				$person->password = $password;
				unset($person->tempPassword);
			}
			if ($firstName !== null)
				$person->firstName = $firstName;
			if ($surname !== null)
				$person->surname = $surname;
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['admins'] = json_encode($_admins);
	file_put_contents(adminFile, $GLOBALS['admins']);
	return $returnable;
}

function deleteAdmin($ids) {
	$wantedIDs = explode(',', $ids);
	$_admins = json_decode($GLOBALS['admins']);
	
	$keeps = array();
	$returnable = array();
	foreach($_admins as $i => $person) {
		if (in_array($person->id, $wantedIDs)) {
			array_push($returnable, $person);
		} else 
			array_push($keeps, $person);
	}
	
	$GLOBALS['admins'] = json_encode($keeps);
	file_put_contents(adminFile, $GLOBALS['admins']);
	return $returnable;
}

function findAdmin($ids, $where) {
	
	$params = [];
	if ($where !== null) {
		if (!empty($where)) {
			$params = explode(';', $where);
			for	($iii = 0; $iii < count($params); $iii++) {
				$params[$iii] = explode(':', $params[$iii], 2);
			}
		}
	}
	
	$wantedIDs = explode(',', $ids);
	$wantedUsers = [];
	$_admins = json_decode($GLOBALS['admins']);
	
	foreach($_admins as $i => $person) {
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
		
		if ($ids == "all" || in_array($person->id, $wantedIDs)) {
			array_push($wantedUsers, $person);
		}
	}
	
	return $wantedUsers;
}

function searchAdmin($searchPhrase, $where) {
	$searchPhrase = strtolower($searchPhrase);
	$_admins = json_decode($GLOBALS['admins']);
	
	$searchTerms = [];
	for ($i = strlen($searchPhrase); $i > 1; $i--) {
		for ($ii = 0; $ii < strlen($searchPhrase) - $i + 1; $ii++) {
			array_push($searchTerms, substr($searchPhrase, $ii, $i));
		}
	}
	
	$params = [];
	if (!empty($where)) {
		$params = explode(';', $where);
		for	($iii = 0; $iii < count($params); $iii++) {
			$params[$iii] = explode(':', $params[$iii], 2);
		}
	}
	
	$matches = [];
	$matchedIDs = [];
	foreach ($searchTerms as $i => $term) {
		if (!empty($term)) {
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