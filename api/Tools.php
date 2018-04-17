<?php

function getReturnReady($returnable, $goDeeper) {
	$data = $returnable['result'];
	if (is_array($data)) {
		foreach	($data as $i => $obj) {
			$obj = getObjReturnReady($obj, $goDeeper);
		}
		$returnable['result'] = $data;
	}
	else {
		$ready = getObjReturnReady($data, $goDeeper);
		$returnable['result'] = empty($ready) ? array() : array($ready);
	}
	
	return $returnable;
}

function getObjReturnReady($data, $goDeeper) {
	unset($data->password);
	unset($data->tempPassword);
	
	if ($goDeeper) {
		if (!empty($data->challenger)) {
			$result = getChallenger($data->challenger);
			if (count($results) > 0) {
				foreach ($results as $i => $result) {
					$result = getObjReturnReady($result, false);
				}
				$data->challenger = getObjReturnReady($result, false);
			}
		}
		if (!empty($data->currentChallenges)) {
			$results = getChallenges(implode(',', $data->currentChallenges));
			if (count($results) > 0) {
				foreach ($results as $i => $result) {
					$result = getObjReturnReady($result, false);
				}
				$data->currentChallenges = $results;
			}
		}
		if (!empty($data->attendees)) {
			$results = getYoungPeople(implode(',', $data->attendees));
			if (count($results) > 0) {
				foreach ($results as $i => $result) {
					$result = getObjReturnReady($result, false);
				}
				$data->attendees = $results;
			}
		}
	}
	else {
		if (!empty($data->challenger)) {
			$data->challenger = array_values($data->challenger);
		}
		if (!empty($data->currentChallenges)) {
			$data->currentChallenges = array_values($data->currentChallenges);
		}
		if (!empty($data->attendees)) {
			$data->attendees = array_values($data->attendees);
		}
	}
	
	return $data;
}

function isUserLevel($wantedLevel) {
	$data = apache_request_headers()['Authorization'];
	
	//Ensure the JWT is two words
	if (sizeof(explode(' ', $data)) === 2) {
		$token = explode(' ', $data)[1];
		
		//Ensure the JWT is in the header.payload.signature format
		if (sizeof(explode('.', $token)) === 3) {
			$tokenParts = explode('.', $token);
			
			$checkPayload = str_replace(['-', '_', ''], ['+', '/', '='], $tokenParts[1]);
			$userType = json_decode(base64_decode($checkPayload))->user_typ;
			
			return $wantedLevel === $userType;
		}
	}
	return false;
}

function getNewID() {
	usleep(1);
	return str_replace('.', '', '' . microtime(true));
}

//Get Types

function forceString($var) {
	if (empty($var))
		return '';
	if (is_array($var)) {
		if (sizeof($var) > 0)
			return forceString($var[0]);
		else
			return '';
	}
	if (is_object($var)) {
		return forceString((array)$var);
	}
	else
		return '' . $var;
}

function forceInt($var) {
	if (empty($var))
		return 0;
	if (is_array($var)) {
		if (sizeof($var) > 0)
			return forceInt($var[0]);
		else
			return 0;
	}
	if (is_object($var)) {
		return forceInt((array)$var);
	}
	else
		return (int) $var;
}

function forceBool($var) {
	$falsey = array('', '0', 'false', 'False', 'FALSE');
	if (empty($var))
		return false;
	if (is_array($var)) {
		if (sizeof($var) > 0)
			return forceBool($var[0]);
		else
			return false;
	}
	if (is_object($var)) {
		return forceBool((array)$var);
	}
	else
		return !in_array((string)$var, $falsey);
}

function forceStringArray($var) {
	if (empty($var))
		return array();
	if (is_object($var)) {
		$var = (array) $var;
	}
	foreach ($var as $thing)
		$thing = forceString($thing);
	return $var;
}

// Getters and Setters

function getCurrentUserID() {
	$data = apache_request_headers()['Authorization'];
	
	//Ensure the JWT is two words
	if (sizeof(explode(' ', $data)) === 2) {
		$token = explode(' ', $data)[1];
		
		//Ensure the JWT is in the header.payload.signature format
		if (sizeof(explode('.', $token)) === 3) {
			$tokenParts = explode('.', $token);
			
			$checkPayload = str_replace(['-', '_', ''], ['+', '/', '='], $tokenParts[1]);
			return json_decode(base64_decode($checkPayload))->user_id;
		}
	}
	return false;
}

function setAdmin($updated) {
	$youngPeople = json_decode(file_get_contents(adminFile), true);
	$youngPeople[$updated->id] = $updated;
	file_put_contents(adminFile, json_encode($youngPeople, JSON_PRETTY_PRINT));
}

function getAdmin($id) {
	$allAdmins = json_decode(file_get_contents(adminFile), true);
	if (array_key_exists($id, $allAdmins))
		return (object) $allAdmins[$id];
	else
		return null;
}

function setChallenger($updated) {
	$youngPeople = json_decode(file_get_contents(challengerFile), true);
	$youngPeople[$updated->id] = $updated;
	file_put_contents(challengerFile, json_encode($youngPeople, JSON_PRETTY_PRINT));
}

function getChallenger($id) {
	$allChallengers = json_decode(file_get_contents(challengerFile), true);
	if (array_key_exists($id, $allChallengers))
		return (object) $allChallengers[$id];
	else
		return null;	
}

function setYoungPerson($updated) {
	$youngPeople = json_decode(file_get_contents(youngPeopleFile), true);
	$youngPeople[$updated->id] = $updated;
	file_put_contents(youngPeopleFile, json_encode($youngPeople, JSON_PRETTY_PRINT));
}

function getYoungPerson($id) {
	$allYoungPeople = json_decode(file_get_contents(youngPeopleFile), true);
	if (array_key_exists($id, $allYoungPeople))
		return (object) $allYoungPeople[$id];
	else
		return null;
}

function getYoungPeople($stringIDs) {
	$allYoungPeople = json_decode(file_get_contents(youngPeopleFile), true);
	$results = array();
	$ids = explode(',', $stringIDs);
	foreach	($ids as $i => $youngPerson) {
		if (array_key_exists($youngPerson, $allYoungPeople))
			$results[] = (object) $allYoungPeople[$youngPerson];
	}
	return sizeof($results) === 0 ? null : $results;
	
}

function setChallenge($updated) {
	$challenges = json_decode(file_get_contents(currentChallengesFile), true);
	$challenges[$updated->id] = $updated;
	file_put_contents(currentChallengesFile, json_encode($challenges, JSON_PRETTY_PRINT));
}

function getChallenge($id) {
	$allChallenges = json_decode(file_get_contents(currentChallengesFile), true);
	if (array_key_exists($id, $allChallenges))
		return (object) $allChallenges[$id];
	else
		return null;
}

function getChallenges($stringIDs) {
	$allChallenges = json_decode(file_get_contents(currentChallengesFile), true);
	$results = array();
	$ids = explode(',', $stringIDs);
	foreach	($ids as $i => $challenge) {
		if (array_key_exists($challenge, $allChallenges))
			$results[] = (object) $allChallenges[$challenge];
	}
	return sizeof($results) === 0 ? null : $results;
}

function getReward($id) {
	$allRewards = json_decode(file_get_contents(rewardsFile), true);
	if (array_key_exists($id, $allRewards))
		return (object) $allRewards[$id];
	else
		return null;
}

function getRewards($stringIDs) {
	$allRewards = json_decode(file_get_contents(rewardsFile), true);
	$results = array();
	$ids = explode(',', $stringIDs);
	foreach	($ids as $i => $reward) {
		if (array_key_exists($reward, $allRewards))
			$results[] = (object) $allRewards[$reward];
	}
	return sizeof($results) === 0 ? null : $results;
}

function setReward($updated) {
	$rewards = json_decode(file_get_contents(rewardsFile), true);
	$rewards[$updated->id] = $updated;
	file_put_contents(rewardsFile, json_encode($rewards, JSON_PRETTY_PRINT));
}
























?>