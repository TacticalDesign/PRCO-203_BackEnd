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
	
	//Check the user is a challenger
	if (!isUserLevel('challenger')) {
		$response['errors'][] = 'You have to be a challenger to use this command';
	}
	
	//To get an existing challenge
	else if ($_SERVER['REQUEST_METHOD'] === 'GET') {		
		$response['result'] = getChallenge(forceString($_GET['id']));
	}

	//To create a new challenge
	else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$response['result'] = createChallenge();
	}

	//To edit an existing challenge
	else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
		$response['result'] = editChallenge();
	}
	
	//Return a value
	$response['count'] = is_array($response['result']) ? sizeof($response['result']) : 1;
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
	$returnable->skills        = forceStringArray(empty($_POST['skills']) ? '' : $_POST['name']);
	$returnable->description   = forceString(empty($_POST['description']) ? '' : $_POST['name']);
	$returnable->reward        = forceInt(empty($_POST['reward']) ? '' : $_POST['name']);
	$returnable->location1     = forceString(empty($_POST['$location1']) ? '' : $_POST['name']);
	$returnable->location2     = forceString(empty($_POST['$location2']) ? '' : $_POST['name']);
	$returnable->location3     = forceString(empty($_POST['$location3']) ? '' : $_POST['name']);
	$returnable->closingTime   = forceInt(empty($_POST['closingTime']) ? '' : $_POST['name']);
	$returnable->minAttendees  = forceInt(empty($_POST['$minAttendees']) ? '' : $_POST['name']);
	$returnable->maxAttendees  = forceInt(empty($_POST['$maxAttendees']) ? '' : $_POST['name']);
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
	$returnable = getChallenge($id);
	
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