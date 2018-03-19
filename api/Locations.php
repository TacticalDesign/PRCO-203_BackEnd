<?php

define('tokenSecret', json_decode(file_get_contents("../../TokenSecret.json"))->secret);
define('godUser', json_decode(file_get_contents("../../TokenSecret.json"))->godUser);
define('godPassword', json_decode(file_get_contents("../../TokenSecret.json"))->godPassword);

define("dataFolder", "Data");

define("profileFolder", dataFolder . "/UserPhotos");

define("coverPhotoFolder", dataFolder . "/CoverPhotos");

define("emailsFolder", dataFolder . "/Emails");

define("adminFile", dataFolder . "/Admins.json");

define("challengerFile", dataFolder . "/Challengers.json");

define("youngPeopleFile", dataFolder . "/YoungPeople.json");

define("currentChallengesFile", dataFolder . "/CurrentChallenges.json");

define("newAccountEmail", emailsFolder . "/NewAccountEmail.html");

if (tokenSecret == false) {
	echo json_encode(array('errors' => array("There is no server-side token secret!")));
	die();
}

if (!file_exists(dataFolder)) {
    mkdir(dataFolder, 0600, true);
}

if (!file_exists(profileFolder)) {
    mkdir(profileFolder, 0600, true);
}

if (!file_exists(coverPhotoFolder)) {
    mkdir(coverPhotoFolder, 0600, true);
}

if (!file_exists(adminFile)){
	file_put_contents(adminFile, json_encode(Array()));
	chmod(adminFile, 0600);
}

if (!file_exists(challengerFile)){
	file_put_contents(challengerFile, json_encode(Array()));
	chmod(challengerFile, 0600);
}

if (!file_exists(youngPeopleFile)){
	file_put_contents(youngPeopleFile, json_encode(Array()));
	chmod(youngPeopleFile, 0600);
}

if (!file_exists(currentChallengesFile)){
	file_put_contents(currentChallengesFile, json_encode(Array()));
	chmod(currentChallengesFile, 0600);
}

?>