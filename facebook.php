<?php

class OAuth2 {
	private $secret;
	private $client_id;
	protected $access_token;
	protected $urls = array(
		'auth_url' => '', // Ask for Permissions
		'token_url' => '', // Grab Access Token
		'user_url' => '', // Grab User Info
	);
	public function __construct($options) {
		if (empty($options['secret']))
			throw new Exception("OAuth2.0 requires a secret key");
		if (empty($options['client_id']))
			throw new Exception("Client ID required");
		$this->secret = $options['secret'];
		$this->client_id = $options['client_id'];
	}
}

class Facebook extends OAuth2 {
	public $fb = array(
		'image' => 'http://yodas.ws/api/facebook.png',
		'client_id' => '',
		'secret' => '',
	);
#feed_url' => 'https://graph.facebook.com/{$user}/feed?access_token=', // Grab Facebook Feed
	public function __construct($options) {
		parent::__construct($options);
		$this->urls = array_merge($this->urls, array(
			'auth_url' => 'https://www.facebook.com/dialog/oauth',
			'token_url' => 'https://graph.facebook.com/oauth/access_token',
			'user_url' => 'https://graph.facebook.com/me?access_token=',
		));
	}
}

// Use Facebook Authentication for Login

// First, Ask for Permission
if (empty($_GET['code']) and !empty($_GET['state'])) {
	$_SESSION['state'] = $_GET['state'];
	$url = "{$fb['auth_url']}?client_id={$fb['client_id']}&state={$_SESSION['state']}" .
		"&redirect_uri=" . urlencode($login_url).
		"&scope=publish_stream";
	header("Location: $url");
	exit;
}

// Second, Now Login
if ($_REQUEST['state'] == $_SESSION['state'] and !empty($_GET['code'])) {
	$url = "{$fb['token_url']}?client_id={$fb['client_id']}&client_secret={$fb['secret']}&code={$_GET['code']}".
		"&redirect_uri=" . urlencode($login_url);
	$fb['response'] = HttpHelper($fb['user_url'] . $_SESSION['fb_token']);
	list($header, $fb_user_response) = explode("\r\n\r\n", $fb['response'], 2);
	// TODO: Check HTTP Status Code
	#$fb_user_response = @file_get_contents($url);
	if ($fb_user_response) {
		$params = null;
		parse_str($fb_user_response, $params);
		$_SESSION['fb_token'] = $params['access_token'];
		unset($params);
	}
	unset($fb_user_response);
	list($state, $redirect) = explode('%', $_SESSION['state'], 2);
	unset($_SESSION['state']);
	header("Location: $hostname$redirect");
	exit;
}

// Third, Load User Data
if (!empty($_SESSION['fb_token']) and empty($_SESSION['user']['fb'])) {
	$fb['response'] = HttpHelper($fb['user_url'] . $_SESSION['fb_token']);
	list($header, $fb_user_response) = explode("\r\n\r\n", $fb['response'], 2);
	$_SESSION['user']['fb'] = json_decode($fb_user_response, true);
	$_SESSION['user']['fb']['image'] = "https://graph.facebook.com/{$_SESSION['user']['fb']['id']}/picture";
}