<?php
class OAuth2 {
	private $secret;
	private $client_id;
	protected $access_token;
	public static auth_url = ''; // Ask for Permissions
	public static token_url = ''; // Grab Access Token
	public static user_url = ''; // Grab User Info

	public function __construct($options) {
		if (empty($options['secret']))
			throw new Exception("OAuth2.0 requires a secret key");
		if (empty($options['client_id']))
			throw new Exception("Client ID required");
		$this->secret = $options['secret'];
		$this->client_id = $options['client_id'];
	}

	public static function getPermission($service) {
		$url = '';
		$_SESSION['state'] = $_GET['state'];
		switch ($service) {
		case 'fb':
			require_once('facebook.php');
			$url = Facebook::$auth_url . '?client_id=' . Facebook::$client_id . '&state=' . Facebook::$state .
				'&redirect_uri=' . urlencode($login_url).
				'&scope=publish_stream';
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
			$url = "{$fb['token_url']}?client_id={$fb['client_id']}&client_secret={$fb['secret']}&code={$_GET['code']}".
				"&redirect_uri=" . urlencode($login_url);
			$fb['response'] = HttpHelper($fb['user_url'] . $_SESSION['fb_token']);
			list($header, $fb_user_response) = explode("\r\n\r\n", $fb['response'], 2);
			// TODO: Check HTTP Status Code
			#$fb_user_response = @file_get_contents($url);
			if ($fb_user_response) {
				$params = null;
				parse_str($fb_user_response, $params);
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
