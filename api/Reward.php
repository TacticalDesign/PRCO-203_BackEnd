<?php

include_once("Locations.php");
include_once("Tools.php");

if (str_replace('/', '\\', __FILE__) == str_replace('/', '\\', $_SERVER['SCRIPT_FILENAME'])) {
	//Create a response
	$response = array();
	$response['result'] = null;
	$response['count'] = 0;
	$response['errors'] = array();
	
	//Check the user has valid login details
	include_once('CheckLoggedIn.php');	
	
	//To get an existing reward
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		if (empty($_GET['id'])) {
			$response['result'] = null;
		}
		else {
			$response['result'] = getReward(forceString($_GET['id']));
			if (empty($response['result']))
				$response['errors'][] = "$id is not a valid challenge ID";
		}
	}

	//To create a new reward
	else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		//Check the user is an admin
		if (!isUserLevel('admin')) {
			$response['errors'][] = 'You have to be an admin to use this command';
		}
		else
			$response['result'] = createReward();
	}

	//To edit an existing reward
	else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
		//Check the user is an admin
		if (!isUserLevel('admin')) {
			$response['errors'][] = 'You have to be an admin to use this command';
		}
		else
			$response['result'] = editReward();
	}
	
	//To freeze/defrost a reward
	else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
		parse_str(file_get_contents('php://input'), $patchVars);
		
		//Detect possible errors
		if ($patchVars['action'] !== 'freeze' && $patchVars['action'] !== 'defrost')
			$response['errors'][] = "$patchVars[action] is not a correct action";
		
		$reward = getReward(forceString($patchVars['id']));
		
		if (empty($reward))
			$response['errors'][] = "$patchVars[id] is not a valid reward";
		
		if ($patchVars['action'] === 'freeze')
			$response['result'] = freezeReward(forceString($patchVars['id']));
		else if ($patchVars['action'] === 'defrost')
			$response['result'] = defrostReward(forceString($patchVars['id']));
	}

	//To delete a reward with a given ID
	else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
		$response['result'] = deleteReward($_GET['id']);
	}
	
	//Return a value
	$response['count'] = is_array($response['result']) ? sizeof($response['result']) : 1;
	echo json_encode(getReturnReady($response, true));
}


//Functions
//=========

function createReward() {
	//Create a new Reward
	$returnable = new stdClass();
	$returnable->id          = getNewID();
	$returnable->frozen      = false;
	$returnable->name        = forceString(empty($_POST['name']) ? '' : $_POST['name']);
	$returnable->image       = profileFolder . "/" . $returnable->id . ".png";
	$returnable->description = forceString(empty($_POST['description']) ? '' : $_POST['description']);
	$returnable->cost        = forceInt(empty($_POST['cost']) ? '' : $_POST['cost']);
	
	//Save and return the reward
	setReward($returnable);
	return $returnable;
}

function editReward() {
	parse_str(file_get_contents('php://input'), $putVars);
	
	//Detect possible errors
	$validKeys = array('id', 'name', 'description', 'cost');
	foreach (array_diff(array_keys($putVars), $validKeys) as $i => $wrongProp) {
		$GLOBALS['response']['errors'][] = "$wrongProp is not a valid property of a reward";
	}
	
	if (sizeof(array_intersect(array_keys($putVars), $validKeys)) === 0)
		$GLOBALS['response']['errors'][] = 'No valid properties of a reward were given';
	
	//Get the reward
	$returnable = getReward($putVars['id']);
	
	if (empty($returnable)) {
		$GLOBALS['response']['errors'][] = "$putVars[id] is not a valid reward ID";
		return null;
	}	
	
	//Edit the reward
	if ($putVars['name'] !== null)
		$returnable->name = forceString($putVars['name']);
	if ($description !== null)
		$returnable->description = forceString($putVars['description']);
	if ($cost !== null)
		$returnable->cost = forceInt($putVars['cost']);
	
	//Save and return the reward
	setReward($returnable);
	return $returnable;
}

function freezeReward($id) {	
	//Find and update the reward
	$returnable = getReward($id);
	$returnable->frozen = true;
	
	//Save the reward
	setReward($returnable);
	return $returnable;	
}

function defrostReward($id) {	
	//Find and update the reward
	$returnable = getReward($id);
	$returnable->frozen = false;
	
	//Save the reward
	setReward($returnable);
	return $returnable;
}

function deleteReward($id) {
	//Get and delete the rewards
	$rewards = json_decode(file_get_contents(rewardsFile), true);
	$returnable = $rewards[$id];
	unset($rewards[$id]);
	
	//Save and return the young person
	file_put_contents(rewardsFile, json_encode($rewards));
	return $returnable;
}



















?>