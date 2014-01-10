<?php
require_once('keys.php');
class OAuth2 {
	protected $access_token;
	public static $auth_url = ''; // Ask for Permissions
	public static $token_url = ''; // Grab Access Token
	public static $user_url = ''; // Grab User Info

	public function __construct($options) {
	}

	public static function getPermission($service, $login_url) {
		if (strpos($login_url, 'http://') !== 0 and strpos($login_url, 'https://') !== 0)
			$login_url = substr($_SERVER['SCRIPT_URI'], 0, strpos($_SERVER['SCRIPT_URI'], '//')+2) . "{$_SERVER['HTTP_HOST']}$login_url";
		$url = $login_url;
		$_SESSION['state'] = md5(uniqid(rand(100,999)));
		switch ($service) {
		case 'fb':
			require_once('facebook.php');
			$url = Facebook::$auth_url . '?client_id=' . Keys::$fb['client_id'] . "&state={$_SESSION['state']}&redirect_uri=" . urlencode($login_url);
			break;
		default:
			return false;
		}
		header("Location: $url");
		exit;
	}

	public static function login($service) {
		switch ($service) {
		case 'fb':
			$url = Facebook::$token_url . '?client_id=' . Facebook::$client_id . '&client_secret=' . Facebook::$secret .  '&code=' . $_GET['code'] .  '&redirect_uri=' . urlencode($login_url);
			$fb_user_response = file_get_contents(Facebook::$user_url . $_SESSION['fb_token']);
			// TODO: Check HTTP Status Code
			if ($fb_user_response) {
				$params = null;
				parse_str($fb_user_response, $params);
echo '<pre>' . print_r($params, true); exit;
				$_SESSION['fb_token'] = $params['access_token'];
				unset($params);
			}
			unset($fb_user_response);
			list($state, $redirect) = explode('%', $_SESSION['state'], 2);
			unset($_SESSION['state']);
			header("Location: $hostname$redirect");
			exit;
		}
	}
}
