<?php
defined ("CORE_EXEC") or die('Access Denied');

class server {
	
	private static $_vars = null;

	public static function init() {
		// init variables only once
		if (self::$_vars !== null) return;
		
		foreach ($_SERVER as $key => $val) {
			self::$_vars[$key] = $val;
		}
	}

	public static function get($key, $no_warning = false) {
		$key = strtoupper($key);
		if (isset(self::$_vars[$key])) return self::$_vars[$key];
		// debug::err(debug::WARNING,'SERVER_KEY_DOESNT_EXIST',$key);
		if (!$no_warning) {
			trigger_error(debug::_('SERVER_KEY_DOESNT_EXIST',$key),debug::WARNING);
		}
		return '';
	}

}

class route {

//	private static $host = ''; // host name
//	private static $ip = '';
	private static $uri = '';
	private static $url = '';
	private static $request_params = ''; // request params string
	private static $request_params_array = array();

	public static function init() {
		$uri = server::get('request_uri');
		list($url,$request_params) = split_str('?',$uri);
//		self::$host = server::get('server_name'); // $_SERVER['SERVER_NAME'];
//		self::$ip = server::get('remote_addr'); // $_SERVER['REMOTE_ADDR'];
		
		self::$uri = $uri;
		self::$request_params = $request_params;
		self::$request_params_array = self::parse_url_params($request_params);
		self::$url = $url;
		
		$referer = server::get('http_referer',true);

//		if ($referer) { //isset($_SERVER['HTTP_REFERER'])) {
//			$ref = xplod('?',$referer); // $_SERVER['HTTP_REFERER']);
//			if ($pos = strpos($ref,self::$host)) {
//				self::$ref = substr($ref,$pos+strlen(self::$host));
//			} else {
//				self::$ref = $url;
//			}
//		}
//		self::$url = remove_right($url,'/');
//		self::app_base('');

		/* console::log(self::url(),'route::url()');
		console::log(self::url(''),"route::url('')");
		console::log(self::url('#'),"route::url('#')");
		console::log(self::url('page=1'),"route::url('page=1')");
		console::log(self::url('v=3'),"route::url('v=3')"); */
		// console::log(self::url(),'');

		return array($url,$request_params);
	}

	// url: /quanta/test/e1?v=2
	// base_url: /quanta
	// relative_url: /test/e1
	// routed_url: /test
	// url_params: v=2
	// echo <a href="'.route::app().'"></a>; // href="/quanta"
	// echo <a href="'.route::app('').'"></a>; // href="/quanta"
	// echo <a href="'.route::app('/').'"></a>; // href="/quanta"
	// echo <a href="'.route::app('/page/1').'"></a>; // href="/quanta/page/1"

	// echo <a href="'.route::base('/tasks/page/2').'"></a>; // href="/tasks/page/2"
	// echo <a href="'.route::_('/tasks/page/2').'"></a>; // href="/tasks/page/2"

	// echo <a href="'.route::url().'"></a>; // href="/quanta/test?v=2"
	// echo <a href="'.route::url('').'"></a>; // href="/quanta/test?v=2"
	// echo <a href="'.route::url('#').'"></a>; // href="/quanta/test/e1?v=2#"
	// echo <a href="'.route::url('page=1').'"></a>; // href="/quanta/test?v=2&page=1"
	// echo <a href="'.route::url('v=3').'"></a>; // href="/quanta/test?v=3"


	public static function app($path = '', $params = array()) {
		$path = remove_left(remove_right($path,'/'),'/');
		// $app = core::app();
		// return $app::get('app_base').$path;
		$app_base = core::app_base();
		if ($app_base !== '/') {
			$path = '/'.$path;
		}
		$url = $app_base.$path;
		if (is_string($params)) {
			$url .= '/?'.$params;
		} else {
			$url = self::build_url($url, $params);
		}
		return $url;
	}

	public static function base($path) {
		$path = '/'.remove_left(remove_right($path,'/'),'/');
		return $path;
	}

	public static function _($path) {
		return self::base($path);
	}

	public static function parse_url_params($url_params) {
		if ($url_params === '') {
			return array();
		}
		$arr = explode('&',$url_params);
		$params = array();
		foreach ($arr as $p) {
			list($key,$val) = split_str('=',$p);
			$params[$key] = $val;
		}
		return $params;
	}

	public static function parse_url($uri = '') {
		if ($uri === '') {
			$uri = self::$uri;
		}
		list($url,$url_params) = split_str('?',$uri);
		$params = self::parse_url_params($url_params);
		return array($url,$params);
	}

	public static function build_url($url,$params) {
		if (count($params) > 0) {
			$params_array = array();
			foreach ($params as $key => $val) {
				$params_array[] = $key.'='.$val;
			}
			$url = remove_right($url,'/').'/';
			$url .= '?'.implode('&',$params_array);
		}
		return $url;
	}

	// $rem_params: array of params need to be removed (param1_name, param2_name, ...) or string "param1_name param2_name ..."
	// $parsed_url: array($url, $params) url string and params in array (param_name => param_value)
	// if parsed_url === '': parsed_url = parse current url
	public static function url_removeParams($rem_params, $parsed_url = '') {
		if (is_scalar($rem_params)) {
			$rem_params = explode(' ',$rem_params);
		}
		if (!is_array($parsed_url)) {
			$parsed_url = route::parse_url();
		}
		list($url,$params) = $parsed_url;
		foreach ($rem_params as $param) {
			unset($params[$param]);
		}
		return self::build_url($url,$params);
	}
	
	public static function url($path = '', $remove_params = '') {
		// console::log($path,'path=');
		if (($path === '') || ($path === '#')) {
			return self::$uri.$path;
		}
		$params = $path;
		if (is_scalar($path)) {
			$params = self::parse_url_params($path); // explode('&',$path);
		}
//		var_dump($params);
//		var_dump(self::$request_params_array);
		$params = extend($params,self::$request_params_array);

		if ($remove_params) {
			if (is_scalar($remove_params)) {
				$remove_params = explode(' ',$remove_params);
			}
			if (count($remove_params) > 0) {
				foreach ($remove_params as $param) {
					unset($params[$param]);
				}
			}
		}
		
		// console::log($params,'params='); LOG URL PARAMS
		
		return self::build_url(self::$url,$params);
		/* if (is_scalar($path)) {
			self::$uri;
		} else {
			$params = implode('&',$path);
			if (strpos(self::$uri,'?') !== false) {
				;
			}
			self::$uri .= $params;
		} */
		// return ;
	}

}
