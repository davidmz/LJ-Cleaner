<?php
class LJ {
	public static function login($username, $hpassword, $args = array()) {
		$challenge = self::getchallenge();
		
		// пытаемся авторизоваться
		$res = self::request('login', self::auth($username, $hpassword, $args));
		return $res;
	}
	
	public static function postevent(
		$username,
		$hpassword,
		$usejournal,
		$subject,
		$body,
		$security = 'public', // or int-mask
		$date = null, // now
		$props = array()
	) {
		if (is_null($date)) $date = new DateTime();
		
		$args = array(
			'usejournal'	=> $usejournal,
			'subject'	=> $subject,
			'event'		=> $body,
			'year'		=> $date->format('Y'),
			'mon'		=> $date->format('m'),
			'day'		=> $date->format('d'),
			'hour'		=> $date->format('H'),
			'min'		=> $date->format('i'),
		);
		
		if (is_int($security)) {
			$args['security'] = 'usemask';
			$args['allowmask'] = $security;
		} else {
			$args['security'] = $security;
		}
		
		foreach ($props as $name => $value) $args['prop_'.$name] = $value;
		
		$res = self::request('postevent', self::auth($username, $hpassword, $args));
		return $res;
	}

	public static function getlastevent(
		$username,
		$hpassword,
		$usejournal
	) {
		$args = array(
			'usejournal'	=> $usejournal,
			'selecttype'	=> 'one',
			'itemid'		=> '-1',
		);
		
		$res = self::request('getevents', self::auth($username, $hpassword, $args));
		return $res;
	}
	
	public static function getlastevents(
		$username,
		$hpassword,
		$usejournal,
		$howmany
	) {
		$args = array(
			'usejournal'	=> $usejournal,
			'selecttype'	=> 'lastn',
			'howmany'		=> $howmany,
		);
		
		$res = self::request('getevents', self::auth($username, $hpassword, $args));
		return $res;
	}
	
	public static function delevent(
		$username,
		$hpassword,
		$usejournal,
		$itemid,
		$anum
	) {
		$args = array(
			'usejournal'	=> $usejournal,
			'subject'	=> '',
			'event'		=> '',
			'itemid'	=> $itemid,
			'anum'		=> $anum,
		);
		
		$res = self::request('editevent', self::auth($username, $hpassword, $args));
		return $res;
	}
	
	private static function auth($username, $hpassword, $args = array()) {
		$challenge = self::getchallenge();
		$args['user'] = $username;
		$args['auth_method'] = 'challenge';
		$args['auth_challenge'] = $challenge;
		$args['auth_response'] = md5($challenge.$hpassword);
		$args['ver'] = '1';
		return $args;
	}
	
	private static function getchallenge() {
		$res = self::request('getchallenge');
		return $res['challenge'];
	}

	private static function request($method, $args = array()) {
		$args['mode'] = $method;
		$args['ver'] = 1;
		
		$curl = curl_init('http://www.livejournal.com/interface/flat');
		curl_setopt_array($curl, array(
			CURLOPT_HEADER	=> false,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_CONNECTTIMEOUT	=> 5,
			CURLOPT_TIMEOUT	=> 5,
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=>	http_build_query($args),
		));
		$contents = curl_exec($curl);
		$info = curl_getinfo($curl);
		$error = curl_error($curl);
		curl_close($curl);
		
		if ($error) throw new LJConnectionError("[{$method}] HTTP error: {$error}");
		if ($info['http_code'] != 200) throw new LJConnectionError("[{$method}] Invalid HTTP code: {$info['http_code']}");
		
		// парсим ответ
		$lines = explode("\n", $contents);
		$response = array();
		for ($i=0; $i<count($lines); $i+=2) {
			if (!$lines[$i]) continue;
			$k = $lines[$i];
			$v = $lines[$i+1];
			$parts = preg_split('/_(\d+)(?:_|$)/', $k, -1,  PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			
			$current =& $response;
			foreach ($parts as $p) {
				if (is_numeric($p)) {
					if (!isset($current[$p])) $current[$p] = array();
					$current =& $current[$p];
				} else {
					if (!isset($current[$p])) $current[$p] = array();
					$current =& $current[$p];
				}
			}
			$current = $v;
			unset($current);
		}
		
		if (!isset($response['success'])) throw new LJProtocolError("[{$method}] LJ error: Unknown success status");
		if($response['success'] == 'FAIL') throw new LJProtocolError("[{$method}] LJ error: {$response['errmsg']}");
		if($response['success'] != 'OK') throw new LJProtocolError("[{$method}] LJ error: Unknown success status: {$response['success']}");
		
		return $response;
	}
}

class LJConnectionError extends Exception {}
class LJProtocolError extends Exception {}
