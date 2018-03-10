<?php

function arrayStrip($mixed) {
	if (is_array($mixed))
		return array_values($mixed)[0];
	else
		return $mixed;
}

?>