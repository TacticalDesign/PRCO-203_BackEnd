<?php

define("dataFolder", "Data");

define("profileFolder", dataFolder . "/UserPhotos");

define("coverPhotoFolder", dataFolder . "/CoverPhotos");

define("adminFile", dataFolder . "/Admins.json");

define("challengerFile", dataFolder . "/Challengers.json");

define("youngPeopleFile", dataFolder . "/YoungPeople.json");

define("currentChallengesFile", dataFolder . "/CurrentChallenges.json");

touch(adminFile);
touch(challengerFile);
touch(youngPeopleFile);
touch(currentChallengesFile);

?>