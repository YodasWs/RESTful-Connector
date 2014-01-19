<?php
// Array of Supported Services
$apis = array(
	'fb' => array(
		'file' => 'facebook.php',
		'login' => '/icons/fb-login.jpg',
		'class' => 'Facebook',
		'name' => 'Facebook',
	),
);
// Load Service Files
require_once('1feed.keys.php');
foreach ($apis as $api) {
	require_once($api['file']);
}

session_start();

if (!empty($_SESSION['user']) or !empty($_SESSION['tokens'])) {
	// Load Service Object and User Data
	foreach (array_keys($_SESSION['tokens']) as $api) {
		if (!empty($_SESSION['tokens'][$api]) and empty($_SESSION['user'][$api])) {
			$_SESSION['user'][$api] = array();
			if (empty($_SESSION[$api]) or get_class($_SESSION[$api]) != $apis[$api]['name'])
				$_SESSION[$api] = new $apis[$api]['name']();
			$_SESSION[$api]->loadUser();
		}
	}
}
