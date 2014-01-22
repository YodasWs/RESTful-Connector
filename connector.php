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

foreach ($apis as $key => $api) {
	// Load Service Object
	if (empty($_SESSION[$key]) or get_class($_SESSION[$key]) != $api['class'])
		$_SESSION[$key] = new $api['class']();
	// Load User Data
	// TODO: Also load if data is "old"
	if (!empty($_SESSION['tokens'][$key]) and empty($_SESSION['user'][$key])) {
		$_SESSION['user'][$key] = array();
		$_SESSION[$key]->getUser();
	}
}
