<?php

function arrayStrip($mixed) {
	if (is_array($mixed))
		return array_values($mixed)[0];
	else
		return $mixed;
}

function onlyKeyword($keyword, $keywords) {
	foreach	($keywords as $i => $key) {
		if ($key != $keyword && !empty($_GET[$key]))
			return false;
	}
	return !empty($_GET[$keyword]);
}

function atLeastOne($values) {
	foreach ($values as $i => $value) {
		if (!empty($_GET[$value]))
			return true;
	}
	return false;
}

?>