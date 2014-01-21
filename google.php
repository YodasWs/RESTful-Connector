<?php
require_once('oauth2.php');
class Google extends OAuth2 {
	const base_uri = 'https://www.googleapis.com/plus/v1';
	const auth_url = 'https://accounts.google.com/o/oauth2/auth';
	const token_url = 'https://accounts.google.com/o/oauth2/token';
	const base_query = '?prettyPrint=false&access_token=';

	private $urls = array(
		'feed' => '/people/me/activities',
		'user' => '/people/me',
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
		$this->construct();
		$user_response = file_get_contents($this->urls['user']);
		$stream = json_decode($user_response, true);
		if (isset($stream['error'])) return false;
		$_SESSION['user']['google'] = $stream;
		return true;
	}

	public function newsStream() {
		return array();
		$this->construct();
		$stream = file_get_contents($this->urls['stream']);
		$stream = json_decode($stream, true);
		if (isset($stream['error'])) return false;
		return $stream;
	}

	public function loadFeed($user='me') {
		$this->construct();
		if (empty($_SESSION['tokens']['google'])) return false;
		$url = "https://www.googleapis.com/plus/v1/people/{$user}/activities/public?access_token={$_SESSION['tokens']['google']}";
		require_once('http.class.php');
		try {
			list($headers, $stream) = httpWorker::get($url);
			preg_match("'^HTTP/1\.. (\d+) '", $headers[0], $matches);
			if ((int) ($matches[1] / 100) != 2) {
				unset($_SESSION['tokens']['google']);
				return false;
			}
			if ($stream) {
				$stream = json_decode($stream, true);
				if (isset($stream['error'])) return false;
				return $stream;
			}
		} catch (Exception $e) {
		}
		return false;
	}
}
