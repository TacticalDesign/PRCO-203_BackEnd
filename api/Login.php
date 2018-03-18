<?php

include_once('Locations.php');
include_once('GetToken.php');

$response = array();
$response['errors'] = array();

//Check the inputs
if (empty($_POST['email']))
	$response['errors'][] = "Request needs an email address";
if (empty($_POST['password']))
	$response['errors'][] = "Request needs a password";

//If no errors have been made so far
if (sizeof($response['errors']) === 0) {
	//Check each user-base for a matching account
	if (!checkUserBase(adminFile, 'admin'))
		if (!checkUserBase(challengerFile, 'challenger'))
			if (!checkUserBase(youngPeopleFile, 'youngPerson'))
				$response['errors'][] = "Account does not exist";
}

echo json_encode($response);


function checkUserBase($file, $accountType) {
	
	$users = json_decode(file_get_contents($file));
	
	foreach($users as $i => $user) {
		if ($user->email !== $_POST['email'])
			continue;
		
		if (password_verify($_POST['password'], $user->password))
			$GLOBALS['response']['token'] = GetToken($users[0]->id, $accountType);
		else
			$GLOBALS['response']['errors'][] = "Password is incorrect";
		
		return true;
	}
	return false;
}



























?>