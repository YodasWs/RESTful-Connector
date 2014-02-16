<?php
require_once('oauth2.php');
class Facebook extends OAuth2 {
	const base_uri = 'https://graph.facebook.com';
	const auth_url = 'https://www.facebook.com/dialog/oauth';
	const token_url = 'https://graph.facebook.com/oauth/access_token';
	const base_query = '?access_token=';

	public function __construct($options=null) {
		if (!empty($_SESSION['tokens']['fb']) and empty($_SESSION['user']['fb']))
			$this->getUser();
		$this->construct();
		parent::__construct($options);
	}

	protected function construct() {
		$urls = array(
			'stream' => '/me/home',
			'user' => '/me',
		);
		return parent::construct($urls);
	}

	public function __get($prop) {
		$this->construct();
		if (in_array($prop, array(
			'logo', 'icon', 'image',
		))) return 'http://yodas.ws/api/facebook.png';
		switch ($prop) {
		}
	}

	public static function getURL($target) {
		switch ($target) {
		case 'user':
			return '';
		case 'feed':
			return '/feed';
		}
		return paretn::getURL($target);
	}

	// Get Profile Picture
	public static function userPic($user) {
		return Facebook::base_uri . "/{$user}/picture";
	}

	// Load User Profile
	public function getUser($username='me') {
		$this->construct();
		if ($username == 'me' and empty($_SESSION['tokens']['fb']))
			return false;
		$url = self::base_uri . "/$username" . self::getURL('user');
		if (!empty($_SESSION['tokens']['fb']))
			$url .= self::base_query . $_SESSION['tokens']['fb'];
		$fb_user_response = file_get_contents($url);
		$user = json_decode($fb_user_response, true);
		if (isset($user['error'])) {
			$_SESSION['error'] = $user['error'];
			return false;
		}
		if ($username == 'me') {
			$_SESSION['user']['fb'] = $user;
			$_SESSION['user']['fb']['image'] = self::base_uri . "/{$_SESSION['user']['fb']['id']}/picture";
		}
		return $user;
	}

	// Load Home Page News Feed
	public function newsStream() {
		$this->construct();
		if (empty($_SESSION['tokens']['fb'])) return false;
		require_once('http.class.php');
		try {
			list($headers, $stream) = httpWorker::get($this->urls['stream']);
			preg_match("'^HTTP/1\.. (\d+) '", $headers[0], $matches);
			if ((int) ($matches[1] / 100) != 2) {
				if (in_array($matches[1], array(
					401,403,
				))) {
					unset($_SESSION['tokens']['fb']);
					header('HTTP/1.1 403 Forbidden');
					header('Location: /login?service=fb');
					exit;
				}
				return false;
			}
			$stream = json_decode($stream, true);
			if (isset($stream['error'])) return false;
			return $stream;
		} catch (Exception $e) {
		}
	}

	// Load User Activity
	public function userFeed($user='me') {
		$this->construct();
		// Active User Required
		if (empty($_SESSION['tokens']['fb'])) {
			if ($user == 'me') {
				// Need to Login
				header('HTTP/1.1 403 Forbidden');
				header('Location: /login?service=fb');
				exit;
			}
			return false;
		}
		$url = self::base_uri . "/$user" . self::getURL('feed') . self::base_query . $_SESSION['tokens']['fb'];
		require_once('http.class.php');
		try{
			list($headers, $feed) = httpWorker::get($url);
			preg_match("'^HTTP/1\.. (\d+) '", $headers[0], $matches);
			if ((int) ($matches[1] / 100) != 2) {
				unset($_SESSION['tokens']['fb']);
				return false;
			}
			if (!$feed) return false;
			$feed = json_decode($feed, true);
			if (!empty($feed['error'])) return false;
			return $feed;
		} catch (Exception $e) {
		}
		return false;
	}
}
