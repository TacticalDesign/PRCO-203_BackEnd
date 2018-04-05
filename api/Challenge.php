<?php

include_once('Locations.php');
include_once('Tools.php');

if (str_replace('/', '\\', __FILE__) == str_replace('/', '\\', $_SERVER['SCRIPT_FILENAME'])) {
	//Create the response
	$response = array();
	$response['result'] = null;
	$response['count'] = 0;
	$response['errors'] = array();
	
	//Check the user has valid login details
	include_once('CheckLoggedIn.php');
	
	//To get an existing challenge
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		if (empty($_GET['id'])) {
			$response['result'] = null;
		}
		else {
			$response['result'] = getChallenge(forceString($_GET['id']));
			if (empty($response['result']))
				$response['errors'][] = "$id is not a valid challenge ID";
		}
	}
	

	//To create a new challenge
	else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		//Check the user is a challenger
		if (!isUserLevel('challenger')) {
			$response['errors'][] = 'You have to be a challenger to use this command';
		}
		else
			$response['result'] = createChallenge();
	}

	//To edit an existing challenge
	else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
		//Check the user is a challenger
		if (!isUserLevel('challenger')) {
			$response['errors'][] = 'You have to be a challenger to use this command';
		}
		else
			$response['result'] = editChallenge();
	}
	
	//Return a value
	$response['count'] = empty($response['result']) ? 0 : 
		(is_array($response['result']) ? sizeof($response['result']) : 1);
	echo json_encode(getReturnReady($response, true));
}

//Functions
//=========
	
function createChallenge() {
	$returnable = new stdClass();
	$returnable->id            = getNewID();
	$returnable->frozen        = false;
	$returnable->challenger    = getCurrentuserID();
	$returnable->adminApproved = false;
	$returnable->name          = forceString(empty($_POST['name']) ? '' : $_POST['name']);
	$returnable->image         = profileFolder . '/' . $returnable->id . '.png';
	$returnable->skills        = forceStringArray(empty($_POST['skills']) ? '' : $_POST['skills']);
	$returnable->description   = forceString(empty($_POST['description']) ? '' : $_POST['description']);
	$returnable->reward        = forceInt(empty($_POST['reward']) ? '' : $_POST['reward']);
	$returnable->location1     = forceString(empty($_POST['$location1']) ? '' : $_POST['location1']);
	$returnable->location2     = forceString(empty($_POST['$location2']) ? '' : $_POST['location2']);
	$returnable->location3     = forceString(empty($_POST['$location3']) ? '' : $_POST['location3']);
	$returnable->closingTime   = forceInt(empty($_POST['closingTime']) ? '' : $_POST['closingTime']);
	$returnable->minAttendees  = forceInt(empty($_POST['$minAttendees']) ? '' : $_POST['minAttendees']);
	$returnable->maxAttendees  = forceInt(empty($_POST['$maxAttendees']) ? '' : $_POST['maxAttendees']);
	$returnable->attendees     = array();
	
	setChallenge($returnable);
	return $returnable;
}

function editChallenge() {
	parse_str(file_get_contents('php://input'), $putVars);
	
	//Detect possible errors
	$validKeys = array('id', 'name', 'skills', 'description', 'reward',
					   'location1', 'location2', 'location3',
					   'closingTime', 'minAttendees', 'maxAttendees');
	foreach (array_diff(array_keys($putVars), $validKeys) as $i => $wrongProp) {
		$GLOBALS['response']['errors'][] = "$wrongProp is not a valid property of a challenge";
	}
	
	if (sizeof(array_intersect(array_keys($putVars), $validKeys)) === 0)
		$GLOBALS['response']['errors'][] = 'No valid properties of a challenge were given';
	
	//Get the challenge
	$returnable = getChallenge($putVars['id']);
	
	if (empty($returnable)) {
		$GLOBALS['response']['errors'][] = "$putVars[id] is not a valid challenge ID";
		return null;
	}
	
	//Edit the challenge
	if (!empty($putVars['name']))
		$returnable->name = forceString($putVars['name']);
	if (!empty($putVars['skills']))
		$returnable->skills = forceStringArray($putVars['skills']);
	if (!empty($putVars['description']))
		$returnable->description = forceString($putVars['description']);
	if (!empty($putVars['reward']))
		$returnable->reward = forceInt($putVars['reward']);
	if (!empty($putVars['location1']))
		$returnable->location1 = forceString($putVars['location1']);
	if (!empty($putVars['location2']))
		$returnable->location2 = forceString($putVars['location2']);
	if (!empty($putVars['location3']))
		$returnable->location3 = forceString($putVars['location3']);
	if (!empty($putVars['closingTime']))
		$returnable->closingTime = forceString($putVars['closingTime']);
	if (!empty($putVars['minAttendees']))
		$returnable->minAttendees = forceString($putVars['minAttendees']);
	if (!empty($putVars['maxAttendees']))
		$returnable->maxAttendees = forceString($putVars['maxAttendees']);
	
	setChallenge($returnable);
	return $returnable;
}





























?>