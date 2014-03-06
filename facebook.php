<?php
require_once('oauth2.php');
require_once('http.class.php');
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
		if (in_array($target, array(
			'feed','picture','permissions','likes',
		))) {
			return "/$target";
		} else switch ($target) {
		case 'user':
			return '';
		}
		return parent::getURL($target);
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
		$request = array(
			'include_headers' => false,
			'batch' => array(),
		);
		if (!empty($_SESSION['tokens']['fb']))
			$request['access_token'] = $_SESSION['tokens']['fb'];
		$keys = array('user'); // Track Which Response Matches Which Request
		$request['batch'][] = array(
			'relative_url' => $username . self::getURL('user'),
			'method' => 'GET',
		);
		if ($username == 'me') {
			// Check Granted Permissions
			$keys[] = 'permissions';
			$request['batch'][] = array(
				'relative_url' => $username . self::getURL('permissions'),
				'method' => 'GET',
			);
		}
		$user = array();

		try {
			$request['batch'] = json_encode($request['batch']);
			list($headers, $fb_response) = httpWorker::post(self::base_uri, $request);
			preg_match("'^HTTP/1\.. (\d+) '", $headers[0], $matches);
			if ((int) ($matches[1] / 100) != 2) return false;
			$fb_response = json_decode($fb_response, true);
			if (isset($fb_response['error'])) {
				$_SESSION['error'] = $fb_response['error'];
				return false;
			}
			foreach ($fb_response as $reply => $response) {
				$body = json_decode($response['body'], true);
				if ((int) ($response['code'] / 100) != 2) return false;
				if (isset($body['error'])) {
					$_SESSION['error'] = $body['error'];
					return false;
				}
				if ($reply === 0) $user = $body;
				else if (!empty($body['data'])) {
					$body['data'][0] = array_filter($body['data'][0]);
					if ($keys[$reply] == 'permissions')
						$user['permissions'] = array_keys($body['data'][0]);
					else
						$user[$keys[$reply]] = $body['data'];
				}
			}
		} catch (Exception $e) {
			return false;
		}

		if ($username == 'me' and !empty($user)) {
			$_SESSION['user']['fb'] = $user;
			$_SESSION['user']['fb']['image'] = self::userPic($_SESSION['user']['fb']['id']);
		}
		return $user;
	}

	// Load Home Page News Feed
	public function newsStream() {
		$this->construct();
		if (empty($_SESSION['tokens']['fb'])) return false;
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
			if (isset($stream['error'])) {
				$_SESSION['error'] = $stream['error'];
				return false;
			}
// TODO: Ask for Like on Each Item
			return $stream['data'];
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

	// Like a Facebook Object
	// https://developers.facebook.com/docs/reference/api/post/
	public function like($object) {
		$this->construct();
		if (empty($_SESSION['tokens']['fb'])) return false;
		if (!in_array('publish_actions', $_SESSION['user']['fb']['permissions'])) return false; // Check Permission
		$object = $this->validID($object);
		if (empty($object)) return false;
		// Like
		try {
			list($headers, $response) = httpWorker::post(Facebook::base_uri . $object . self::getURL('likes'), array(
				'access_token' => $_SESSION['tokens']['fb'],
			));
			return $response;
		} catch (Exception $e) {
		}
		return false;
	}

	private function validID($object) {
		// Check for Valid ID
		if (is_int($object) or is_numeric($object)) {
			$object = "/$object";
		} else if (is_string($object) and preg_match("'^\d+_\d+$'", $object)) {
			// This is a valid Facebook ID, "<user_id>_<post_id>"
			$object = "/{$object}";
		} else if (is_string($object) and preg_match("'^(https://graph.facebook.com)?/?(\d+)/\w+/(\d+)$'", $object, $matches)) {
			// Convert to Facebook ID, "<user_id>_<post_id>"
			$object = "/{$matches[1]}_{$matches[2]}";
		} else return false;
		return $object;
	}

	// Unlike a Facebook Object
	public function unlike($object) {
		$this->construct();
		if (empty($_SESSION['tokens']['fb'])) return false;
		if (!in_array('publish_actions', $_SESSION['user']['fb']['permissions'])) return false;
		$object = $this->validID($object);
		if (empty($object)) return false;
		try {
			$url = Facebook::base_uri . $object . self::getURL('likes') . self::base_query . $_SESSION['tokens']['fb'];
			list($headers, $response) = httpWorker::request($url, 'DELETE');
			return $response;
		} catch (Exception $e) {
		}
		return false;
	}
}
