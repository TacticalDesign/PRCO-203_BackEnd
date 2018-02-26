<?php

touch("CurrentChallenges.json");

$return = null;

$challenges = file_get_contents("CurrentChallenges.json");

//To delete a challenge at a given ID
if (!empty($_GET['delete'])) {
	$return = deleteChallenge($_GET['delete']);
}

//To create a new challenge when no ID is given
if (empty($_GET['edit']) && (
		   !empty($_GET['challenger'])
		|| !empty($_GET['adminApproved'])
		|| !empty($_GET['name'])
		|| !empty($_GET['image'])
		|| !empty($_GET['skills'])
		|| !empty($_GET['description'])
		|| !empty($_GET['reward'])
		|| !empty($_GET['location1'])
		|| !empty($_GET['location2'])
		|| !empty($_GET['location3'])
		|| !empty($_GET['closingTime']))) {
	$return = createChallenge(
			!empty($_GET['challenger'])    ? $_GET['challenger'] : null,
			!empty($_GET['adminApproved']) ? $_GET['adminApproved'] : null,
			!empty($_GET['name'])          ? $_GET['name'] : null,
			!empty($_GET['image'])         ? $_GET['image'] : null,
			!empty($_GET['skills']) 	   ? $_GET['skills'] : null,
			!empty($_GET['description'])   ? $_GET['description'] : null,
			!empty($_GET['reward'])        ? $_GET['reward'] : null,
			!empty($_GET['location1'])     ? $_GET['location1'] : null,
			!empty($_GET['location2'])     ? $_GET['location2'] : null,
			!empty($_GET['location3'])     ? $_GET['location3'] : null,
			!empty($_GET['closingTime'])   ? $_GET['closingTime'] : null);

//To edit an existing challenge at a given ID
} elseif (!empty($_GET['edit']) && (
		   !empty($_GET['challenger'])
		|| !empty($_GET['adminApproved'])
		|| !empty($_GET['name'])
		|| !empty($_GET['image'])
		|| !empty($_GET['skills'])
		|| !empty($_GET['description'])
		|| !empty($_GET['reward'])
		|| !empty($_GET['location1'])
		|| !empty($_GET['location2'])
		|| !empty($_GET['location3'])
		|| !empty($_GET['closingTime']))) {
	$return = editChallenge(
			!empty($_GET['edit'])          ? $_GET['edit'] : null,
			!empty($_GET['challenger'])    ? $_GET['challenger'] : null,
			!empty($_GET['adminApproved']) ? $_GET['adminApproved'] : null,
			!empty($_GET['name'])          ? $_GET['name'] : null,
			!empty($_GET['image'])         ? $_GET['image'] : null,
			!empty($_GET['skills']) 	   ? $_GET['skills'] : null,
			!empty($_GET['description'])   ? $_GET['description'] : null,
			!empty($_GET['reward'])        ? $_GET['reward'] : null,
			!empty($_GET['location1'])     ? $_GET['location1'] : null,
			!empty($_GET['location2'])     ? $_GET['location2'] : null,
			!empty($_GET['location3'])     ? $_GET['location3'] : null,
			!empty($_GET['closingTime'])   ? $_GET['closingTime'] : null);
}

//To return only specific items at given IDs
else if (!empty($_GET['find'])) {
	$return = findChallenges($_GET['find']);
}

else if (!empty($_GET['search'])) {
	$return = searchChallenges(
			$_GET['search'],
			!empty($_GET['where']) ? $_GET['where'] : null);
}

//Return a value if needed
if (!empty($return))
	echo $return;

function deleteChallenge($id) {
	$_challenges = json_decode($GLOBALS['challenges']);
	
	$success = false;
	foreach($_challenges as $i => $thing) {
		if ($thing->id == $id) {
			unset($_challenges[$i]);
			$_challenges = array_values($_challenges);
			$success = $thing;
		}
	}
	
	$GLOBALS['challenges'] = json_encode($_challenges);
	file_put_contents("CurrentChallenges.json", $GLOBALS['challenges']);
	return json_encode($success);
}

function createChallenge($challenger, $adminApproved, $name,
						 $image, $skills, $description,
						 $reward, $location1, $location2,
						 $location3, $closingTime) {
	$newItem = new stdClass();
	$newItem->id            = date("zyHis");
	$newItem->challenger    = $challenger;
	$newItem->adminApproved = $adminApproved;
	$newItem->name          = $name;
	$newItem->image         = $image;
	$newItem->skills        = empty($skills) ? array() : $skills;
	$newItem->description   = $description;
	$newItem->reward        = $reward;
	$newItem->location1     = $location1;
	$newItem->location2     = $location2;
	$newItem->location3     = $location3;
	$newItem->closingTime   = $closingTime;
	
	$_challenges = json_decode($GLOBALS['challenges']);
	array_push($_challenges, $newItem);
	$GLOBALS['challenges'] = json_encode($_challenges);
	file_put_contents("CurrentChallenges.json", $GLOBALS['challenges']);
	return $GLOBALS['challenges'];
}

function editChallenge($id, $challenger, $adminApproved, $name,
						 $image, $skills, $description,
						 $reward, $location1, $location2,
						 $location3, $closingTime) {
	$_challenges = json_decode($GLOBALS['challenges']);
	
	$returnable = false;
	foreach($_challenges as $i => $thing) {
		if ($thing->id == $id) {
			if ($challenger != null)
				$thing->challenger = $challenger;
			if ($adminApproved != null)
				$thing->adminApproved = $adminApproved;
			if ($image != null)
				$thing->image = $image;
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
			
			$returnable = $thing;
		}
	}
	
	$GLOBALS['challenges'] = json_encode($_challenges);
	file_put_contents("CurrentChallenges.json", $GLOBALS['challenges']);
	return json_encode($returnable);
}

function findChallenges($ids) {
	if ($ids == "all"){
		return $GLOBALS['challenges'];
	}
	
	$wantedIDs = explode(',', $ids);
	$wantedItems = [];
	$_challenges = json_decode($GLOBALS['challenges']);
	
	foreach($_challenges as $i => $thing) {
		if (in_array($thing->id, $wantedIDs)) {
			array_push($wantedItems, $thing);
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
					array_push($matches, $challenge);
					array_push($matchedIDs, $challenge->id);
				}
			}
		}
	}
	
	return json_encode($matches);
}


























?>