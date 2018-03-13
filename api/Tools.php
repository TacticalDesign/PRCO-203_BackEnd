<?php

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

function atLeastAll($values) {
	foreach ($values as $i => $value) {
		if (empty($_GET[$value]))
			return false;
	}
	return true;
}

function getArray($array) {
	if (empty($_GET[$array]))
		return array();
	else if (is_array($_GET[$array]))
		return $_GET[$array];
	else
		return array($_GET[$array]);
}

function getString($var) {
	if (empty($_GET[$var]))
		return null;
	else if (is_array($_GET[$var]))
		return array_values($_GET[$var])[0];
	else
		return $_GET[$var];
}

function getEncrypted($var) {
	if (empty($_GET[$var]))
		return null;
	else if (is_array($_GET[$var]))
		return password_hash(array_values($_GET[$var])[0], PASSWORD_BCRYPT);
	else
		return password_hash($_GET[$var], PASSWORD_BCRYPT);
}

function getInt($var) {
	if (empty($_GET[$var]))
		return null;
	else if (is_array($_GET[$var]))
		return (int)(array_values($_GET[$var])[0]);
	else
		return (int)$_GET[$var];
}

function getBool($var) {
	$falsey = array('', '0', 'false', 'False', 'FALSE');
	if (empty($_GET[$var]))
		return false;
	else if (is_array($_GET[$var]))
		return !in_array(array_values($_GET[$var])[0], $falsey);
	else
		return !in_array($_GET[$var], $falsey);
}

?>