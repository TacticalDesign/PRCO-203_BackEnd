<?php

include_once("GetYoungPeople.php");
include_once("GetChallengers.php");
include_once("GetChallenges.php");

function onlyKeyword($keyword, $keywords) {
	foreach	($keywords as $i => $key) {
		if ($key != $keyword && !empty($_GET[$key]))
			return false;
	}
	return !empty($_GET[$keyword]);
}

function atLeastOne($values) {
	foreach ($values as $i => $value) {
		if (!empty($_GET[$value]))
			return true;
	}
	return false;
}

function atLeastAll($values) {
	foreach ($values as $i => $value) {
		if (empty($_GET[$value]))
			return false;
	}
	return true;
}

//Get Types

function getArray($array) {
	if (empty($_GET[$array]))
		return array();
	else if (is_array($_GET[$array]))
		return $_GET[$array];
	else
		return array($_GET[$array]);
}

function getString($var) {
	if (empty($_GET[$var]))
		return null;
	else if (is_array($_GET[$var]))
		return array_values($_GET[$var])[0];
	else
		return $_GET[$var];
}

function getEncrypted($var) {
	if (empty($_GET[$var]))
		return null;
	else if (is_array($_GET[$var]))
		return password_hash(array_values($_GET[$var])[0], PASSWORD_BCRYPT);
	else
		return password_hash($_GET[$var], PASSWORD_BCRYPT);
}

function getInt($var) {
	if (empty($_GET[$var]))
		return null;
	else if (is_array($_GET[$var]))
		return (int)(array_values($_GET[$var])[0]);
	else
		return (int)$_GET[$var];
}

function getBool($var) {
	$falsey = array('', '0', 'false', 'False', 'FALSE');
	if (empty($_GET[$var]))
		return false;
	else if (is_array($_GET[$var])) {
		return !in_array(array_values($_GET[$var])[0], $falsey);
	} else {
		return !in_array($_GET[$var], $falsey);
	}
}

//End get Types

function getReturnReady($returnable, $goDeeper) {
	$data = $returnable['result'];
	if (is_array($data)) {
		foreach	($data as $i => $obj) {
			$obj = getObjReturnReady($obj, $goDeeper);
		}
		$returnable['result'] = $data;
	}
	else
		$returnable['result'] = getObjReturnReady($data, $goDeeper);
	
	return $returnable;
}

function getObjReturnReady($data, $goDeeper) {
	unset($data->password);
	unset($data->tempPassword);
	
	if ($goDeeper) {
		if (!empty($data->challenger)) {
			$results = findChallenger($data->challenger, null);
			if (count($results) > 0)
				$data->challenger = $results[0];
		}
		if (!empty($data->currentChallenges)) {
			$results = findChallenges(implode(',', $data->currentChallenges), null);
			if (count($results) > 0)
				$data->currentChallenges = $results;
		}
		if (!empty($data->attendees)) {
			$results = findYoungPerson(implode(',', $data->attendees), null);
			if (count($results) > 0)
				$data->attendees = $results;
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










?>