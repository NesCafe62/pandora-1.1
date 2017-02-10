<?php
defined ("CORE_EXEC") or die('Access Denied');

class head {

	private static $head = array();

	public static function add($line) {
		self::$head[] = $line;
	}

	// private static $doctype = 'DOCTYPE'; // html5

	//public static function setDocType($doc = 'HTML5') {
	//	self::$doctype = 'DOCTYPE';
	//}
	
	private static $favicons = array();
	
	private static function getIcons() {
		$icons = '';
		foreach (self::$favicons as $icon_path => $rel) { // $params) {
			// list($rel, $size) = $params;
			if ( ($rel == '') || ($rel === 'shortcut') ) {
				$rel = 'shortcut icon';
			} else if ($rel === 'apple' || $rel === 'touch') {
				$rel = 'apple-itouch-icon';
			}
			/* if ($size != '') {
				$size = ' size="'.$size.'"';
			} */
			
			$type = 'image/x-icon';
			$ext = getExtension($icon_path);
			if ($ext === 'png') {
				$type = 'image/png';
			}

			$icons .= '<link rel="'.$rel.'" href="'.$icon_path.'" type="'.$type.'">';
		}
		return $icons;
	}
	
	public static function icon($icon_path, $rel = '') { // , $size = '') {
		self::$favicons[$icon_path] = $rel; // array($rel, $size);
	}
	
	private static $title = '';
	
	public static function title($title) {
		self::$title = htmlspecialchars($title);
	}

	public static function docType() {
		// html5
		// '<!'.self::$doctype.'>'
		return '<!DOCTYPE HTML>';
	}

	// <meta content="text/html; charset=utf-8" http-equiv="Content-Type">

	private static $meta = array();

	public static function meta($header, $value) {
		self::$meta[$header] = $value; // '<meta content="'.$value.'" http-equiv="'.$header.'">';
	}

	private static $http_base = '/';
	
	public static function base($base) {
		if ($base == '') $base = '/';
		self::$http_base = $base;
	}
	
	public static function init() {
		// self::$http_base = '/';
		// self::$http_base = 'http://'.server::get('server_name').'/';
	}
	
	public static function getHead() {
		$head = '';
		$http_base = self::$http_base;
		if ( !starts_with($http_base,'//') && !starts_with($http_base,'http://') ) {
			$http_base = 'http://'.server::get('server_name').'/'.remove_left($http_base,'/');
		}
		if (!ends_with($http_base,'/')) {
			$http_base .= '/';
		}
		if (self::$title) {
			$head .= '<title>'.self::$title.'</title>';
		}
		$head .= '<base href="'.$http_base.'">';
		foreach (self::$meta as $header => $val) {
			$head .= '<meta content="'.$val.'" http-equiv="'.$header.'">';
		}
		$head .= implode('',self::$head);
		$head .= self::getIcons();
		$head .= styles::getStyles();
		return $head;
	}

}
