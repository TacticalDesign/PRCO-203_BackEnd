<?php

include("Locations.php");
include("Tools.php");

$return = "false";

$admins = file_get_contents(adminFile);

//To delete an admin at a given ID
if (!empty($_GET['delete'])) {
	$return = deleteUser($_GET['delete']);
}

//To create a new admin when no ID is given
if (empty($_GET['edit']) &&
	empty($_GET['push']) && 
	empty($_GET['pop']) && (
		   !empty($_GET['email'])
		|| !empty($_GET['password'])
		|| !empty($_GET['firstName'])
		|| !empty($_GET['surname']))) {
	$return = createUser(
			!empty($_GET['email'])     ? arrayStrip($_GET['email']) : null,
			!empty($_GET['password'])  ? password_hash(arrayStrip($_GET['password']), PASSWORD_BCRYPT) : null,
			!empty($_GET['firstName']) ? arrayStrip($_GET['firstName']) : null,
			!empty($_GET['surname'])   ? arrayStrip($_GET['surname']) : null);
}

//To edit an existing admin at a given ID
else if (!empty($_GET['edit']) && (
		   !empty($_GET['email'])
		|| !empty($_GET['password'])
		|| !empty($_GET['firstName'])
		|| !empty($_GET['surname']))) {
	$return = editUser(
			arrayStrip($_GET['edit']),
			!empty($_GET['email'])     ? arrayStrip($_GET['email']) : null,
			!empty($_GET['password'])  ? password_hash(arrayStrip($_GET['password']), PASSWORD_BCRYPT) : null,
			!empty($_GET['firstName']) ? arrayStrip($_GET['firstName']) : null,
			!empty($_GET['surname'])   ? arrayStrip($_GET['surname']) : null);
}

//To return only specific admins at given IDs
else if (!empty($_GET['find'])) {
	$return = findUsers($_GET['find'],
			!empty($_GET['where']) ? $_GET['where'] : null);
}

else if (!empty($_GET['search'])) {
	$return = searchUsers(
			$_GET['search'],
			!empty($_GET['where']) ? $_GET['where'] : null);
}

//Return a value if needed
if (!empty($return))
	echo $return;

function deleteUser($id) {
	$_admins = json_decode($GLOBALS['admins']);
	
	$returnable = false;
	foreach($_admins as $i => $person) {
		if ($person->id == $id) {
			unset($_admins[$i]);
			$_admins = array_values($_admins);
			$returnable = $person;
		}
	}
	
	$GLOBALS['admins'] = json_encode($_admins);
	file_put_contents(adminFile, $GLOBALS['admins']);
	unset($returnable->password);
	return json_encode($returnable);
}

function createUser($email, $password, $firstName,
						 $surname) {
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

function editUser($id, $email, $password,
						$firstName, $surname) {
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