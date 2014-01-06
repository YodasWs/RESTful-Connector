<?php
require_once('oath2.php');
class Facebook extends OAuth2 {
	public static $client_id;
	public static $secret;
	public static $auth_url = 'https://www.facebook.com/dialog/oauth';
	public static $token_url = 'https://graph.facebook.com/oauth/access_token';
	public static $user_url = 'https://graph.facebook.com/me?access_token=';

	private $user;

	public function __construct($options) {
		parent::__construct($options);
	}

	public function __get($prop) {
		if (in_array($prop, array(
			'logo', 'icon', 'image',
		))) return 'http://yodas.ws/api/facebook.png';
		switch ($prop) {
		}
	}

	public function loadUser() {
		$fb_user_response = file_get_contents($fb['user_url'] . $_SESSION['fb_token']);
		$_SESSION['user']['fb'] = json_decode($fb_user_response, true);
		$_SESSION['user']['fb']['image'] = "https://graph.facebook.com/{$_SESSION['user']['fb']['id']}/picture";
	}

	public function loadFeed($user) {
		if (empty($user)) $user = 'me';
		if (empty($_SESSION['fb_token'])) {
			if ($user == 'me') {
				// Need to Login
				header('Location: /login?service=fb');
				exit;
			} else {
			}
		} else $url = "https://graph.facebook.com/{$user}/feed?access_token={$_SESSION['fb_token']}";
	}
}

// TODO: Load User Data
if (!empty($_SESSION['fb_token']) and empty($_SESSION['user']['fb'])) {
}
