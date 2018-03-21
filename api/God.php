<?php


function createAdmin($email, $firstName) {
	//Check the user is an god
	if (!isUserLevel('god')) {
		$GLOBALS['response']['errors'][] = "You have to be a god account to use this command";
		return null;
	}
	
	//Check the given email is valid
	$email = filter_var($email, FILTER_SANITIZE_EMAIL);
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$GLOBALS['response']['errors'][] = "$email is not a valid email address";
		return null;
	}
	
	//Generate a new temporary password 
	$tempPassword = bin2hex(openssl_random_pseudo_bytes(4));
	
	//Create and send an email with login details
	$subject = "Welcome to the Dead Pencil's App!";
	$props = array(
		'{$email}' => $email,
		'{$tempPassword}' => $tempPassword,
		'{$name}' => $firstName
	);
	$message = strtr(file_get_contents(newAccountEmail), $props);
	$headers  = "From: NoReply@realideas.org;" . "\r\n";
	$headers .= "MIME-Version: 1.0;" . "\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
	
	if(!mail($email, $subject, $message, $headers)) {
		echo "Unable to email new address";
		/////////////////////////////////////////////////
		// MAJOR DEBUG CODE - PASSWORDS BEING LEAKED
		if(in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1','::1')))
			echo $tempPassword;
		/////////////////////////////////////////////////
		else 
			die();
	}
	else
		echo "Sent Email";
	
	$returnable = new stdClass();
	$returnable->id           = date("zyHis");
	$returnable->frozen       = false;
	$returnable->email        = $email;
	$returnable->password     = null;
	$returnable->tempPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
	$returnable->firstName    = $firstName;
	$returnable->surname      = null;
	$returnable->image        = profileFolder . "/" . $returnable->id . ".png";
	
	updateAdmin($returnable);
	return $returnable;
}
	
?>