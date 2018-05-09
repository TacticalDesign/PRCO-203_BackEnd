<?php

function getReturnReady($returnable, $goDeeper) {
	//Get the retuanable content
	$data = $returnable['result'];
	
	//If the content is an array
	if (is_array($data)) {
		foreach	($data as $i => $obj) {
			//Make everything in the array return-ready
			$obj = getObjReturnReady($obj, $goDeeper);
		}
		$returnable['result'] = $data;
	}
	//Else if the object isn't an array
	else {
		//Make this one this array-ready - including putting it in an array
		$ready = getObjReturnReady($data, $goDeeper);
		$returnable['result'] = empty($ready) ? array() : array($ready);
	}
	
	return $returnable;
}

function getObjReturnReady($data, $goDeeper) {
	//Remove all sensitive data - note this doesn't change the actual
	//		saved object, just the returned data
	unset($data->password);
	unset($data->tempPassword);
	
	//If IDs should be replaced with objects one level deeper
	if ($goDeeper) {
		//Replace challenger IDs with challenger data
		if (!empty($data->challenger)) {
			$result = getChallenger($data->challenger);
			if (!empty($results)) {
				$data->challenger = getObjReturnReady($result, false);
			}
		}
		//Replace challenge IDs with challenge data
		if (!empty($data->currentChallenges)) {
			$results = getChallenges(implode(',', $data->currentChallenges));
			if (count($results) > 0) {
				foreach ($results as $i => $result) {
					$result = getObjReturnReady($result, false);
				}
				$data->currentChallenges = $results;
			}
		}
		//Replace young person IDs with young people data
		if (!empty($data->attendees)) {
			$results = getYoungPeople(implode(',', array_values($data->attendees)));
			if (count($results) > 0) {
				foreach ($results as $i => $result) {
					$result = getObjReturnReady($result, false);
				}
				$data->attendees = $results;
			}
		}
	}
	else {
		/*if (!empty($data->challenger) && is_array($data->challenger)) {
			$data->challenger = array_values($data->challenger);
		}
		if (!empty($data->currentChallenges)) {
			$data->currentChallenges = array_values($data->currentChallenges);
		}
		if (!empty($data->attendees)) {
			$data->attendees = array_values($data->attendees);
		}*/
		
		//Ensure all arrays are sequential
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$data->$key = array_values($value);
			}
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
	//If null was passed in return an empty string
	if (empty($var))
		return '';
	//If an array was passed in return the first index
	if (is_array($var)) {
		if (sizeof($var) > 0)
			return forceString($var[0]);
		else
			return '';
	}
	//If in object was passed in return it's first parameter
	if (is_object($var)) {
		return forceString((array)$var);
	}
	//Else cast the passed in value to a string
	else
		return '' . $var;
}

function forceInt($var) {
	//If null was passed in return zero
	if (empty($var))
		return 0;
	//If an array was passed in return the first index or 0 if it's length is 0
	if (is_array($var)) {
		if (sizeof($var) > 0)
			return forceInt($var[0]);
		else
			return 0;
	}
	//If in object was passed in return it's first parameter
	if (is_object($var)) {
		return forceInt((array)$var);
	}
	//Else cast the passed in value to a int
	else
		return (int) $var;
}

function forceBool($var) {
	$falsey = array('', '0', 'false', 'False', 'FALSE');
	//If null was passed in return false
	if (empty($var))
		return false;
	//If an array was passed in return the first index or false if it's length is 0
	if (is_array($var)) {
		if (sizeof($var) > 0)
			return forceBool($var[0]);
		else
			return false;
	}
	//If in object was passed in return it's first parameter
	if (is_object($var)) {
		return forceBool((array)$var);
	}
	else
	//Else cast the passed in value to a string and return if it's not included in the falsey array
		return !in_array((string)$var, $falsey);
}

function forceStringArray($var) {
	//If null was passed in return an empty array
	if (empty($var))
		return array();
	//If an object was passed in cast it to an array
	if (is_object($var)) {
		$var = (array) $var;
	}
	//Ensure all it's values are strings
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

function getAdmin($id) {
	//Get all current admin
	$allAdmins = json_decode(file_get_contents(adminFile), true);
	//Return the wanted index if it exists
	if (array_key_exists($id, $allAdmins))
		return (object) $allAdmins[$id];
	//Else return null
	return null;
}

function setAdmin($updated) {
	//Get all current admin
	$admins = json_decode(file_get_contents(adminFile), true);
	//Add in the to-be-set index
	$admins[$updated->id] = $updated;
	//Save all the admin
	file_put_contents(adminFile, json_encode($admins, JSON_PRETTY_PRINT));
}

function getChallenger($id) {
	//Get all the challengers
	$allChallengers = json_decode(file_get_contents(challengerFile), true);
	
	//If the wanted ID exists
	if (array_key_exists($id, $allChallengers)) {
		$challenger = (object) $allChallengers[$id];
		$change = false; 
		
		//Check none of it's current challengers are dead IDs
		foreach ($challenger->currentChallenges as $i => $challenge) {
			if (empty(getChallenge($i))) {
				unset($challenger->currentChallenges[$i]);
				$change = true;
			}
		}
		
		//Save it if it was changed
		if ($change)
			setChallenger($challenger);
		
		return $challenger;
	}
	return null;	
}

function setChallenger($updated) {
	//Get all current challengers
	$challengers = json_decode(file_get_contents(challengerFile), true);
	//Add in the to-be-set index
	$challengers[$updated->id] = $updated;
	//Save all the challengers
	file_put_contents(challengerFile, json_encode($challengers, JSON_PRETTY_PRINT));
}

function getYoungPerson($id) {
	//Get all of thr young people
	$allYoungPeople = json_decode(file_get_contents(youngPeopleFile), true);
	
	//If the wanted ID exists
	if (array_key_exists($id, $allYoungPeople)) {
		$youngPerson = (object) $allYoungPeople[$id];
		$change = false;
		
		//Check none of it's current challengers are dead IDs
		foreach ($youngPerson->currentChallenges as $i => $challenge) {
			if (empty(getChallenge($i))) {
				unset($youngPerson->currentChallenges[$i]);
				$change = true;
			}
		}
		
		//Save it if it was changed
		if ($change)
			setYoungPerson($youngPerson);
		
		return $youngPerson;
	}
	return null;
}

function getYoungPeople($stringIDs) {
	//Get all the young people
	$allYoungPeople = json_decode(file_get_contents(youngPeopleFile), true);
	$results = array();
	$ids = explode(',', $stringIDs);
	
	//Foreach young person...
	foreach	($ids as $i => $_youngPerson) {
		//... If their ID is in the array of wanted IDs
		if (array_key_exists($_youngPerson, $allYoungPeople)) {
			$youngPerson = (object) $allYoungPeople[$_youngPerson];
			$change = false;
			
			//Check none of it's current challengers are dead IDs
			foreach ($youngPerson->currentChallenges as $_youngPerson => $challenge) {
				if (empty(getChallenge($_youngPerson))) {
					unset($youngPerson->currentChallenges[$_youngPerson]);
					$change = true;
				}
			}
			
			//Save it if it was changed
			if ($change)
				setYoungPerson($youngPerson);
			
			$results[] = $youngPerson;
		}
	}
	return sizeof($results) === 0 ? null : $results;
	
}

function setYoungPerson($updated) {
	//Get all of the young people
	$youngPeople = json_decode(file_get_contents(youngPeopleFile), true);
	//Add in the to-be-set index
	$youngPeople[$updated->id] = $updated;
	//Save all the young people
	file_put_contents(youngPeopleFile, json_encode($youngPeople, JSON_PRETTY_PRINT));
}

function getChallenge($id) {
	//Get all the challenges
	$allChallenges = json_decode(file_get_contents(currentChallengesFile), true);
	//Return the wanted ID if it exists
	if (array_key_exists($id, $allChallenges))
		return (object) $allChallenges[$id];
	return null;
}

function getChallenges($stringIDs) {
	//Get all the challenges
	$allChallenges = json_decode(file_get_contents(currentChallengesFile), true);
	$results = array();
	$ids = explode(',', $stringIDs);
	
	//Foreach challenge...
	foreach	($ids as $i => $challenge) {
		//... If their ID is in the array of wanted IDs
		if (array_key_exists($challenge, $allChallenges))
			$results[] = (object) $allChallenges[$challenge];
	}
	return sizeof($results) === 0 ? null : $results;
}

function setChallenge($updated) {
	//Get all of the challenges
	$challenges = json_decode(file_get_contents(currentChallengesFile), true);
	//Add in the to-be-set index
	$challenges[$updated->id] = $updated;
	//Save all the challenges
	file_put_contents(currentChallengesFile, json_encode($challenges, JSON_PRETTY_PRINT));
}

function getReward($id) {
	//Get all the rewards
	$allRewards = json_decode(file_get_contents(rewardsFile), true);	
	//Return the wanted ID if it exists
	if (array_key_exists($id, $allRewards))
		return (object) $allRewards[$id];
	else
		return null;
}

function getRewards($stringIDs) {
	//Get all the rewards
	$allRewards = json_decode(file_get_contents(rewardsFile), true);
	$results = array();
	$ids = explode(',', $stringIDs);
	
	//Foreach reward...
	foreach	($ids as $i => $reward) {
		//... If their ID is in the array of wanted IDs
		if (array_key_exists($reward, $allRewards))
			$results[] = (object) $allRewards[$reward];
	}
	return sizeof($results) === 0 ? null : $results;
}

function setReward($updated) {
	//Get all of the rewards
	$rewards = json_decode(file_get_contents(rewardsFile), true);
	//Add in the to-be-set index
	$rewards[$updated->id] = $updated;
	//Save all the rewards
	file_put_contents(rewardsFile, json_encode($rewards, JSON_PRETTY_PRINT));
}
















?>