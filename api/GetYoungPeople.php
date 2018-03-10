<?php

include("Locations.php");
include("Tools.php");

$return = "false";

$youngPeople = file_get_contents(youngPeopleFile);

//To delete a user at a given ID
if (!empty($_GET['delete'])) {
	$return = deleteUser($_GET['delete']);
}

//To create a new user when no ID is given
if (empty($_GET['edit']) &&
	empty($_GET['push']) && 
	empty($_GET['pop']) && (
		   !empty($_GET['email'])
		|| !empty($_GET['password'])
		|| !empty($_GET['firstName'])
		|| !empty($_GET['surname'])
		|| !empty($_GET['skills'])
		|| !empty($_GET['interests'])
		|| !empty($_GET['currentChallenges'])
		|| !empty($_GET['archivedChallenges']))) {
	$return = createUser(
		   !empty($_GET['email'])              ? arrayStrip($_GET['email']) : null,
		   !empty($_GET['password'])           ? password_hash(arrayStrip($_GET['password']), PASSWORD_BCRYPT) : null,
		   !empty($_GET['firstName'])          ? arrayStrip($_GET['firstName']) : null,
		   !empty($_GET['surname'])            ? arrayStrip($_GET['surname']) : null,
		   !empty($_GET['skills'])             ? $_GET['skills'] : array(),
		   !empty($_GET['interests'])          ? $_GET['interests'] : array(),
		   !empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : array(),
		   !empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : array());
}

//To edit an existing young person at a given ID
else if (!empty($_GET['edit']) &&
		  empty($_GET['push']) && 
		  empty($_GET['pop']) && (
			   !empty($_GET['email'])
			|| !empty($_GET['password'])
			|| !empty($_GET['firstName'])
			|| !empty($_GET['surname'])
			|| !empty($_GET['skills'])
			|| !empty($_GET['interests'])
			|| !empty($_GET['currentChallenges'])
			|| !empty($_GET['archivedChallenges']))) {
	$return = editUser(
			arrayStrip($_GET['edit']),
			!empty($_GET['email'])              ? arrayStrip($_GET['email']) : null,
			!empty($_GET['password'])           ? password_hash(arrayStrip($_GET['password']), PASSWORD_BCRYPT) : null,
			!empty($_GET['firstName'])          ? arrayStrip($_GET['firstName']) : null,
			!empty($_GET['surname'])            ? arrayStrip($_GET['surname']) : null,
			!empty($_GET['skills'])             ? $_GET['skills'] : null,
			!empty($_GET['interests'])          ? $_GET['interests'] : null,
			!empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : null,
			!empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : null);
}
			
//To push values to a young person's array contents
else if ( empty($_GET['edit']) &&
		 !empty($_GET['push']) && 
		  empty($_GET['pop']) &&(
			   !empty($_GET['skills'])
			|| !empty($_GET['interests'])
			|| !empty($_GET['currentChallenges'])
			|| !empty($_GET['archivedChallenges']))) {
	$return = pushUser(
			$_GET['push'],
			!empty($_GET['skills'])             ? $_GET['skills'] : array(),
			!empty($_GET['interests'])          ? $_GET['interests'] : array(),
			!empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : array(),
			!empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : array());
}

//To pop values from a young person's array contents
else if ( empty($_GET['edit']) &&
		  empty($_GET['push']) && 
		 !empty($_GET['pop']) &&(
			   !empty($_GET['skills'])
			|| !empty($_GET['interests'])
			|| !empty($_GET['currentChallenges'])
			|| !empty($_GET['archivedChallenges']))) {
	$return = popUser(
			$_GET['pop'],
			!empty($_GET['skills'])             ? $_GET['skills'] : array(),
			!empty($_GET['interests'])          ? $_GET['interests'] : array(),
			!empty($_GET['currentChallenges'])  ? $_GET['currentChallenges'] : array(),
			!empty($_GET['archivedChallenges']) ? $_GET['archivedChallenges'] : array());
}

//To return only specific young people at given IDs
else if (!empty($_GET['find'])) {
	$return = findUsers($_GET['find'],
			!empty($_GET['where']) ? $_GET['where'] : null);
}

//To search all young people for a query
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

function deleteUser($ids) {
	$wantedIDs = explode(',', $ids);
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	
	$keeps = array();
	$returnable = array();
	foreach($_youngPeople as $i => $person) {
		if (in_array($person->id, $wantedIDs)) {
			unset($person->password);
			array_push($returnable, $person);
		} else 
			array_push($keeps, $person);
	}
	
	$GLOBALS['youngPeople'] = json_encode($keeps);
	file_put_contents(youngPeopleFile, $GLOBALS['youngPeople']);
	return json_encode($returnable);
}

function createUser($email, $password, $firstName,
					$surname, $skills, $interests,
					$currentChallenges, $archivedChallenges) {
	$returnable = new stdClass();
	$returnable->id                 = date("zyHis");
	$returnable->email              = $email;
	$returnable->password           = $password;
	$returnable->firstName          = $firstName;
	$returnable->surname            = $surname;
	$returnable->image              = profileFolder . "/" . $returnable->id . ".png";
	$returnable->skills             = $skills;
	$returnable->interests          = $interests;
	$returnable->currentChallenges  = $currentChallenges;
	$returnable->archivedChallenges = $archivedChallenges;
	
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	array_push($_youngPeople, $returnable);
	$GLOBALS['youngPeople'] = json_encode($_youngPeople);
	file_put_contents(youngPeopleFile, $GLOBALS['youngPeople']);
	unset($returnable->password);
	return json_encode($returnable);
}

function editUser($id, $email, $password, $firstName,
					$surname, $skills, $interests,
					$currentChallenges, $archivedChallenges) {
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	
	$returnable = false;
	foreach($_youngPeople as $i => $person) {
		if ($person->id == $id) {
			if ($email != null)
				$person->email = $email;
			if ($password != null)
				$person->password = $password;
			if ($firstName != null)
				$person->firstName = $firstName;
			if ($surname != null)
				$person->surname = $surname;
			if ($skills != null)
				$person->skills = $skills;
			if ($interests != null)
				$person->interests = $interests;
			if ($currentChallenges != null)
				$person->currentChallenges = $currentChallenges;
			if ($archivedChallenges != null)
				$person->archivedChallenges = $archivedChallenges;
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['youngPeople'] = json_encode($_youngPeople);
	file_put_contents(youngPeopleFile, $GLOBALS['youngPeople']);
	unset($returnable->password);
	return json_encode($returnable);
}

function pushUser($id, $skills, $interests,
					$currentChallenges, $archivedChallenges) {
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	
	$returnable = false;
	foreach($_youngPeople as $i => $person) {
		if ($person->id == $id) {
			$person->skills    = array_unique(array_merge($person->skills, $skills));
			$person->interests = array_unique(array_merge($person->interests, $interests));
			$person->currentChallenges = array_unique(array_merge($person->currentChallenges, $currentChallenges));
			$person->archivedChallenges = array_unique(array_merge($person->archivedChallenges, $archivedChallenges));
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['youngPeople'] = json_encode($_youngPeople);
	file_put_contents(youngPeopleFile, $GLOBALS['youngPeople']);
	unset($returnable->password);
	return json_encode($returnable);
}

function popUser($id, $skills, $interests,
					$currentChallenges, $archivedChallenges) {
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	
	$returnable = false;
	foreach($_youngPeople as $i => $person) {
		if ($person->id == $id) {
			$person->skills    = array_values(array_diff($person->skills, $skills));
			$person->interests = array_values(array_diff($person->interests, $interests));
			$person->currentChallenges = array_values(array_diff($person->currentChallenges, $currentChallenges));
			$person->archivedChallenges = array_values(array_diff($person->archivedChallenges, $archivedChallenges));
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['youngPeople'] = json_encode($_youngPeople);
	file_put_contents(youngPeopleFile, $GLOBALS['youngPeople']);
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
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	
	foreach($_youngPeople as $i => $person) {
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
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	
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
			foreach ($_youngPeople as $ii => $person) {
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