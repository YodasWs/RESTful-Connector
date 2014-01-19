<?php
require_once('oauth2.php');
class Facebook extends OAuth2 {
	const base_uri = 'https://graph.facebook.com';
	const auth_url = 'https://www.facebook.com/dialog/oauth';
	const token_url = 'https://graph.facebook.com/oauth/access_token';
	const base_query = '?access_token=';

	private $urls = array(
		'stream' => '/me/home',
		'user' => '/me',
	);
	private $is_constructed = false;

	public function __construct($options) {
		if (!empty($_SESSION['tokens']['fb']) and empty($_SESSION['user']['fb']))
			$this->loadUser();
		$this->construct();
		parent::__construct($options);
	}

	private function construct() {
		if ($this->is_constructed) return true;
		foreach ($this->urls as &$url) {
			$url = self::base_uri . $url . self::base_query . $_SESSION['tokens']['fb'];
		}
		$this->is_constructed = true;
		return true;
	}

	public function __get($prop) {
		$this->construct();
		if (in_array($prop, array(
			'logo', 'icon', 'image',
		))) return 'http://yodas.ws/api/facebook.png';
		switch ($prop) {
		}
	}

	public function loadUser() {
		$this->construct();
		$fb_user_response = file_get_contents($this->urls['user']);
		$_SESSION['user']['fb'] = json_decode($fb_user_response, true);
		$_SESSION['user']['fb']['image'] = self::base_uri . "/{$_SESSION['user']['fb']['id']}/picture";
	}

	public function newsStream() {
		$this->construct();
		$stream = file_get_contents($this->urls['stream']);
		$stream = json_decode($stream, true);
		if (isset($stream['error'])) return false;
		return $stream;
	}

	public function loadFeed($user) {
		$this->construct();
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
