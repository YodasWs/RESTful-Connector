<?php
require_once('oauth2.php');
class Google extends OAuth2 {
	const base_uri = 'https://www.googleapis.com/plus/v1';
	const auth_url = 'https://accounts.google.com/o/oauth2/auth';
	const token_url = 'https://accounts.google.com/o/oauth2/token';
	const base_query = '?prettyPrint=false&access_token=';

	public function __construct($options=null) {
		if (!empty($_SESSION['tokens']['google']) and empty($_SESSION['user']['google']))
			$this->getUser();
		$this->construct();
		parent::__construct($options);
	}

	protected function construct() {
		$urls = array(
			'feed' => '/people/me/activities',
			'user' => '/people/me',
		);
		return parent::construct($urls);
	}

	public function __get($prop) {
		$this->construct();
		if (in_array($prop, array(
		))) return '';
		switch ($prop) {
		}
	}

	public static function getURL($target) {
		switch ($target) {
		case 'user':
			return '/people';
		case 'feed':
			return '/activities';
		}
		return parent::getURL($target);
	}

	public function getUser($user='me') {
		$this->construct();
		if (empty($_SESSION['tokens']['google'])) return false;
		$url = self::base_uri . self::getURL('user') . "/$user" . self::base_query . $_SESSION['tokens']['google'];
		require_once('http.class.php');
		try {
			list($headers, $stream) = httpWorker::get($url);
			preg_match("'^HTTP/1\.. (\d+) '", $headers[0], $matches);
			if ((int) ($matches[1] / 100) != 2) {
				unset($_SESSION['tokens']['google']);
				return false;
			}
			$stream = json_decode($user, true);
			if (isset($stream['error'])) return false;
			$_SESSION['user']['google'] = $stream;
			return true;
		} catch (Exception $e) {
		}
		return false;
	}

	public function newsStream() {
		return array();
		$this->construct();
		$stream = file_get_contents($this->urls['stream']);
		$stream = json_decode($stream, true);
		if (isset($stream['error'])) return false;
		return $stream;
	}

	public function userFeed($user='me') {
		$this->construct();
		if (empty($_SESSION['tokens']['google'])) {
			if ($user == 'me') {
				// Login Required
				header('HTTP/1.1 403 Forbidden');
				header('Location: /login?service=google');
				exit;
			}
			return false;
		}
		$url = self::base_uri . "/people/{$user}/activities/public?access_token={$_SESSION['tokens']['google']}";
		require_once('http.class.php');
		try {
			list($headers, $stream) = httpWorker::get($url);
			preg_match("'^HTTP/1\.. (\d+) '", $headers[0], $matches);
			if ((int) ($matches[1] / 100) != 2) {
				unset($_SESSION['tokens']['google']);
				return false;
			}
			if (!$stream) return false;
			$stream = json_decode($stream, true);
			if (isset($stream['error'])) return false;
			return $stream;
		} catch (Exception $e) {
		}
		return false;
	}

	public function unlike($object) {}
	public function like($object) {}
}
