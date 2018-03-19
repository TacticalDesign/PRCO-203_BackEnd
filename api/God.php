<?php
	include_once("Locations.php");
	include_once("Tools.php");

	$admins = file_get_contents(adminFile);
	
	$email = 'tobysmith568@hotmail.co.uk';
	$tempPassword = bin2hex(openssl_random_pseudo_bytes(4));
	$firstName = "Toby";
	
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
		die();
	}
	else {
		echo "Sent Email";
	}
	
	$returnable = new stdClass();
	$returnable->id           = date("zyHis");
	$returnable->frozen       = false;
	$returnable->email        = $email;
	$returnable->password     = null;
	$returnable->tempPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
	$returnable->firstName    = $firstName;
	$returnable->surname      = null;
	$returnable->image        = profileFolder . "/" . $returnable->id . ".png";
	
	$admins = json_decode($admins);
	array_push($admins, $returnable);
	file_put_contents(adminFile, json_encode($admins));
?>