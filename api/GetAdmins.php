<?php

include("Locations.php");

$return = "false";

$admins = file_get_contents(adminFile);

//To delete a user at a given ID
if (!empty($_GET['delete'])) {
	$return = deleteUser($_GET['delete']);
}

//To create a new user when no ID is given
if (empty($_GET['edit']) && (
		   !empty($_GET['email'])
		|| !empty($_GET['password'])
		|| !empty($_GET['firstName'])
		|| !empty($_GET['surname']))) {
	$return = createUser(
			!empty($_GET['email'])     ? $_GET['email'] : null,
			!empty($_GET['password'])  ? $_GET['password'] : null,
			!empty($_GET['firstName']) ? $_GET['firstName'] : null,
			!empty($_GET['surname'])   ? $_GET['surname'] : null);

//To edit an existing challenge at a given ID
} elseif (!empty($_GET['edit']) && (
		   !empty($_GET['email'])
		|| !empty($_GET['password'])
		|| !empty($_GET['firstName'])
		|| !empty($_GET['surname']))) {
	$return = editUser(
			!empty($_GET['edit'])      ? $_GET['edit'] : null,
			!empty($_GET['email'])     ? $_GET['email'] : null,
			!empty($_GET['password'])  ? $_GET['password'] : null,
			!empty($_GET['firstName']) ? $_GET['firstName'] : null,
			!empty($_GET['surname'])   ? $_GET['surname'] : null);
}

//To return only specific users at given IDs
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
	
	$success = false;
	foreach($_admins as $i => $person) {
		if ($person->id == $id) {
			unset($_admins[$i]);
			$_admins = array_values($_admins);
			$success = $person;
		}
	}
	
	$GLOBALS['admins'] = json_encode($_admins);
	file_put_contents(adminFile, $GLOBALS['admins']);
	return json_encode($success);
}

function createUser($email, $password, $firstName,
						 $surname) {
	$newItem = new stdClass();
	$newItem->id        = date("zyHis");
	$newItem->email     = $email;
	$newItem->password  = $password;
	$newItem->firstName = $firstName;
	$newItem->surname   = $surname;
	$newItem->image     = profileFolder . "/" . $newItem->id . ".png";
	
	$_admins = json_decode($GLOBALS['admins']);
	array_push($_admins, $newItem);
	$GLOBALS['admins'] = json_encode($_admins);
	file_put_contents(adminFile, $GLOBALS['admins']);
	return $GLOBALS['admins'];
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
	} else if ($ids == "all") {
		return $GLOBALS['admins'];
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
		
		if ($ids == "all" || in_array($person->id, $wantedIDs))
			array_push($wantedUsers, $person);
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
					array_push($matches, $person);
					array_push($matchedIDs, $person->id);
				}
			}
		}
	}
	
	return json_encode($matches);
}
























?>