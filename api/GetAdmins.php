<?php

include_once("CheckLoggedIn.php");

include_once("Locations.php");
include_once("Tools.php");

$admins = file_get_contents(adminFile);

if (__FILE__ == str_replace('/', '\\', $_SERVER['SCRIPT_FILENAME'])) {
	
	$return = "false";
	$keywords = array('new', 'edit', 'delete', 'find', 'search');

	//To create a new admin with a given email
	if (onlyKeyword('new', $keywords)) {
		$return = createAdmin(
			getBool('frozen'),
			getString('new'),
			getEncrypted('password'),
			getString('firstName'),
			getString('surname')
		);
	}

	//To edit an existing admin with a given ID
	else if (onlyKeyword('edit', $keywords) &&
			 atLeastOne(array('frozen', 'email', 'password', 'firstName', 'surname'))) {
		$return = editAdmin(
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
		$return = deleteAdmin(
			getString('delete')
		);
	}

	//To find only specific admins with given IDs
	else if (onlyKeyword('find', $keywords)) {
		$return = findAdmin(
			getString('find'),
			getString('where')
		);
	}

	//To search for admins with a query
	else if (onlyKeyword('search', $keywords)) {
		$return = searchAdmin(
			getString('search'),
			getString('where')
		);
	}

	else if (onlyKeyword('test', array('test'))) {
		$return = json_encode(array('thing' => getBool('test')));
	}

	//Return a value if needed
	if (!empty($return))
		echo json_encode(getReturnReady($return, true));
}

//Functions
//=========

function createAdmin($frozen, $email, $password, $firstName, $surname) {
	$returnable = new stdClass();
	$returnable->id        = date("zyHis");
	$returnable->frozen    = $frozen;
	$returnable->email     = $email;
	$returnable->password  = $password;
	$returnable->firstName = $firstName;
	$returnable->surname   = $surname;
	$returnable->image     = profileFolder . "/" . $returnable->id . ".png";
	
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
			if ($password !== null)
				$person->password = $password;
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