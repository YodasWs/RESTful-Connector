<?php
// First, Ask for Permission
if (empty($_GET['code']) and !empty($_REQUEST['state'])) {
	// Services Using OAuth2.0
	if (in_array($_REQUEST['service'], array(
		'fb',
	))) {
		require_once('oauth2.php');
		OAuth2::getPermission($_REQUEST['service']);
		// If we're here, there was an error
		header('Location: /');
		exit;
	}
}

// Second, Now Login
if ($_REQUEST['state'] == $_SESSION['state'] and !empty($_GET['code'])) {
	// Services Using OAuth2.0
	if (in_array($_REQUEST['service'], array(
		'fb',
	))) {
		require_once('oauth2.php');
		OAuth2::login($_REQUEST['service']);
		// If we're here, there was an error
		header('Location: /');
		exit;
	}
	switch ($_REQUEST['service']) {
	case 'fb':
		require_once('facebook.php');
		break;
	}
}
?>
