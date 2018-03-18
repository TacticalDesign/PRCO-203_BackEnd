<?php

include_once("Locations.php");
include_once("Tools.php");
include_once("GetChallenges.php");

$youngPeople = file_get_contents(youngPeopleFile);

if (__FILE__ == str_replace('/', '\\', $_SERVER['SCRIPT_FILENAME'])) {

	include_once("CheckLoggedIn.php");
	
	$return = "false";
	$keywords = array('new', 'edit', 'push', 'pop', 'feedback', 'attend', 'delete', 'find', 'search');
	
	//To create a new young person with a given email
	if (onlyKeyword('new', $keywords)) {
		$return = createYoungPerson(
			getBool('frozen'),
			getString('new'),
			getEncrypted('password'),
			getString('firstName'),
			getString('surname'),
			getInt('balance'),
			getArray('skills'),
			getArray('interests'),
			getArray('currentChallenges'),
			getArray('archivedChallenges')
		);
	}

	//To edit an existing young person at a given ID
	else if (onlyKeyword('edit', $keywords) && 
			 atLeastOne(array('frozen', 'email', 'password', 'firstName', 'surname',
							  'balance', 'skills', 'interests', 'currentChallenges',
							  'archivedChallenges'))) {
		$return = editYoungPerson(
			getString('edit'),
			getBool('frozen'),
			getString('email'),
			getEncrypted('password'),
			getString('firstName'),
			getString('surname'),
			getInt('balance'),
			getArray('skills'),
			getArray('interests'),
			getArray('currentChallenges'),
			getArray('archivedChallenges')
		);
	}
				
	//To push values to a young person's array contents
	else if (onlyKeyword('push', $keywords) &&
			 atLeastOne(array('skills', 'interests', 'currentChallenges', 'archivedChallenges'))) {
		$return = pushYoungPerson(
			getString('push'),
			getArray('skills'),
			getArray('interests'),
			getArray('currentChallenges'),
			getArray('archivedChallenges')
		);
	}

	//To pop values from a young person's array contents
	else if (onlyKeyword('pop', $keywords) &&
			 atLeastOne(array('skills', 'interests', 'currentChallenges', 'archivedChallenges'))) {
		$return = popYoungPerson(
			getString('pop'),
			getArray('skills'),
			getArray('interests'),
			getArray('currentChallenges'),
			getArray('archivedChallenges')
		);
	}

	//To add a new feedback to a young person with a given ID
	else if (onlyKeyword('feedback', $keywords) &&
			 atLeastAll(array('challenge', 'rating'))) {
		$return = feedbackYoungPerson( 
			getString('feedback'),
			getString('challenge'),
			getInt('rating'),
			getString('comment')
		);
	}
	
	//To mark a young person with a given ID as attending a challenge
	else if (onlyKeyword('attend', $keywords) &&
			 atLeastAll(array('challenge', 'attending'))) {
		$return = attendYoungPerson(
			getString('attend'),
			getString('challenge'),
			getBool('attending')
		);
	}

	//To delete a young person with a given ID
	else if (onlyKeyword('delete', $keywords)) {
		$return = deleteYoungPerson(
			getString('delete')
		);
	}

	//To return only specific young people with given IDs
	else if (onlyKeyword('find', $keywords)) {
		$return = findYoungPerson(
			getString('find'),
			getString('where')
		);
	}

	//To search all young people for a query
	else if (onlyKeyword('search', $keywords)) {
		$return = searchYoungPerson(
			getString('search'),
			getString('where')
		);
	}

	//Return a value if needed
	if (!empty($return))
		echo json_encode(getReturnReady($return, true));
}

//Functions
//=========

function createYoungPerson($frozen, $email, $password, $firstName,
			               $surname, $balance, $skills, $interests,
			               $currentChallenges, $archivedChallenges) {
	$returnable = new stdClass();
	$returnable->id                 = date("zyHis");
	$returnable->frozen				= $frozen;
	$returnable->email              = $email;
	$returnable->password           = $password;
	$returnable->firstName          = $firstName;
	$returnable->surname            = $surname;
	$returnable->balance			= $balance;
	$returnable->image              = profileFolder . "/" . $returnable->id . ".png";
	$returnable->skills             = $skills;
	$returnable->interests          = $interests;
	$returnable->currentChallenges  = $currentChallenges;
	$returnable->archivedChallenges = $archivedChallenges;
	$returnable->feedbacks			= array();
	
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	array_push($_youngPeople, $returnable);
	$GLOBALS['youngPeople'] = json_encode($_youngPeople);
	file_put_contents(youngPeopleFile, $GLOBALS['youngPeople']);
	return $returnable;
}

function editYoungPerson($id, $frozen, $email, $password, $firstName,
				         $surname, $balance, $skills, $interests,
				         $currentChallenges, $archivedChallenges) {
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	
	$returnable = false;
	foreach($_youngPeople as $i => $person) {
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
			if ($balance !== null)
				$person->balance = $balance;
			if ($skills !== null)
				$person->skills = $skills;
			if ($interests !== null)
				$person->interests = $interests;
			if ($currentChallenges !== null)
				$person->currentChallenges = $currentChallenges;
			if ($archivedChallenges !== null)
				$person->archivedChallenges = $archivedChallenges;
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['youngPeople'] = json_encode($_youngPeople);
	file_put_contents(youngPeopleFile, $GLOBALS['youngPeople']);
	return $returnable;
}

function pushYoungPerson($id, $skills, $interests,
				         $currentChallenges, $archivedChallenges) {
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	
	$returnable = false;
	foreach($_youngPeople as $i => $person) {
		if ($person->id == $id) {
			$person->skills             = array_unique(array_merge($person->skills, $skills));
			$person->interests          = array_unique(array_merge($person->interests, $interests));
			$person->currentChallenges  = array_unique(array_merge($person->currentChallenges, $currentChallenges));
			$person->archivedChallenges = array_unique(array_merge($person->archivedChallenges, $archivedChallenges));
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['youngPeople'] = json_encode($_youngPeople);
	file_put_contents(youngPeopleFile, $GLOBALS['youngPeople']);
	return $returnable;
}

function popYoungPerson($id, $skills, $interests,
					    $currentChallenges, $archivedChallenges) {
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	
	$returnable = false;
	foreach($_youngPeople as $i => $person) {
		if ($person->id == $id) {
			$person->skills             = array_values(array_diff($person->skills, $skills));
			$person->interests          = array_values(array_diff($person->interests, $interests));
			$person->currentChallenges  = array_values(array_diff($person->currentChallenges, $currentChallenges));
			$person->archivedChallenges = array_values(array_diff($person->archivedChallenges, $archivedChallenges));
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['youngPeople'] = json_encode($_youngPeople);
	file_put_contents(youngPeopleFile, $GLOBALS['youngPeople']);
	return $returnable;
}

function feedbackYoungPerson($id, $challenge, $rating, $comment) {
	$feedback = new stdClass();
	$feedback->challenge = $challenge;
	$feedback->rating = $rating;
	$feedback->comment = $comment;
	
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	
	$returnable = false;
	foreach($_youngPeople as $i => $person) {
		if ($person->id == $id) {
			array_push($person->feedbacks, $feedback);
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['youngPeople'] = json_encode($_youngPeople);
	file_put_contents(youngPeopleFile, $GLOBALS['youngPeople']);
	return $returnable;
}

function attendYoungPerson($id, $challenge, $attending) {
	if ($attending) {
		pushYoungPerson($id, array(), array(), array($challenge), array());
		pushChallenge($challenge, array(), array($id));
	} else {
		popYoungPerson($id, array(), array(), array($challenge), array());
		popChallenge($challenge, array(), array($id));
	}
	
	return findYoungPerson($id, null);
}

function deleteYoungPerson($ids) {
	$wantedIDs = explode(',', $ids);
	$_youngPeople = json_decode($GLOBALS['youngPeople']);
	
	$keeps = array();
	$returnable = array();
	foreach($_youngPeople as $i => $person) {
		if (in_array($person->id, $wantedIDs)) {
			array_push($returnable, $person);
		} else 
			array_push($keeps, $person);
	}
	
	$GLOBALS['youngPeople'] = json_encode($keeps);
	file_put_contents(youngPeopleFile, $GLOBALS['youngPeople']);
	return $returnable;
}

function findYoungPerson($ids, $where) {
	
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
			array_push($wantedUsers, $person);
		}
	}
	
	return $wantedUsers;
}

function searchYoungPerson($searchPhrase, $where) {
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
	
	return $matches;
}























?>