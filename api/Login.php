<?php

include_once('Locations.php');
include_once('GetToken.php');
include_once('GetAdmins.php');
include_once('GetChallengers.php');
include_once('GetYoungPeople.php');

$response = array();
$response['token'] = null;
$response['errors'] = array();

//Check the inputs
if (empty($_POST['email']))
	$response['errors'][] = "Request needs an email address";
if (empty($_POST['password']))
	$response['errors'][] = "Request needs a password";

//If no errors have been made so far
if (sizeof($response['errors']) === 0) {
	//Check for god account
	if ($_POST['email'] === godUser && $_POST['password'] === godPassword) {
		$GLOBALS['response']['token'] = GetToken("000", "god");
		echo json_encode($GLOBALS['response']);
		die();
	}
	
	//Check each user-base for a matching account
	if (!checkUserBase(adminFile, 'admin'))
		if (!checkUserBase(challengerFile, 'challenger'))
			if (!checkUserBase(youngPeopleFile, 'youngPerson'))
				$response['errors'][] = "Account does not exist";
}

echo json_encode($response);


function checkUserBase($file, $accountType) {
	
	$users = json_decode(file_get_contents($file));
	
	//For every user in the given file
	foreach($users as $i => $user) {
		//If the emails don't match, skip the current user
		if ($user->email !== $_POST['email'])
			continue;
		
		//Check if a valid tempPassword is being used
		if (!empty($_POST['tempPassword'])) {
			if (empty($user->tempPassword)) {
				$GLOBALS['response']['errors'][] = "Account is not using a temporary password";
				return true;
			}
			
			//If the account is using a tempPassword and it matches			
			if (password_verify($_POST['tempPassword'], $user->tempPassword)) {
				switch ($accountType) {
					case 'admin':
						//Read all admins
						$allUsers = json_decode(file_get_contents(adminFile));
						//Find the wanted one
						$foundUser = $allUsers->{$user->id};
						//Adjust the password settings
						$foundUser->password = password_hash($_POST['password'], PASSWORD_BCRYPT);
						unset($foundUser->tempPassword);
						//Re-add the wanted admin back in
						$allUsers->{$user->id} = $foundUser;
						file_put_contents(adminFile, json_encode($allUsers, JSON_PRETTY_PRINT));
						break;
					case 'challenger':
						//Read all challengers
						$allUsers = json_decode(file_get_contents(challengerFile));
						//Find the wanted one
						$foundUser = $allUsers->{$user->id};
						//Adjust the password settings
						$foundUser->password = password_hash($_POST['password'], PASSWORD_BCRYPT);
						unset($foundUser->tempPassword);
						//Re-add the wanted challenger back in
						$allUsers->{$user->id} = $foundUser;
						file_put_contents(challengerFile, json_encode($allUsers, JSON_PRETTY_PRINT));
						break;
					case 'youngPerson':
						//Read all young people
						$allUsers = json_decode(file_get_contents());
						//Find the wanted one
						$foundUser = $allUsers->{$user->id};
						//Adjust the password settings
						$foundUser->password = password_hash($_POST['password'], PASSWORD_BCRYPT);
						unset($foundUser->tempPassword);
						//Re-add the wanted young person back in
						$allUsers->{$user->id} = $foundUser;
						file_put_contents(youngPeopleFile, json_encode($allUsers, JSON_PRETTY_PRINT));
						break;
				}
				$GLOBALS['response']['token'] = GetToken($foundUser->id, $accountType);
				return true;
			}
			else{
				$GLOBALS['response']['errors'][] = "TempPassword is incorrect";
				return true;
			}
		}
		
		if (password_verify($_POST['password'], $user->password))
			$GLOBALS['response']['token'] = GetToken($user->id, $accountType);
		else
			$GLOBALS['response']['errors'][] = "Password is incorrect";
		
		return true;
	}
	return false;
}











?>