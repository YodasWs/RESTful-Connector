<?php
class httpWorker {
	public static function get($url, $additional_headers) {
		return $this::request($url, 'GET', '', $additional_headers);
	}

	public static function post($url, $request, $additional_headers) {
		return $this::request($url, 'POST', $request, $additional_headers);
	}

	public static function request($url, $method = null, $request = null, $additional_headers = null) {

		if (empty($method)) $method = 'GET';
		if (!is_string($method)) throw new Exception('HTTP Method Required');
		$method = strtoupper($method);

		if (empty($request)) {
			if (!in_array($method, array('GET', 'DELETE', 'HEAD', 'TRACE', 'OPTIONS', 'CONNECT', 'PURGE')))
				throw new Exception("HTTP Method $method requires \$request data");
			else $request = '';
		}

		if (empty($url) or !filter_var($url, FILTER_VALIDATE_URL))
			throw new Exception("Valid URL required");
		$url = parse_url($url);
		if (empty($url['host'])) throw new Exception("Host name required in URL");

		// Set Scheme and Port
		switch ($url['scheme']) {
		case 'https':
			$hostname = "ssl://{$url['host']}";
			$url['port'] = 443;
			break;
		default: // Simple HTTP
			$hostname = $url['host'];
			$url['scheme'] = 'http';
			$url['port'] = 80;
		}

		$length = strlen($request);
		if (!empty($url['query']) and strpos($url['query'], '?') !== 0) $url['query'] = "?{$url['query']}";

		// Add Headers
		$headers = "$method {$url['path']}{$url['query']} HTTP/1.1\r\n";
		$headers .= "Host: {$url['host']}\r\n";
		$headers .= "Connection: close\r\n";
		$headers .= "Content-Length: $length\r\n";
		if (!empty($additional_headers) and is_array($additional_headers)) {
			foreach ($additional_headers as $header) $headers .= trim($header) . "\r\n";
		}
		if (!empty($request))
			$headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$request = "$headers\r\n$request";

		$fp = fsockopen($hostname, $url['port'], $errno, $errstr);
		fwrite($fp, $request);
		$response = '';
		while (!feof($fp)) {
			$response .= fgets($fp, 1024);
		}
		return $response;
	}
}?>