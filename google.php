<?php
require_once('oauth2.php');
class Google extends OAuth2 {
	const base_uri = 'https://';
	const auth_url = 'https://accounts.google.com/o/oauth2/auth';
	const token_url = 'https://accounts.google.com/o/oauth2/token';
	const base_query = '?access_token=';

	private $urls = array(
	);
	private $is_constructed = false;

	public function __construct($options) {
		if (!empty($_SESSION['tokens']['google']) and empty($_SESSION['user']['google']))
			$this->loadUser();
		$this->construct();
		parent::__construct($options);
	}

	private function construct() {
		if ($this->is_constructed) return true;
		foreach ($this->urls as &$url) {
			$url = self::base_uri . $url . self::base_query . $_SESSION['tokens']['google'];
		}
		$this->is_constructed = true;
		return true;
	}

	public function __get($prop) {
		$this->construct();
		if (in_array($prop, array(
		))) return '';
		switch ($prop) {
		}
	}

	public function loadUser() {
		return array();
		$this->construct();
		$user_response = file_get_contents($this->urls['user']);
		$_SESSION['user']['google'] = json_decode($user_response, true);
#		$_SESSION['user']['google']['image'] = self::base_uri . "/{$_SESSION['user']['google']['id']}/picture";
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
		if (empty($_SESSION['tokens']['google'])) {
			if ($user == 'me') {
				// Need to Login
				header('HTTP/1.1 403 Forbidden');
				header('Location: /login?service=google');
				exit;
			} else {
			}
		} else $url = "https://graph.facebook.com/{$user}/feed?access_token={$_SESSION['tokens']['google']}";
	}
}
