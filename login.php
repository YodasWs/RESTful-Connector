<?php
session_start();
if (isset($_SERVER['HTTP_REFERER'])) {
	$_SESSION['prelogin'] = $_SERVER['HTTP_REFERER'];
	if (strstr($_SERVER['HTTP_REFERER'], '?')) {
		$referer = substr($_SERVER['HTTP_REFERER'], 0, strpos($_SERVER['HTTP_REFERER'], '?'));
	} else $referer = $_SERVER['HTTP_REFERER'];
	if ($_SERVER['SCRIPT_URL'] == $referer) {
		$_SESSION['prelogin'] = substr($_SERVER['HTTP_REFERER'], 0, strpos($_SERVER['HTTP_REFERER'], ':')) . "://{$_SERVER['HTTP_HOST']}";
	}
} else $_SESSION['prelogin'] = substr($_SERVER['SCRIPT_URI'], 0, strpos($_SERVER['SCRIPT_URI'], ':')) . "://{$_SERVER['HTTP_HOST']}";

// First, Ask for Permission
if (empty($_SESSION['user']['fb']) and empty($_SESSION['tokens']['fb']) and empty($_GET['code']) and !empty($_REQUEST['service'])) {
	// Services Using OAuth2.0
	if (in_array($_REQUEST['service'], array(
		'fb',
	))) {
		require_once('oauth2.php');
		OAuth2::getPermission($_REQUEST['service'], "/login?service={$_REQUEST['service']}");
		// If we're here, there was an error
		unset($_SESSION['state']);
		$_SESSION['error'] = array('Could not login');
		header('HTTP/1.1 403 Unauthorized');
		header("Location: {$_SESSION['prelogin']}");
		unset($_SESSION['prelogin']);
		exit;
	}
}

// Second, Now Login
if (empty($_SESSION['fbtoken']) and !empty($_REQUEST['state']) and !empty($_SESSION['state']) and $_REQUEST['state'] == $_SESSION['state'] and !empty($_GET['code'])) {
	// Services Using OAuth2.0
	if (in_array($_REQUEST['service'], array(
		'fb',
	))) {
		require_once('oauth2.php');
		OAuth2::login($_REQUEST['service']);
		// If we're here, there was an error
		$_SESSION['error'] = array('Could not login');
		header('HTTP/1.1 403 Unauthorized');
		header("Location: {$_SESSION['prelogin']}");
		unset($_SESSION['prelogin']);
		exit;
	}
}
switch ($_REQUEST['service']) {
case 'fb':
	require_once('facebook.php');
	break;
}
// Successfully Logged In !
header("Location: {$_SESSION['prelogin']}");
unset($_SESSION['prelogin']);
exit;
?>
