<?php
// Array of Supported Services
$apis = array(
	'fb' => array(
		'file' => 'facebook.php',
		'login' => '/icons/fb-login.jpg',
		'class' => 'Facebook',
		'name' => 'Facebook',
	),
	'google' => array(
		'file' => 'google.php',
		'login' => 'https://ssl.gstatic.com/s2/oz/images/sprites/signinbutton-094c03c836f9f91d08b943a90778d34e.png',
		'class' => 'Google',
		'name' => 'Google+',
		'scope' => array(
			'https://www.googleapis.com/auth/plus.login',
		),
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
			if (empty($_SESSION[$api]) or get_class($_SESSION[$api]) != $apis[$api]['class'])
				$_SESSION[$api] = new $apis[$api]['class']();
			$_SESSION[$api]->loadUser();
		}
	}
}
