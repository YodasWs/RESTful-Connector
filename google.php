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
			'stream' => '/people/me/activities/public',
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

	public static function getURL($target, $user='me') {
		switch ($target) {
		case 'user':
			return "/people/$user";
		case 'feed':
			return self::getURL('user', $user) . "/activities/public";
		case 'circles':
			return self::getURL('user', $user) . "/people/visible";
		}
		return parent::getURL($target);
	}

	public function getUser($user='me') {
		$this->construct();
		if (empty($_SESSION['tokens']['google'])) return false;
		$url = self::base_uri . self::getURL('user', $user) . self::base_query . $_SESSION['tokens']['google'];
		require_once('http.class.php');
		try {
			list($headers, $stream) = httpWorker::get($url);
			preg_match("'^HTTP/1\.. (\d+) '", $headers[0], $matches);
			if ((int) ($matches[1] / 100) != 2) {
error_log("{$headers[0]} in " . __METHOD__);
				unset($_SESSION['tokens']['google']);
				return false;
			}
			$user = json_decode($stream, true);
			if (isset($user['error'])) {
				$_SESSION['error'] = $user['error'];
				return false;
			}
			$_SESSION['user']['google'] = $user;
			return true;
		} catch (Exception $e) {
		}
		return false;
	}

	// People $user is Following
	// https://developers.google.com/+/api/latest/people/list
	public function getFollowing($user='me') {
		$this->construct();
		if (empty($_SESSION['tokens']['google'])) return false;
		$url = self::base_uri . self::getURL('circles', $user) . self::base_query . $_SESSION['tokens']['google'];
		require_once('http.class.php');
		try {
			list($headers, $json) = httpWorker::get($url);
			if ((int) ($matches[1] / 100) != 2) {
#				unset($_SESSION['tokens']['google']);
				return false;
			}
			$users = json_decode($json, true);
			return $users['items'];
		} catch (Exception $e) {
		}
		return false;
	}

	// Get Activities from User's Friends
	public function newsStream() {
		$this->construct();
		if (empty($_SESSION['tokens']['google'])) return false;
error_log("in " . __METHOD__);
		require_once('http.class.php');
		$_SESSION['user']['google']['following'] = $this->getFollowing('me');
		if (empty($_SESSION['user']['google']['following']) or !is_array($_SESSION['user']['google']['following'])) return false;
		$stream = array();
		foreach ($_SESSION['user']['google']['following'] as $person) {
			$stream[] = userFeed($person['id']);
		}
return $stream;
		try {
			list($headers, $stream) = httpWorker::get($this->urls['stream']);
			preg_match("'^HTTP/1\.. (\d+) '", $headers[0], $matches);
			if ((int) ($matches[1] / 100) != 2) {
#				unset($_SESSION['tokens']['google']);
				return false;
			}
			$stream = json_decode($stream, true);
			if (isset($stream['error'])) {
				$_SESSION['error'] = $stream['error'];
				return false;
			}
			return $stream['items'];
		} catch (Exception $e) {
		}
		return false;
	}

	// Activities Posted by $user
	// https://developers.google.com/+/api/latest/activities/list
	public function userFeed($user='me') {
		$this->construct();
		if (empty($_SESSION['tokens']['google'])) {
			if ($user == 'me') {
				return false;
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
error_log("{$headers[0]} in " . __METHOD__);
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
