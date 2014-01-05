<?php
require_once('oath2.php');
class Facebook extends OAuth2 {
	public $fb = array(
		'image' => 'http://yodas.ws/api/facebook.png',
		'client_id' => '',
		'secret' => '',
	);
	public static $auth_url = 'https://www.facebook.com/dialog/oauth';
	public static $token_url = 'https://graph.facebook.com/oauth/access_token';
	public static $user_url = 'https://graph.facebook.com/me?access_token=';
#feed_url' => 'https://graph.facebook.com/{$user}/feed?access_token=', // Grab Facebook Feed
	public function __construct($options) {
		parent::__construct($options);
	}

	public function loadUser() {
		$fb['response'] = HttpHelper($fb['user_url'] . $_SESSION['fb_token']);
		list($header, $fb_user_response) = explode("\r\n\r\n", $fb['response'], 2);
		$_SESSION['user']['fb'] = json_decode($fb_user_response, true);
		$_SESSION['user']['fb']['image'] = "https://graph.facebook.com/{$_SESSION['user']['fb']['id']}/picture";
	}
}

// Use Facebook Authentication for Login


// Second, Now Login
if ($_REQUEST['state'] == $_SESSION['state'] and !empty($_GET['code'])) {
}

// Third, Load User Data
if (!empty($_SESSION['fb_token']) and empty($_SESSION['user']['fb'])) {
}
