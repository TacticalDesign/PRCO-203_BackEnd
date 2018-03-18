<?php

include_once("Locations.php");
include_once("Tools.php");

$challenges = file_get_contents(currentChallengesFile);

if (str_replace('/', '\\', __FILE__) == str_replace('/', '\\', $_SERVER['SCRIPT_FILENAME'])) {

	include_once("CheckLoggedIn.php");
	
	$return = "false";
	$keywords = array('new', 'edit', 'push', 'pop', 'delete', 'find', 'search');

	//To create a new challenge with a given name
	if (onlyKeyword('new', $keywords)) {
		$return = createChallenge(
			getBool('frozen'),
			getString('challenger'),
			getBool('adminApproved'),
			getString('new'),
			getArray('skills'),
			getString('description'),
			getInt('reward'),
			getString('location1'),
			getString('location2'),
			getString('location3'),
			getString('closingTime'),
			getInt('minAttendees'),
			getInt('maxAttendees'),
			getArray('attendees')
		);
	}

	//To edit an existing challenge with a given ID
	else if (onlyKeyword('edit', $keywords) &&
			 atLeastOne(array('frozen', 'challenger', 'adminApproved', 'name', 'skills', 'description',
							  'reward', 'location1', 'location2', 'location3', 'closingTime',
							  'minAttendees', 'maxAttendees', 'attendees'))) {
		$return = editChallenge(
			getString('edit'),
			getBool('frozen'),
			getString('challenger'),
			getBool('adminApproved'),
			getString('name'),
			getArray('skills'),
			getString('description'),
			getInt('reward'),
			getString('location1'),
			getString('location2'),
			getString('location3'),
			getString('closingTime'),
			getInt('minAttendees'),
			getInt('maxAttendees'),
			getArray('attendees')
		);
	}
				
	//To push values to a challenges array contents
	else if (onlyKeyword('push', $keywords) &&
			 atLeastOne(array('skills', 'attendees'))) {
		$return = pushChallenge(
			getString('push'),
			getArray('skills'),
			getArray('attendees')
		);
	}

	//To pop values from a challenges array contents
	else if (onlyKeyword('pop', $keywords) &&
			 atLeastOne(array('skills', 'attendees'))) {
		$return = popChallenge(
			getString('pop'),
			getArray('skills'),
			getArray('attendees')
		);
	}

	//To delete a challenge with a given ID
	else if (onlyKeyword('delete', $keywords)) {
		$return = deleteChallenge(
			getString('delete')
		);
	}

	//To return only specific challenges with given IDs
	else if (onlyKeyword('find', $keywords)) {
		$return = findChallenges(
			getString('find'),
			getString('where')
		);
	}

	//To search for challenges with a query
	else if (onlyKeyword('search', $keywords)) {
		$return = searchChallenges(
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

function createChallenge($frozen, $challenger, $adminApproved, $name,
						 $skills, $description, $reward,
						 $location1, $location2, $location3,
						 $closingTime, $minAttendees, $maxAttendees, $attendees) {
	$returnable = new stdClass();
	$returnable->id            = date("zyHis");
	$returnable->frozen        = $frozen;
	$returnable->challenger    = $challenger;
	$returnable->adminApproved = $adminApproved;
	$returnable->name          = $name;
	$returnable->image         = profileFolder . "/" . $returnable->id . ".png";
	$returnable->skills        = $skills;
	$returnable->description   = $description;
	$returnable->reward        = $reward;
	$returnable->location1     = $location1;
	$returnable->location2     = $location2;
	$returnable->location3     = $location3;
	$returnable->closingTime   = $closingTime;
	$returnable->minAttendees  = $minAttendees;
	$returnable->maxAttendees  = $maxAttendees;
	$returnable->attendees     = $attendees;
		
	$_challenges = json_decode($GLOBALS['challenges']);
	array_push($_challenges, $returnable);
	$GLOBALS['challenges'] = json_encode($_challenges);
	file_put_contents(currentChallengesFile, $GLOBALS['challenges']);
	return $returnable;
}

function editChallenge($id, $frozen, $challenger, $adminApproved, $name,
						 $skills, $description, $reward,
						 $location1, $location2, $location3,
						 $closingTime, $minAttendees, $maxAttendees, $attendees) {
	$_challenges = json_decode($GLOBALS['challenges']);
	
	$returnable = false;
	foreach($_challenges as $i => $thing) {
		if ($thing->id == $id) {
			if ($frozen !== null)
				$thing->frozen = $frozen;
			if ($challenger !== null)
				$thing->challenger = $challenger;
			if ($adminApproved !== null)
				$thing->adminApproved = $adminApproved;
			if ($name !== null)
				$thing->name = $name;
			if ($skills !== null)
				$thing->skills = $skills;
			if ($description !== null)
				$thing->description = $description;
			if ($reward !== null)
				$thing->reward = $reward;
			if ($location1 !== null)
				$thing->location1 = $location1;
			if ($location2 !== null)
				$thing->location2 = $location2;
			if ($location3 !== null)
				$thing->location3 = $location3;
			if ($closingTime !== null)
				$thing->closingTime = $closingTime;
			if ($minAttendees !== null)
				$thing->minAttendees = $minAttendees;
			if ($maxAttendees !== null)
				$thing->maxAttendees = $maxAttendees;
			
			$returnable = $thing;
		}
	}
	
	$GLOBALS['challenges'] = json_encode($_challenges);
	file_put_contents(currentChallengesFile, $GLOBALS['challenges']);
	return $returnable;
}

function pushChallenge($id, $skills, $attendees) {
	$_challenges = json_decode($GLOBALS['challenges']);
	
	$returnable = false;
	foreach($_challenges as $i => $person) {
		if ($person->id == $id) {
			$person->skills = array_unique(array_merge($person->skills, $skills));
			$person->attendees = array_unique(array_merge($person->attendees, $attendees));
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['challenges'] = json_encode($_challenges);
	file_put_contents(currentChallengesFile, $GLOBALS['challenges']);
	return $returnable;
}

function popChallenge($id, $skills, $attendees) {
	$_challenges = json_decode($GLOBALS['challenges']);
	
	$returnable = false;
	foreach($_challenges as $i => $person) {
		if ($person->id == $id) {
			$person->skills = array_values(array_diff($person->skills, $skills));
			$person->attendees = array_values(array_diff($person->attendees, $attendees));
			
			$returnable = $person;
		}
	}
	
	$GLOBALS['challenges'] = json_encode($_challenges);
	file_put_contents(currentChallengesFile, $GLOBALS['challenges']);
	return $returnable;
}

function deleteChallenge($ids) {
	$wantedIDs = explode(',', $ids);
	$_challenges = json_decode($GLOBALS['challenges']);
	
	$keeps = array();
	$returnable = array();
	foreach($_challenges as $i => $thing) {
		if (in_array($thing->id, $wantedIDs))
			array_push($returnable, $thing);
		else 
			array_push($keeps, $thing);
	}
	
	$GLOBALS['challenges'] = json_encode($keeps);
	file_put_contents(currentChallengesFile, $GLOBALS['challenges']);
	return $returnable;
}

function findChallenges($ids, $where) {
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
	$wantedItems = array();
	$_challenges = json_decode($GLOBALS['challenges']);
	
	foreach($_challenges as $i => $challenge) {
		$skip = false;
		foreach	($params as $param) {
			if (is_bool($challenge->{$param[0]})) {
				if (json_decode($challenge->{$param[0]}) != json_decode($param[1])) {
					$skip = true;
					break;
				}
			} else {
				if ($challenge->{$param[0]} != $param[1]) {
					$skip = true;
					break;
				}
			}
		}
		
		if ($skip)
			continue;
		
		if ($ids == "all" || in_array($challenge->id, $wantedIDs)) {
			array_push($wantedItems, $challenge);
		}
	}
	
	return $wantedItems;
}

function searchChallenges($searchPhrase, $where) {
	$searchPhrase = strtolower($searchPhrase);
	$_challenges = json_decode($GLOBALS['challenges']);
	
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
			foreach ($_challenges as $ii => $challenge) {
				$skip = false;
				foreach	($params as $param) {
					if (is_bool($challenge->{$param[0]})) {
						if (json_decode($challenge->{$param[0]}) != json_decode($param[1])) {
							$skip = true;
							break;
						}
					} else {
						if ($challenge->{$param[0]} != $param[1]) {
							$skip = true;
							break;
						}
					}
				}
				
				if ($skip)
					continue;
				
				if ((strpos(strtolower($challenge->name), $term) !== false
							  || strpos(strtolower($challenge->name), $term) !== false
							  || strpos(strtolower(implode("|", $challenge->skills)), $term) !== false
							  || strpos(strtolower($challenge->description), $term) !== false
							  || strpos(strtolower($challenge->location1), $term) !== false
							  || strpos(strtolower($challenge->location2), $term) !== false
							  || strpos(strtolower($challenge->location3), $term) !== false)
							  && !in_array($challenge->id, $matchedIDs)) {
					array_push($matches, $challenge);
					array_push($matchedIDs, $challenge->id);
				}
			}
		}
	}
	
	return $matches;
}
























?>