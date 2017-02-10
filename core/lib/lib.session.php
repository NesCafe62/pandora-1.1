<?php
defined ("CORE_EXEC") or die('Access Denied');

class session {

	private static $started = false;

	public static function close() {
		if (self::$started) {
			session_destroy();
		}
	}

	public static function start() {
		if (!self::$started) {
			session_start();
			self::$started = true;
		}
	}

	public static function restart() {
		if (self::$started) {
			session_regenerate_id();
		}
		session_start();
		self::$started = true;
	}


	// private static $vars_session = array();
	
	public static function init() {
		self::start();
		/* foreach ($_SESSION as $key => $val) {
			self::$vars_session[$key] = $val;
			unset($_SESSION[$key]);
		} */
	}

//	public static function save() {
		/* foreach (self::$vars_session as $key => $val) {
			$_SESSION[$key] = $val;
		} */
//	}
	
	public static function _has($key) {
		if (!self::$started) self::init();

		// return isset(self::$vars_session[$key]);
		return isset($_SESSION[$key]);
	}
	
	public static function _get($key, $default = null) {
		if (!self::$started) self::init();

		// if (isset(self::$vars_session[$key])) {
		if (isset($_SESSION[$key])) {
			return $_SESSION[$key];
			// return self::$vars_session[$key];
		} else {
			return $default;
		}
	}

	public static function _set($key, $val) {
		if (!self::$started) self::init();

		// self::$vars_session[$key] = $val;
		$_SESSION[$key] = $val;
	}

	public static function _clear($key) {
		if (!self::$started) self::init();

		// unset(self::$vars_session[$key]);
		unset($_SESSION[$key]);
	}


	private $prefix;
	public function __construct($prefix) {
		$this->prefix = $prefix.'.';
	}

	public function has($key) {
		return self::_has($this->prefix.$key);
		// return isset(self::$vars_session[$this->prefix.$var]);
	}

	public function get($key, $default = null) {
		return self::_get($this->prefix.$key, $default);
	}

	public function set($key, $val) {
		self::_set($this->prefix.$key, $val);
	}

	public function clear($var) {
		self::_clear($this->prefix.$var);
	}


}
