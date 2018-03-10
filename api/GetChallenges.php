<?php

include("Locations.php");
include("Tools.php");

$return = "false";

$challenges = file_get_contents(currentChallengesFile);

//To delete a challenge at a given ID
if (!empty($_GET['delete'])) {
	$return = deleteChallenge($_GET['delete']);
}

//To create a new challenge when no ID is given 
if (empty($_GET['edit']) &&
	empty($_GET['push']) && 
	empty($_GET['pop']) && (
		   !empty($_GET['challenger'])
		|| !empty($_GET['adminApproved'])
		|| !empty($_GET['name'])
		|| !empty($_GET['skills'])
		|| !empty($_GET['description'])
		|| !empty($_GET['reward'])
		|| !empty($_GET['location1'])
		|| !empty($_GET['location2'])
		|| !empty($_GET['location3'])
		|| !empty($_GET['closingTime'])
		|| !empty($_GET['minAttendees'])
		|| !empty($_GET['maxAttendees'])
		|| !empty($_GET['attendees']))) {
	$return = createChallenge(
			!empty($_GET['challenger'])    ? arrayStrip($_GET['challenger']) : null,
			!empty($_GET['adminApproved']) ? arrayStrip($_GET['adminApproved']) : null,
			!empty($_GET['name'])          ? arrayStrip($_GET['name']) : null,
			!empty($_GET['skills'])        ? $_GET['skills'] : array(),
			!empty($_GET['description'])   ? arrayStrip($_GET['description']) : null,
			!empty($_GET['reward'])        ? arrayStrip($_GET['reward']) : null,
			!empty($_GET['location1'])     ? arrayStrip($_GET['location1']) : null,
			!empty($_GET['location2'])     ? arrayStrip($_GET['location2']) : null,
			!empty($_GET['location3'])     ? arrayStrip($_GET['location3']) : null,
			!empty($_GET['closingTime'])   ? arrayStrip($_GET['closingTime']) : null,
			!empty($_GET['minAttendees'])  ? arrayStrip($_GET['minAttendees']) : null,
			!empty($_GET['maxAttendees'])  ? arrayStrip($_GET['maxAttendees']) : null,
			!empty($_GET['attendees'])     ? $_GET['attendees'] : array());
}

//To edit an existing challenge at a given ID
else if (!empty($_GET['edit']) &&
		  empty($_GET['push']) && 
		  empty($_GET['pop']) && (
		   !empty($_GET['challenger'])
		|| !empty($_GET['adminApproved'])
		|| !empty($_GET['name'])
		|| !empty($_GET['skills'])
		|| !empty($_GET['description'])
		|| !empty($_GET['reward'])
		|| !empty($_GET['location1'])
		|| !empty($_GET['location2'])
		|| !empty($_GET['location3'])
		|| !empty($_GET['closingTime'])
		|| !empty($_GET['minAttendees'])
		|| !empty($_GET['maxAttendees'])
		|| !empty($_GET['attendees']))) {
	$return = editChallenge(
			arrayStrip($_GET['edit']),
			!empty($_GET['challenger'])    ? arrayStrip($_GET['challenger']) : null,
			!empty($_GET['adminApproved']) ? arrayStrip($_GET['adminApproved']) : null,
			!empty($_GET['name'])          ? arrayStrip($_GET['name']) : null,
			!empty($_GET['skills'])        ? $_GET['skills'] : null,
			!empty($_GET['description'])   ? arrayStrip($_GET['description']) : null,
			!empty($_GET['reward'])        ? arrayStrip($_GET['reward']) : null,
			!empty($_GET['location1'])     ? arrayStrip($_GET['location1']) : null,
			!empty($_GET['location2'])     ? arrayStrip($_GET['location2']) : null,
			!empty($_GET['location3'])     ? arrayStrip($_GET['location3']) : null,
			!empty($_GET['closingTime'])   ? arrayStrip($_GET['closingTime']) : null,
			!empty($_GET['minAttendees'])  ? arrayStrip($_GET['minAttendees']) : null,
			!empty($_GET['maxAttendees'])  ? arrayStrip($_GET['maxAttendees']) : null,
			!empty($_GET['attendees'])     ? $_GET['attendees'] : null);
}
			
//To push values to a challenges array contents
else if ( empty($_GET['edit']) &&
		 !empty($_GET['push']) && 
		  empty($_GET['pop']) &&(
			   !empty($_GET['skills'])
			|| !empty($_GET['attendees']))) {
	$return = pushChallenge(
			$_GET['push'],
			!empty($_GET['skills'])    ? $_GET['skills'] : array(),
			!empty($_GET['attendees']) ? $_GET['attendees'] : array());
}

//To pop values from a challenges array contents
else if ( empty($_GET['edit']) &&
		  empty($_GET['push']) && 
		 !empty($_GET['pop']) &&(
			   !empty($_GET['skills'])
			|| !empty($_GET['attendees']))) {
	$return = popChallenge(
			$_GET['pop'],
			!empty($_GET['skills'])    ? $_GET['skills'] : array(),
			!empty($_GET['attendees']) ? $_GET['attendees'] : array());
}

//To return only specific challenges at given IDs
else if (!empty($_GET['find'])) {
	$return = findChallenges($_GET['find'],
			!empty($_GET['where']) ? $_GET['where'] : null);
}

//To search for challenges with a search term
else if (!empty($_GET['search'])) {
	$return = searchChallenges(
			$_GET['search'],
			!empty($_GET['where']) ? $_GET['where'] : null);
}

//Return a value if needed
if (!empty($return))
	echo $return;

//Functions
//=========

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
	return json_encode($returnable);
}

function createChallenge($challenger, $adminApproved, $name,
						 $skills, $description, $reward,
						 $location1, $location2, $location3,
						 $closingTime, $minAttendees, $maxAttendees, $attendees) {
	$returnable = new stdClass();
	$returnable->id            = date("zyHis");
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
	$returnable->attendees  = $attendees;
		
	$_challenges = json_decode($GLOBALS['challenges']);
	array_push($_challenges, $returnable);
	$GLOBALS['challenges'] = json_encode($_challenges);
	file_put_contents(currentChallengesFile, $GLOBALS['challenges']);
	unset($returnable->password);
	return json_encode($returnable);
}

function editChallenge($id, $challenger, $adminApproved, $name,
						 $skills, $description, $reward,
						 $location1, $location2, $location3,
						 $closingTime, $minAttendees, $maxAttendees, $attendees) {
	$_challenges = json_decode($GLOBALS['challenges']);
	
	$returnable = false;
	foreach($_challenges as $i => $thing) {
		if ($thing->id == $id) {
			if ($challenger != null)
				$thing->challenger = $challenger;
			if ($adminApproved != null)
				$thing->adminApproved = $adminApproved;
			if ($name != null)
				$thing->name = $name;
			if ($skills != null)
				$thing->skills = $skills;
			if ($description != null)
				$thing->description = $description;
			if ($reward != null)
				$thing->reward = $reward;
			if ($location1 != null)
				$thing->location1 = $location1;
			if ($location2 != null)
				$thing->location2 = $location2;
			if ($location3 != null)
				$thing->location3 = $location3;
			if ($closingTime != null)
				$thing->closingTime = $closingTime;
			if ($minAttendees != null)
				$thing->minAttendees = $minAttendees;
			if ($maxAttendees != null)
				$thing->maxAttendees = $maxAttendees;
			
			$returnable = $thing;
		}
	}
	
	$GLOBALS['challenges'] = json_encode($_challenges);
	file_put_contents(currentChallengesFile, $GLOBALS['challenges']);
	unset($returnable->password);
	return json_encode($returnable);
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
	unset($returnable->password);
	return json_encode($returnable);
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
	unset($returnable->password);
	return json_encode($returnable);
}

function findChallenges($ids, $where) {
	
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
	$wantedItems = [];
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
			unset($challenge->password);
			array_push($wantedItems, $challenge);
		}
	}
	
	return json_encode($wantedItems);
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
					unset($challenge->password);
					array_push($matches, $challenge);
					array_push($matchedIDs, $challenge->id);
				}
			}
		}
	}
	
	return json_encode($matches);
}
























?>