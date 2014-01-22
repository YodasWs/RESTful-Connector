<?php
if (file_exists('keys.php'))
	require_once('keys.php');

abstract class OAuth2 {
	protected $access_token;
	const auth_url = ''; // Ask for Permissions
	const token_url = ''; // Grab Access Token
	protected $is_constructed = false;
	protected $urls = array();

	public function __construct($options=null) {
	}

	public static function getPermission($api, $login_url) {
		global $apis;
		if (strpos($login_url, 'http://') !== 0 and strpos($login_url, 'https://') !== 0)
			$login_url = substr($_SERVER['SCRIPT_URI'], 0, strpos($_SERVER['SCRIPT_URI'], ':')) . "://{$_SERVER['HTTP_HOST']}$login_url";
		$url = $login_url;
		$_SESSION['state'] = md5(uniqid(rand(100,999))) . '%' . $login_url;
		switch ($api) {
		case 'fb':
			require_once($apis['fb']['file']);
			$url = Facebook::auth_url .
				'?client_id=' . Keys::$fb['client_id'] .
				'&state=' . urlencode($_SESSION['state']) .
				'&redirect_uri=' . urlencode($login_url);
			break;
		case 'google':
			require_once($apis['google']['file']);
			$url = Google::auth_url . '?response_type=code&access_type=offline&include_granted_scopes=true' .
				'&scope=' . urlencode(implode(' ', $apis['google']['scope'])) .
				'&client_id=' . Keys::$google['client_id'] .
				'&state=' . urlencode(urlencode($_SESSION['state'])) .
				'&redirect_uri=' . urlencode($login_url);
			break;
		default:
			return false;
		}
		header('HTTP/1.1 302 Found');
		header("Location: $url");
		exit;
	}

	public function login($api) {
		global $apis;
		list($state, $login_url) = explode('%', $_SESSION['state'], 2);
		$this->is_constructed = false;
		unset($_SESSION['state']);
		require_once('http.class.php');
		switch ($api) {
		case 'fb':
			$url = Facebook::token_url .
				'?client_id=' . Keys::$fb['client_id'] .
				'&client_secret=' . Keys::$fb['secret'] .
				'&code=' . $_GET['code'] .
				'&redirect_uri=' . urlencode($login_url);
			$fb_user_response = '';
			try {
				$http = httpWorker::request($url);
				$fb_user_response = $http['response'];
			} catch (Exception $e) {
				$_SESSION['error'] = array('Could not log into Facebook');
				header('HTTP/1.1 500 Error');
				header("Location: {$_SESSION['prelogin']}");
				unset($_SESSION['prelogin']);
				exit;
			}
			// TODO: Check HTTP Status Code
			if ($fb_user_response) {
				$params = null;
				parse_str($fb_user_response, $params);
				$_SESSION['tokens']['fb'] = $params['access_token'];
				unset($params);
			}
			unset($http);
			unset($fb_user_response);
			break;
		case 'google':
			$google_response = '';
			$login_url = urldecode($login_url);
			try {
				$http = httpWorker::request(Google::token_url, 'POST', http_build_query(array(
					'client_secret' => Keys::$google['secret'],
					'client_id' => Keys::$google['client_id'],
					'redirect_uri' => $login_url,
					'grant_type' => 'authorization_code',
					'code' => $_GET['code'],
				)));
				$google_response = $http['response'];
			} catch (Exception $e) {
				$_SESSION['error'] = array('Could not log into Google');
				header('HTTP/1.1 500 Error');
				header("Location: {$_SESSION['prelogin']}");
				unset($_SESSION['prelogin']);
				exit;
			}
			// TODO: Check HTTP Status Code
			if ($google_response) {
				$params = json_decode($google_response, true);
				$_SESSION['tokens']['google'] = $params['access_token'];
				// TODO: Save $params['refresh_token']
				unset($params);
			}
			break;
		default:
			header('HTTP/1.1 400 Bad Request');
			exit;
		}
		$this->getUser();
		header('HTTP/1.1 302 Found');
		header("Location: $login_url");
		exit;
	}

	public static function getURL($target) {
		switch ($target) {
		}
		return false;
	}

	protected function construct($urls) {
		if ($this->is_constructed) return true;
		$class = get_called_class();
		switch ($class) {
		case 'Facebook':
			$api = 'fb';
			break;
		case 'Google':
			$api = 'google';
			break;
		default:
			return false;
		}
		foreach ($urls as $key => $url) {
			$this->urls[$key] = $class::base_uri . $url;
			if (!empty($_SESSION['tokens'][$api]))
				$this->urls[$key] .= $class::base_query . $_SESSION['tokens'][$api];
		}
		$this->is_constructed = true;
		return true;
	}
	public abstract function userFeed();
	public abstract function getUser();
}
