<?php
require_once('keys.php');
class OAuth2 {
	protected $access_token;
	const auth_url = ''; // Ask for Permissions
	const token_url = ''; // Grab Access Token

	public function __construct($options) {
	}

	public static function getPermission($service, $login_url) {
		if (strpos($login_url, 'http://') !== 0 and strpos($login_url, 'https://') !== 0)
			$login_url = substr($_SERVER['SCRIPT_URI'], 0, strpos($_SERVER['SCRIPT_URI'], ':')) . "://{$_SERVER['HTTP_HOST']}$login_url";
		$url = $login_url;
		$_SESSION['state'] = md5(uniqid(rand(100,999))) . '%' . $login_url;
		switch ($service) {
		case 'fb':
			require_once('facebook.php');
			$url = Facebook::auth_url .
				'?client_id=' . Keys::$fb['client_id'] .
				'&state=' . urlencode($_SESSION['state']) .
				'&redirect_uri=' . urlencode($login_url);
			break;
		default:
			return false;
		}
		header("Location: $url");
		exit;
	}

	public static function login($service) {
		list($state, $login_url) = explode('%', $_SESSION['state'], 2);
		unset($_SESSION['state']);
		switch ($service) {
		case 'fb':
			require_once('facebook.php');
			$url = Facebook::token_url .
				'?client_id=' . Keys::$fb['client_id'] .
				'&client_secret=' . Keys::$fb['secret'] . 
				'&code=' . $_GET['code'] . 
				'&redirect_uri=' . urlencode($login_url);
			require_once('http.class.php');
			$fb_user_response = '';
			try {
				$http = httpWorker::request($url);
				$fb_user_response = $http['response'];
			} catch (Exception $e) {
				$_SESSION['error'] = array('Could not log into Facebook');
				header('HTTP/1.1 500 Error');
				header("Location: {$_SESSION['prelogin']}");
				unset($_SESSION['prelogin']);
				exit;
			}
			// TODO: Check HTTP Status Code
			if ($fb_user_response) {
				$params = null;
				parse_str($fb_user_response, $params);
				$_SESSION['tokens']['fb'] = $params['access_token'];
				unset($params);
			}
			unset($http);
			unset($fb_user_response);
			header("Location: $login_url");
			exit;
		}
	}
}
