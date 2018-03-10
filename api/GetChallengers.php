<?php

include("Locations.php");
include("Tools.php");

$keywords = array('new', 'edit', 'push', 'pop', 'delete', 'find', 'search');
$return = "false";

$challengers = file_get_contents(challengerFile);

//To create a new challenger with a given email
if (onlyKeyword('new', $keywords) &&
	atLeastOne(array('password', 'name', 'colour', 'contactEmail', 'contactPhone',
					 'about', 'currentChallenges', 'archivedChallenges'))) {
	$return = createUser(
			arrayStrip($_GET['new']),
			!empty($_GET['password'])           ? password_hash(arrayStrip($_GET['password']), PASSWORD_BCRYPT) : null,
			!empty($_GET['name'])               ? arrayStrip($_GET['name']) : null,
			!empty($_GET['colour'])             ? arrayStrip($_GET['colour']) : null,
			!empty($_GET['contactEmail'])       ? arrayStrip($_GET['contactEmail']) : null,
			!empty($_GET['contactPhone'])       ? arrayStrip($_GET['contactPhone']) : null,
			!empty($_GET['about'])              ? arrayStrip($_GET['about']) : null,
		    !empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : array(),
		    !empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : array());
}

//To edit an existing challenger with a given ID
else if (onlyKeyword('edit', $keywords) &&
		 atLeastOne(array('email', 'password', 'name', 'colour', 'contactEmail', 'contactPhone',
					 'about', 'currentChallenges', 'archivedChallenges'))) {
	$return = editUser(
			arrayStrip($_GET['edit']),
			!empty($_GET['email'])              ? arrayStrip($_GET['email'] : null,
			!empty($_GET['password'])           ? password_hash(arrayStrip($_GET['password']), PASSWORD_BCRYPT) : null,
			!empty($_GET['name'])               ? arrayStrip($_GET['name']) : null,
			!empty($_GET['colour'])             ? arrayStrip($_GET['colour']) : null,
			!empty($_GET['contactEmail'])       ? arrayStrip($_GET['contactEmail']) : null,
			!empty($_GET['contactPhone'])       ? arrayStrip($_GET['contactPhone']) : null,
			!empty($_GET['about'])              ? arrayStrip($_GET['about']) : null,
			!empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : null,
			!empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : null);
}
			
//To push values to a young person's array contents
else if (onlyKeyword('push', $keywords) &&
		 atLeastOne(array('currentChallenges', 'archivedChallenges'))) {
	$return = pushUser(
			arrayStrip($_GET['push']),
			!empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : array(),
			!empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : array());
}

//To pop values from a young person's array contents
else if (onlyKeyword('pop', $keywords) &&
		 atLeastOne(array('currentChallenges', 'archivedChallenges'))) {
	$return = popUser(
			arrayStrip($_GET['pop']),
			!empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : array(),
			!empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : array());
}

//To delete a challenger with a given ID
else if (onlyKeyword('delete', $keywords)) {
	$return = deleteUser($_GET['delete']);
}

//To return only specific challengers at given IDs
else if (onlyKeyword('find', $keywords)) {
	$return = findUsers($_GET['find'],
			!empty($_GET['where']) ? $_GET['where'] : null);
}

//To search for challengers with a query
else if (onlyKeyword('search', $keywords)) {
	$return = searchUsers(
			$_GET['search'],
			!empty($_GET['where']) ? $_GET['where'] : null);
}

//Return a value if needed
if (!empty($return))
	echo $return;

//Functions
//=========

function deleteUser($ids) {
	$wantedIDs = explode(',', $ids);
	$_challengers = json_decode($GLOBALS['challengers']);
	
	$keeps = array();
	$returnable = array();
	foreach($_challengers as $i => $person) {
		if (in_array($person->id, $wantedIDs) {
			unset($person->password);
			array_push($returnable, $person);
		} else 
			array_push($keeps, $person);
	}
	
	$GLOBALS['challengers'] = json_encode($keeps);
	file_put_contents(challengerFile, $GLOBALS['challengers']);
	return json_encode($returnable);
}

function createUser($email, $password, $name, $colour,
					$contactEmail, $contactPhone, $about,
					$currentChallenges, $archivedChallenges) {
	$returnable = new stdClass();
	$returnable->id                 = date("zyHis");
	$returnable->email              = $email;
	$returnable->password           = $password;
	$returnable->name               = $name;
	$returnable->image              = profileFolder . "/" . $returnable->id . ".png";
	$returnable->cover              = coverPhotoFolder . "/" . $returnable->id . ".png";
	$returnable->colour             = $colour;
	$returnable->contactEmail       = $contactEmail;
	$returnable->contactPhone       = $contactPhone;
	$returnable->about              = $about;
	$returnable->currentChallenges  = $currentChallenges;
	$returnable->archivedChallenges = $archivedChallenges;
	
	$_challengers = json_decode($GLOBALS['challengers']);
	array_push($_challengers, $returnable);
	$GLOBALS['challengers'] = json_encode($_challengers);
	file_put_contents(challengerFile, $GLOBALS['challengers']);
	return json_encode($returnable);
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