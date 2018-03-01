<?php

touch("YoungPeople.json");

$return = "false";

$youngPeople = file_get_contents("YoungPeople.json");

//To delete a user at a given ID
if (!empty($_GET['delete'])) {
	$return = deleteUser($_GET['delete']);
}

//To create a new user when no ID is given
if (empty($_GET['edit']) && (
		   !empty($_GET['email'])
		|| !empty($_GET['password'])
		|| !empty($_GET['firstName'])
		|| !empty($_GET['surname'])
		|| !empty($_GET['image'])
		|| !empty($_GET['skills'])
		|| !empty($_GET['interests'])
		|| !empty($_GET['feedback']))) {
	$return = createUser(
			!empty($_GET['email'])     ? $_GET['email'] : null,
			!empty($_GET['password'])  ? $_GET['password'] : null,
			!empty($_GET['firstName']) ? $_GET['firstName'] : null,
			!empty($_GET['surname'])   ? $_GET['surname'] : null,
			!empty($_GET['image'])   ? $_GET['image'] : null,
			!empty($_GET['skills'])    ? $_GET['skills'] : null,
			!empty($_GET['interests']) ? $_GET['interests'] : null,
			!empty($_GET['feedback'])  ? $_GET['feedback'] : null);

//To edit an existing challenge at a given ID
} elseif (!empty($_GET['edit']) && (
		   !empty($_GET['email'])
		|| !empty($_GET['password'])
		|| !empty($_GET['firstName'])
		|| !empty($_GET['surname'])
		|| !empty($_GET['image'])
		|| !empty($_GET['skills'])
		|| !empty($_GET['interests'])
		|| !empty($_GET['feedback']))) {
	$return = editUser(
			!empty($_GET['edit'])      ? $_GET['edit'] : null,
			!empty($_GET['email'])     ? $_GET['email'] : null,
			!empty($_GET['password'])  ? $_GET['password'] : null,
			!empty($_GET['firstName']) ? $_GET['firstName'] : null,
			!empty($_GET['surname'])   ? $_GET['surname'] : null,
			!empty($_GET['image'])    ? $_GET['image'] : null,
			!empty($_GET['skills'])    ? $_GET['skills'] : null,
			!empty($_GET['interests']) ? $_GET['interests'] : null,
			!empty($_GET['feedback'])  ? $_GET['feedback'] : null);
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
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	
	$success = false;
	foreach($_youngPeople as $i => $person) {
		if ($person->id == $id) {
			unset($_youngPeople[$i]);
			$_youngPeople = array_values($_youngPeople);
			$success = $person;
		}
	}
	
	//if ($success == false)
		//Do the above for challengers
	
	//if ($success == false)
		//Do the above for admins	
	
	$GLOBALS['youngPeople'] = json_encode($_youngPeople);
	file_put_contents("YoungPeople.json", $GLOBALS['youngPeople']);
	return json_encode($success);
}

function createUser($email, $password, $firstName,
						 $surname, $image, $skills,
						 $interests, $feedbacks) {
	$newItem = new stdClass();
	$newItem->id        = date("zyHis");
	$newItem->email     = $email;
	$newItem->password  = $password;
	$newItem->firstName = $firstName;
	$newItem->surname   = $surname;
	$newItem->image     = $image;
	$newItem->skills    = $skills;
	$newItem->interests = $interests;
	$newItem->feedbacks = $feedbacks;
	
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	array_push($_youngPeople, $newItem);
	$GLOBALS['youngPeople'] = json_encode($_youngPeople);
	file_put_contents("YoungPeople.json", $GLOBALS['youngPeople']);
	return $GLOBALS['youngPeople'];
}

function editUser($id, $email, $password,
						$firstName, $surname, $image,
						$skills, $interests, $feedbacks) {
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
			if ($image != null)
				$person->image = $image;
			if ($skills != null)
				$person->skills = $skills;
			if ($interests != null)
				$person->interests = $interests;
			if ($feedbacks != null)
				$person->feedbacks = $feedbacks;
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['youngPeople'] = json_encode($_youngPeople);
	file_put_contents("YoungPeople.json", $GLOBALS['youngPeople']);
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
		return $GLOBALS['youngPeople'];
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
		
		if ($ids == "all" || in_array($person->id, $wantedIDs))
			array_push($wantedUsers, $person);
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
					array_push($matches, $person);
					array_push($matchedIDs, $person->id);
				}
			}
		}
	}
	
	return json_encode($matches);
}
























?>