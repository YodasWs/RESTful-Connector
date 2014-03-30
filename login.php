<?php
require_once('connector.php');
if (isset($_SERVER['HTTP_REFERER'])) {
	$_SESSION['prelogin'] = $_SERVER['HTTP_REFERER'];
	if (strstr($_SERVER['HTTP_REFERER'], '?')) {
		$referer = substr($_SERVER['HTTP_REFERER'], 0, strpos($_SERVER['HTTP_REFERER'], '?'));
	} else $referer = $_SERVER['HTTP_REFERER'];
	if ($_SERVER['SCRIPT_URL'] == $referer) {
		$_SESSION['prelogin'] = substr($_SERVER['HTTP_REFERER'], 0, strpos($_SERVER['HTTP_REFERER'], ':')) . "://{$_SERVER['HTTP_HOST']}";
	}
} else $_SESSION['prelogin'] = substr($_SERVER['SCRIPT_URI'], 0, strpos($_SERVER['SCRIPT_URI'], ':')) . "://{$_SERVER['HTTP_HOST']}";
if (strpos($_SESSION['prelogin'], ':') === 0) $_SESSION['prelogin'] = "http{$_SESSION['prelogin']}";

// First, Ask for Permission
if (empty($_GET['code']) and !empty($_REQUEST['service']) and in_array($_REQUEST['service'], array_keys($apis))) {
	if (empty($_SESSION['tokens'][$_REQUEST['service']])) {
		// APIs Using OAuth2.0
		if (in_array($_REQUEST['service'], array(
			'fb','google',
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
}

// Decode Double-encoded URLs
if (in_array($_REQUEST['service'], array(
	'google',
)) and !empty($_REQUEST['state'])) {
	$_REQUEST['state'] = urldecode($_REQUEST['state']);
}

// Second, Now Login
if (!empty($_GET['code']) and !empty($_REQUEST['service']) and in_array($_REQUEST['service'], array_keys($apis)) and !empty($_SESSION['state']) and $_REQUEST['state'] == $_SESSION['state']) {
	if (empty($_SESSION['tokens'][$_REQUEST['service']])) {
		// APIs Using OAuth2.0
		if (in_array($_REQUEST['service'], array_keys($apis))) {
			require_once('oauth2.php');
			$_SESSION[$_REQUEST['service']]->login($_REQUEST['service']);
			// If we're here, there was an error
			$_SESSION['error'] = array('Could not login');
			header('HTTP/1.1 403 Unauthorized');
			header("Location: {$_SESSION['prelogin']}");
			unset($_SESSION['prelogin']);
			exit;
		}
	}
}

// Successfully Logged In !
header('HTTP/1.1 200 OK');
header("Location: {$_SESSION['prelogin']}");
$_SESSION['logged_in'] = true;
unset($_SESSION['prelogin']);
exit;
?>
