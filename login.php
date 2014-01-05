<?php

// First, Ask for Permission
if (empty($_GET['code']) and !empty($_GET['state'])) {
}

if (in_array($_REQUEST['service'], array(
	'fb',
))) {
	require_once('oauth2.php');
	OAuth2::getPermission($_REQUEST['service']);
	// If we're here, there was an error
	header('Location: /');
}

switch ($_REQUEST['service']) {
case 'fb':
	require_once('facebook.php');
	break;
}
?>
