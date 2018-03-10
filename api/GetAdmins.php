<?php

include("Locations.php");
include("Tools.php");

$keywords = array('new', 'edit', 'delete', 'find', 'search');
$return = "false";

$admins = file_get_contents(adminFile);

//To create a new admin with a given email
if (onlyKeyword('new', $keywords) &&
	atLeastOne(array('password', 'firstName', 'surname'))) {
	$return = createUser(
		getVar('new'),
		getEncrypted('password'),
		getVar('firstName'),
		getVar('surname')
	);
}

//To edit an existing admin with a given ID
else if (onlyKeyword('edit', $keywords) &&
		 atLeastOne(array('email', 'password', 'firstName', 'surname'))) {
	$return = editUser(
		getVar('edit'),
		getVar('email'),
		getEncrypted('password'),
		getVar('firstName'),
		getVar('surname')
	);
}

//To delete an admin with a given ID
else if (onlyKeyword('delete', $keywords)) {
	$return = deleteUser(
		getVar('delete')
	);
}

//To find only specific admins with given IDs
else if (onlyKeyword('find', $keywords)) {
	$return = findUsers(
		getVar('find'),
		getVar('where')
	);
}

//To search for admins with a query
else if (onlyKeyword('search', $keywords)) {
	$return = searchUsers(
		getVar('search'),
		getVar('where')
	);
}

//Return a value if needed
if (!empty($return))
	echo $return;

//Functions
//=========

function createUser($email, $password, $firstName, $surname) {
	$returnable = new stdClass();
	$returnable->id        = date("zyHis");
	$returnable->email     = $email;
	$returnable->password  = $password;
	$returnable->firstName = $firstName;
	$returnable->surname   = $surname;
	$returnable->image     = profileFolder . "/" . $returnable->id . ".png";
	
	$_admins = json_decode($GLOBALS['admins']);
	array_push($_admins, $returnable);
	$GLOBALS['admins'] = json_encode($_admins);
	file_put_contents(adminFile, $GLOBALS['admins']);
	unset($returnable->password);
	return json_encode($returnable);
}

function editUser($id, $email, $password, $firstName, $surname) {
	$_admins = json_decode($GLOBALS['admins']);
	
	$returnable = false;
	foreach($_admins as $i => $person) {
		if ($person->id == $id) {
			if ($email != null)
				$person->email = $email;
			if ($password != null)
				$person->password = $password;
			if ($firstName != null)
				$person->firstName = $firstName;
			if ($surname != null)
				$person->surname = $surname;
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['admins'] = json_encode($_admins);
	file_put_contents(adminFile, $GLOBALS['admins']);
	unset($returnable->password);
	return json_encode($returnable);
}

function deleteUser($ids) {
	$wantedIDs = explode(',', $ids);
	$_admins = json_decode($GLOBALS['admins']);
	
	$keeps = array();
	$returnable = array();
	foreach($_admins as $i => $person) {
		if (in_array($person->id, $wantedIDs)) {
			unset($person->password);
			array_push($returnable, $person);
		} else 
			array_push($keeps, $person);
	}
	
	$GLOBALS['admins'] = json_encode($keeps);
	file_put_contents(adminFile, $GLOBALS['admins']);
	return json_encode($returnable);
}

function findUsers($ids, $where) {
	
	$params = [];
	if ($where != null) {
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
			unset($person->password);
			array_push($wantedUsers, $person);
		}
	}
	
	return json_encode($wantedUsers);
}

function searchUsers($searchPhrase, $where) {
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
					unset($person->password);
					array_push($matches, $person);
					array_push($matchedIDs, $person->id);
				}
			}
		}
	}
	
	return json_encode($matches);
}
























?>