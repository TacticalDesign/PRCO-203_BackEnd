<?php

include_once('GetToken.php');

include_once('GetAdmins.php');
include_once('GetChallengers.php');
include_once('GetYoungPeople.php');

$response = array();
$response['errors'] = array();

//Check the inputs
if (empty($_POST['email']))
	$response['errors'][] = "Request needs an email address";
if (empty($_POST['password']))
	$response['errors'][] = "Request needs a password";

//If no errors have been made so far
if (sizeof($response['errors']) === 0) {
	//Check each user base for a matching account
	if (!checkUserBase(FindAdmin("all", "email:" . $_POST['email']), 'admin'))
		if (!checkUserBase(FindChallenger("all", "email:" . $_POST['email']), 'challenger'))
			if (!checkUserBase(FindYoungPerson("all", "email:" . $_POST['email']), 'youngPerson'))
				$response['errors'][] = "Email does not match";
}

echo json_encode($response);




function checkUserBase($users, $accountType) {	
	if (sizeof($users) === 1) {
		if (password_verify($_POST['password'], $users[0]->password))
			$GLOBALS['response']['token'] = GetToken($users[0]->id, $accountType);
		else
			$GLOBALS['response']['errors'][] = "Password is incorrect";
		return true;
	}
	return false;
}



























?>