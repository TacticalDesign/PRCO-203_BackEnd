<?php

touch("Challengers.json");

$return = "false";

$challengers = file_get_contents("Challengers.json");

//To delete a user at a given ID
if (!empty($_GET['delete'])) {
	$return = deleteUser($_GET['delete']);
}

//To create a new user when no ID is given
if (empty($_GET['edit']) && (
		   !empty($_GET['email'])
		|| !empty($_GET['password'])
		|| !empty($_GET['name'])
		|| !empty($_GET['image'])
		|| !empty($_GET['cover'])
		|| !empty($_GET['colour'])
		|| !empty($_GET['contactEmail'])
		|| !empty($_GET['contactPhone'])
		|| !empty($_GET['about'])
		|| !empty($_GET['currentChallenges'])
		|| !empty($_GET['archivedChallenges']))) {
	$return = createUser(
			!empty($_GET['email'])              ? $_GET['email'] : null,
			!empty($_GET['password'])           ? $_GET['password'] : null,
			!empty($_GET['name'])               ? $_GET['name'] : null,
			!empty($_GET['image'])              ? $_GET['image'] : null,
			!empty($_GET['cover'])              ? $_GET['cover'] : null,
			!empty($_GET['colour'])             ? $_GET['colour'] : null,
			!empty($_GET['contactEmail'])       ? $_GET['contactEmail'] : null,
			!empty($_GET['contactPhone'])       ? $_GET['contactPhone'] : null,
			!empty($_GET['about'])              ? $_GET['about'] : null,
			!empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : null,
			!empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : null);

//To edit an existing challenger at a given ID
} elseif (!empty($_GET['edit']) && (
		   !empty($_GET['email'])
		|| !empty($_GET['password'])
		|| !empty($_GET['name'])
		|| !empty($_GET['image'])
		|| !empty($_GET['cover'])
		|| !empty($_GET['colour'])
		|| !empty($_GET['contactEmail'])
		|| !empty($_GET['contactPhone'])
		|| !empty($_GET['about'])
		|| !empty($_GET['currentChallenges'])
		|| !empty($_GET['archivedChallenges']))) {
	$return = editUser(
			!empty($_GET['email'])              ? $_GET['email'] : null,
			!empty($_GET['password'])           ? $_GET['password'] : null,
			!empty($_GET['name'])               ? $_GET['name'] : null,
			!empty($_GET['image'])              ? $_GET['image'] : null,
			!empty($_GET['cover'])              ? $_GET['cover'] : null,
			!empty($_GET['colour'])             ? $_GET['colour'] : null,
			!empty($_GET['contactEmail'])       ? $_GET['contactEmail'] : null,
			!empty($_GET['contactPhone'])       ? $_GET['contactPhone'] : null,
			!empty($_GET['about'])              ? $_GET['about'] : null,
			!empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : null,
			!empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : null);
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
	file_put_contents("Challengers.json", $GLOBALS['challengers']);
	return json_encode($success);
}

function createUser($email, $password, $name,
					$image, $cover, $colour,
					$contactEmail, $contactPhone, $about,
					$currentChallenges, $archivedChallenges) {
	$newItem = new stdClass();
	$newItem->id                 = date("zyHis");
	$newItem->email              = $email;
	$newItem->password           = $password;
	$newItem->name               = $name;
	$newItem->image              = $image;
	$newItem->cover              = $cover;
	$newItem->colour             = $colour;
	$newItem->contactEmail       = $contactEmail;
	$newItem->contactPhone       = $contactPhone;
	$newItem->about              = $about;
	$newItem->currentChallenges  = $currentChallenges;
	$newItem->archivedChallenges = $archivedChallenges;
	
	$_challengers = json_decode($GLOBALS['challengers']);
	array_push($_challengers, $newItem);
	$GLOBALS['challengers'] = json_encode($_challengers);
	file_put_contents("Challengers.json", $GLOBALS['challengers']);
	return $GLOBALS['challengers'];
}

function editUser($id, $email, $password, $name,
					$image, $cover, $colour,
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
			if ($image != null)
				$person->image = $image;
			if ($cover != null)
				$person->cover = $cover;
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
	file_put_contents("Challengers.json", $GLOBALS['challengers']);
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