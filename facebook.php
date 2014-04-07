<?php
require_once('oauth2.php');
require_once('http.class.php');
class Facebook extends OAuth2 {
	const base_uri = 'https://graph.facebook.com';
	const auth_url = 'https://www.facebook.com/dialog/oauth';
	const token_url = 'https://graph.facebook.com/oauth/access_token';
	const base_query = '?access_token=';

	private $paging = array(
		'next' => array(),
		'prev' => array(),
	);

	public function __construct($options=null) {
		if (!empty($_SESSION['tokens']['fb']) and empty($_SESSION['user']['fb']))
			$this->getUser();
		$this->construct();
		parent::__construct($options);
	}

	protected function construct() {
		if ($this->is_constructed) return true;
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
			'feed','picture','permissions','likes','comments'
		))) {
			return "/$target";
		} else switch ($target) {
		case 'user':
			return '';
		}
		return parent::getURL($target);
	}

	public function login($api) {
		$login_url = parent::login('fb');
		if (!empty($login_url) and !empty($_SESSION['tokens']['fb'])) {
			// Get Long-lived Token
			list($headers, $response) = httpWorker::get(
				Facebook::token_url . '?grant_type=fb_exchange_token' .
				'&client_id=' . Keys::$fb['client_id'] .
				'&client_secret=' . Keys::$fb['secret'] .
				'&fb_exchange_token=' . $_SESSION['tokens']['fb']
			);
			preg_match("'^HTTP/1\.. (\d+) '", $headers[0], $matches);
			if ((int) ($matches[1] / 100) == 2) {
				parse_str($response, $params);
				$_SESSION['tokens']['fb'] = $params['access_token'];
				$_SESSION['tokens']['fb_expires'] = $params['expires'] + time();
				unset($params);
				// TODO: Get Client Long-lived Token, https://developers.facebook.com/docs/facebook-login/access-tokens
			}
			// Success, Redirect!
			$this->getUser();
			header('HTTP/1.1 302 Found');
			header("Location: $login_url");
			exit;
		}
		return false;
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
			$_SESSION['error'] = $e->getMessage();
			return false;
		}

		if ($username == 'me' and !empty($user)) {
			$_SESSION['user']['fb'] = $user;
			$_SESSION['user']['fb']['image'] = self::userPic($_SESSION['user']['fb']['id']);
		}
		return $user;
	}

	// Load Home Page News Feed
	public function newsStream($limit=25, $query='') {
		$this->construct();
		if (empty($_SESSION['tokens']['fb'])) return false;
		try {
			$url = "{$this->urls['stream']}&limit=$limit";
			if (!empty($query)) $url .= "&$query";
			list($headers, $stream) = httpWorker::get($url);
			preg_match("'^HTTP/1\.\d+ (\d+) '", $headers[0], $matches);
			if ((int) ($matches[1] / 100) != 2) {
				error_log(__METHOD__ . ": HTTP Status {$matches[1]}");
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
				error_log(__METHOD__ . ': ' . $stream['error']);
				$_SESSION['error'] = $stream['error'];
				return false;
			}
			if (!empty($stream['paging']['next']) and !in_array($stream['paging']['next'], $this->paging['next']))
				$this->paging['next']['stream'] = $stream['paging']['next'];
			if (!empty($stream['paging']['prev']) and !in_array($stream['paging']['prev'], $this->paging['prev']))
				$this->paging['prev']['stream'] = $stream['paging']['prev'];
// TODO: Ask for Like on Each Item
			return $stream['data'];
		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
		}
		return false;
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
			$_SESSION['error'] = $e->getMessage();
		}
		return false;
	}

	// Like a Facebook Object
	// https://developers.facebook.com/docs/reference/api/post/
	// https://developers.facebook.com/docs/graph-api/reference/object/likes
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
			if (!$response) return false;
			if ($response == 'true') return true; // Like Successful
			$response = json_decode($response, true);
			if (!empty($response['error'])) {
				if (!empty($response['error']['code']) and $response['error']['code'] == 1705)
					return true; // User Liked This Already
				$_SESSION['error'] = $response['error']['message'];
				error_log($response['error']['message']);
				return false;
			}
			return false;
		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
			error_log($e->getMessage());
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
		if (empty($_SESSION['user']['fb']['permissions'])) return false;
		if (!in_array('publish_actions', $_SESSION['user']['fb']['permissions'])) return false;
		$object = $this->validID($object);
		if (empty($object)) return false;
		try {
			$url = Facebook::base_uri . $object . self::getURL('likes') . self::base_query . $_SESSION['tokens']['fb'];
			list($headers, $response) = httpWorker::request($url, 'DELETE');
			return $response;
		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
		}
		return false;
	}

	public function getComments($item) {
		$this->construct();
		if (empty($_SESSION['tokens']['fb'])) return false;
		$item = $this->validID($item);
		if (empty($item)) return false;
		try {
			list($headers, $response) = httpWorker::get(
				Facebook::base_uri . $item . self::getURL('comments') . self::base_query . $_SESSION['tokens']['fb']
			);
			preg_match("'^HTTP/1\.. (\d+) '", $headers[0], $matches);
			if ((int) ($matches[1] / 100) != 2) return false;
			if (!$response) return false;
			$response = json_decode($response, true);
			if (!empty($response['error'])) {
				error_log(__METHOD__ . ': ' . $response['error']);
				$_SESSION['error'] = $response['error'];
				return false;
			}
			if (!isset($response['data'])) return false;
			return $response['data'];
		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
		}
		return false;
	}

	// TODO: Post Comment
	public function postComment($item, $comment) {
		$this->construct();
		if (empty($_SESSION['tokens']['fb'])) return false;
		if (empty($_SESSION['user']['fb']['permissions'])) return false;
		if (!in_array('publish_actions', $_SESSION['user']['fb']['permissions'])) return false;
		$item = $this->validID($item);
		if (empty($item)) return false;
		$key = 'message';
		if ($attachment = $this->validID($comment)) {
			$comment = $attachment;
			$key = 'attachment_id';
		}
		try {
			list($headers, $response) = httpWorker::post(Facebook::base_uri . $object . self::getURL('comments'), array(
				'access_token' => $_SESSION['tokens']['fb'],
				$key => $comment,
			));
			return $response;
		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
		}
		return false;
	}
}
