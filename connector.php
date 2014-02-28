<?php
// Array of Supported Services
$apis = array(
	'fb' => array(
		'file' => 'facebook.php',
		'login' => '/icons/facebook.png',
		'class' => 'Facebook',
		'name' => 'Facebook',
		'scope' => array(
			// https://developers.facebook.com/docs/facebook-login/permissions/
			'read_stream',
			'user_likes',
			'publish_actions', // Warning: This is Optional
		),
		'color' => array(
			'#5371ae',
			'#90b3f2',
		),
	),
	'google' => array(
		'file' => 'google.php',
		'login' => '/icons/google.png',
		'class' => 'Google',
		'name' => 'Google+',
		'scope' => array(
			'https://www.googleapis.com/auth/plus.login',
		),
		'color' => array(
			'#de472f',
			'#ffe4e1',
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
