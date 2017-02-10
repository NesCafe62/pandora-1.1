<?php
defined ("CORE_EXEC") or die('Access Denied');

abstract class base_static {
	private static $_statics = array();


	final public static function name($with_namespace = true) {
		$name = get_called_class();
		if (!$with_namespace) {
			$name = self::cut_namespace($name);
		}
		return $name;
	}

	final public static function cut_namespace($name) {
		$arr = explode('\\',$name);
		return array_pop($arr);
	}


	final public static function has_method($method, $name = '') {
		if ($name == '') $name = self::name();
		return method_exists($name,$method);
	}

	final public static function call_method($method, $params = array(), $name = '') {
		if ($name == '') $name = self::name();
		return call_user_func_array($name.'::'.$method,$params);
	}



	final public static function get_static($var, $default = null, $name = '') {
		if ($name == '') $name = self::name();
		if ( !isset($name::$$var) ) return $default;
		return $name::$$var;
	}



	final public static function set($var, $val, $name = '') {
		if ($name == '') $name = self::name();
		self::$_statics[$name][$var] = $val;
		return $val;
	}

	final public static function get($var, $default = null, $name = '') {
		if ($name == '') $name = self::name();
		if ( !isset(self::$_statics[$name][$var]) ) return $default;
		return self::$_statics[$name][$var];
	}

	final public static function _vars($name = '') {
		if ($name == '') $name = self::name();
		return self::$_statics[$name];
	}

	final public static function has($var, $name = '') {
		if ($name == '') $name = self::name();
		return isset(self::$_statics[$name][$var]);
	}

	final public static function clear($var, $name = '') {
		if ($name == '') $name = self::name();
		unset(self::$_statics[$name][$var]);
	}

}
