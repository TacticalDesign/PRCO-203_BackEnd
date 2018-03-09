<?php

define("dataFolder", "Data");

define("profileFolder", dataFolder . "/UserPhotos");

define("coverPhotoFolder", dataFolder . "/CoverPhotos");

define("adminFile", dataFolder . "/Admins.json");

define("challengerFile", dataFolder . "/Challengers.json");

define("youngPeopleFile", dataFolder . "/YoungPeople.json");

define("currentChallengesFile", dataFolder . "/CurrentChallenges.json");

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