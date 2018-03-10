<?php

include("Locations.php");

$return = "false";

$challengers = file_get_contents(challengerFile);

//To delete a challenger at a given ID
if (!empty($_GET['delete'])) {
	$return = deleteUser($_GET['delete']);
}

//To create a new challenger when no ID is given
if (empty($_GET['edit']) &&
	empty($_GET['push']) && 
	empty($_GET['pop']) && (
		   !empty($_GET['email'])
		|| !empty($_GET['password'])
		|| !empty($_GET['name'])
		|| !empty($_GET['colour'])
		|| !empty($_GET['contactEmail'])
		|| !empty($_GET['contactPhone'])
		|| !empty($_GET['about'])
		|| !empty($_GET['currentChallenges'])
		|| !empty($_GET['archivedChallenges']))) {
	$return = createUser(
			!empty($_GET['email'])              ? $_GET['email'] : null,
			!empty($_GET['password'])           ? password_hash($_GET['password'], PASSWORD_BCRYPT) : null,
			!empty($_GET['name'])               ? $_GET['name'] : null,
			!empty($_GET['colour'])             ? $_GET['colour'] : null,
			!empty($_GET['contactEmail'])       ? $_GET['contactEmail'] : null,
			!empty($_GET['contactPhone'])       ? $_GET['contactPhone'] : null,
			!empty($_GET['about'])              ? $_GET['about'] : null,
		    !empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : array(),
		    !empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : array());
}

//To edit an existing challenger at a given ID
else if (!empty($_GET['edit']) &&
		 empty($_GET['push']) && 
		 empty($_GET['pop']) && (
			   !empty($_GET['email'])
			|| !empty($_GET['password'])
			|| !empty($_GET['name'])
			|| !empty($_GET['colour'])
			|| !empty($_GET['contactEmail'])
			|| !empty($_GET['contactPhone'])
			|| !empty($_GET['about'])
			|| !empty($_GET['currentChallenges'])
			|| !empty($_GET['archivedChallenges']))) {
	$return = editUser(
			$_GET['edit'],
			!empty($_GET['email'])              ? $_GET['email'] : null,
			!empty($_GET['password'])           ? password_hash($_GET['password'], PASSWORD_BCRYPT) : null,
			!empty($_GET['name'])               ? $_GET['name'] : null,
			!empty($_GET['colour'])             ? $_GET['colour'] : null,
			!empty($_GET['contactEmail'])       ? $_GET['contactEmail'] : null,
			!empty($_GET['contactPhone'])       ? $_GET['contactPhone'] : null,
			!empty($_GET['about'])              ? $_GET['about'] : null,
			!empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : null,
			!empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : null);
}
			
//To push values to a young person's array contents
else if ( empty($_GET['edit']) &&
		 !empty($_GET['push']) && 
		  empty($_GET['pop']) &&(
			   !empty($_GET['currentChallenges'])
			|| !empty($_GET['archivedChallenges']))) {
	$return = pushUser(
			$_GET['push'],
			!empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : array(),
			!empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : array());
}

//To pop values from a young person's array contents
else if ( empty($_GET['edit']) &&
		  empty($_GET['push']) && 
		 !empty($_GET['pop']) &&(
			   !empty($_GET['currentChallenges'])
			|| !empty($_GET['archivedChallenges']))) {
	$return = popUser(
			$_GET['pop'],
			!empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : array(),
			!empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : array());
}

//To return only specific challengers at given IDs
else if (!empty($_GET['find'])) {
	$return = findUsers($_GET['find'],
			!empty($_GET['where']) ? $_GET['where'] : null);
}

//To search for challengers with a search term
else if (!empty($_GET['search'])) {
	$return = searchUsers(
			$_GET['search'],
			!empty($_GET['where']) ? $_GET['where'] : null);
}

//Return a value if needed
if (!empty($return))
	echo $return;

//Functions
//=========

function deleteUser($id) {
	$_challengers = json_decode($GLOBALS['challengers']);
	
	$success = false;
	foreach($_challengers as $i => $person) {
		if ($person->id == $id) {
			unset($_challengers[$i]);
			$_challengers = array_values($_challengers);
			$success = $person;
		}
	}
	
	$GLOBALS['challengers'] = json_encode($_challengers);
	file_put_contents(challengerFile, $GLOBALS['challengers']);
	return json_encode($success);
}

function createUser($email, $password, $name, $colour,
					$contactEmail, $contactPhone, $about,
					$currentChallenges, $archivedChallenges) {
	$newItem = new stdClass();
	$newItem->id                 = date("zyHis");
	$newItem->email              = $email;
	$newItem->password           = $password;
	$newItem->name               = $name;
	$newItem->image              = profileFolder . "/" . $newItem->id . ".png";
	$newItem->cover              = coverPhotoFolder . "/" . $newItem->id . ".png";
	$newItem->colour             = $colour;
	$newItem->contactEmail       = $contactEmail;
	$newItem->contactPhone       = $contactPhone;
	$newItem->about              = $about;
	$newItem->currentChallenges  = $currentChallenges;
	$newItem->archivedChallenges = $archivedChallenges;
	
	$_challengers = json_decode($GLOBALS['challengers']);
	array_push($_challengers, $newItem);
	$GLOBALS['challengers'] = json_encode($_challengers);
	file_put_contents(challengerFile, $GLOBALS['challengers']);
	return $GLOBALS['challengers'];
}

function editUser($id, $email, $password, $name, $colour,
					$contactEmail, $contactPhone, $about,
					$currentChallenges, $archivedChallenges) {
	$_challengers = json_decode($GLOBALS['challengers']);
	
	$returnable = false;
	foreach($_challengers as $i => $person) {
		if ($person->id == $id) {
			if ($email != null)
				$person->email = $email;
			if ($password != null)
				$person->password = $password;
			if ($name != null)
				$person->name = $name;
			if ($colour != null)
				$person->colour = $colour;
			if ($contactEmail != null)
				$person->contactEmail = $contactEmail;
			if ($contactPhone != null)
				$person->contactPhone = $contactPhone;
			if ($about != null)
				$person->about = $about;
			if ($currentChallenges != null)
				$person->currentChallenges = $currentChallenges;
			if ($archivedChallenges != null)
				$person->archivedChallenges = $archivedChallenges;
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['challengers'] = json_encode($_challengers);
	file_put_contents(challengerFile, $GLOBALS['challengers']);
	return json_encode($returnable);
}

function pushUser($id, $currentChallenges, $archivedChallenges) {
	$_challengers = json_decode($GLOBALS['challengers']);
	
	$returnable = false;
	foreach($_challengers as $i => $person) {
		if ($person->id == $id) {
			$person->currentChallenges = array_unique(array_merge($person->currentChallenges, $currentChallenges));
			$person->archivedChallenges = array_unique(array_merge($person->archivedChallenges, $archivedChallenges));
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['challengers'] = json_encode($_challengers);
	file_put_contents(challengerFile, $GLOBALS['challengers']);
	return json_encode($returnable);
}

function popUser($id, $currentChallenges, $archivedChallenges) {
	$_challengers = json_decode($GLOBALS['challengers']);
	
	$returnable = false;
	foreach($_challengers as $i => $person) {
		if ($person->id == $id) {
			$person->currentChallenges = array_values(array_diff($person->currentChallenges, $currentChallenges));
			$person->archivedChallenges = array_values(array_diff($person->archivedChallenges, $archivedChallenges));
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['challengers'] = json_encode($_challengers);
	file_put_contents(challengerFile, $GLOBALS['challengers']);
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
		return $GLOBALS['challengers'];
	}
	
	$wantedIDs = explode(',', $ids);
	$wantedUsers = [];
	$_challengers = json_decode($GLOBALS['challengers']);
	
	foreach($_challengers as $i => $person) {
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
	$_challengers = json_decode($GLOBALS['challengers']);
	
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
			foreach ($_challengers as $ii => $person) {
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
	
	return json_encode($matches);
}
























?>