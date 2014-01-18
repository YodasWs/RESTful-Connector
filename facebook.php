<?php
require_once('oauth2.php');
class Facebook extends OAuth2 {
	const auth_url = 'https://www.facebook.com/dialog/oauth';
	const token_url = 'https://graph.facebook.com/oauth/access_token';

	const user_url = 'https://graph.facebook.com/me?access_token=';

	public function __construct($options) {
		if (!empty($_SESSION['tokens']['fb']) and empty($_SESSION['user']['fb']))
			$this->loadUser();
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
		$fb_user_response = file_get_contents(self::user_url . $_SESSION['tokens']['fb']);
		$_SESSION['user']['fb'] = json_decode($fb_user_response, true);
		$_SESSION['user']['fb']['image'] = "https://graph.facebook.com/{$_SESSION['user']['fb']['id']}/picture";
	}

	public function loadFeed($user) {
		if (empty($user)) $user = 'me';
		if (empty($_SESSION['tokens']['fb'])) {
			if ($user == 'me') {
				// Need to Login
				header('HTTP/1.1 403 Forbidden');
				header('Location: /login?service=fb');
				exit;
			} else {
			}
		} else $url = "https://graph.facebook.com/{$user}/feed?access_token={$_SESSION['tokens']['fb']}";
	}
}

// TODO: Load User Data
if (!empty($_SESSION['tokens']['fb']) and empty($_SESSION['user']['fb'])) {
	if (empty($_SESSION['facebook']) or get_class($_SESSION['facebook']) != 'Facebook')
		$_SESSION['facebook'] = new Facebook();
#	$_SESSION['user']['fb'] = array('id' => 'me');
}
