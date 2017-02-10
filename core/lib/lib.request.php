<?php
defined ("CORE_EXEC") or die('Access Denied');

class request {

	private static $vars_post = array();

	private static $vars_get = array();

	public static function init() {
		foreach ($_POST as $key => $val) {
			self::$vars_post[$key] = $val;
			unset($_POST[$key]);
		}
		
		foreach ($_GET as $key => $val) {
			self::$vars_get[$key] = $val;
			unset($_GET[$key]);
		}

		foreach ($_REQUEST as $key => $val) {
			unset($_REQUEST[$key]);
		}
	}

	public static function get($key, $default = null, $method = 'any', $params = null) {
		if (isset(self::$vars_post[$key]) && $method !== 'get') {
			$res = self::$vars_post[$key];
		} else if (isset(self::$vars_get[$key]) && $method !== 'post') {
			$res = self::$vars_get[$key];
		} else {
			return $default;
		}
		if (is_array($res)) {
			foreach ($res as &$r) {
				$r = self::filter_val($r,$params);
			}
		} else {
			$res = self::filter_val($res,$params);
		}
		return $res;
	}

	
	
	private static $regex_cache = array();
	
	private static function filter_regexp($allowed_chars, $allowed_other) { // $filter, 
		$all_chars = 'A-Z a-z 0-9 А-Я а-я ( ) , . : ; - + ! ? * / % № & \ < > \' _ " ` ~ | @ # $ ^ = [ ] { }'; // <- \' = '
		
		$key = ($allowed_other?'1 ':'0 ').$allowed_chars;
		
		if (!isset(self::$regex_cache[$key])) {
			
			$symbols = '';
			$arr = explode(' ',$all_chars);
			foreach ($arr as $char) {
				if ( (strpos($allowed_chars,$char) === false) xor !$allowed_other) {
					if (strlen($char) <= 1) {
						$char = str_replace(['\\','-','[',']','#'], ['\\\\','\-','\[','\]','\#'], $char);
					}
					$symbols .= $char;
				} else {
					$allowed_chars = str_replace($char,'',$allowed_chars);
				}
			}
			if ($allowed_other) {
				$regexp = '#['.$symbols.']#u';
			} else {
				$regexp = '#[^'.$symbols.']#u';
			}
			return $regexp;
			self::$regex_cache[$key] = $regexp;
			
		}
		
		return self::$regex_cache[$key];
	}
	
	private static function filter_val($val, $params = null) {
		$val_ = $val;
		// return $val;
		
		if (!is_array($params)) {
			if ($params) {
				$params = ['allow' => $params];
			} else {
				$params = [];
			}
		}
		$params = extend($params,[
			'allow' => 'A-Za-z0-9А-Яа-я(),.:;-+!?*/%№&\<>\'_"`~|@#$^=[]{}',
			'allow_other' => false,
			'html' => false,
			'html_attribs' => [],
			'spaces' => true
		]);
		$allow_chars = $params['allow'];
		$allow_other = $params['allow_other'];
		$allowed_tags = $params['html'];
		$allowed_attribs = $params['html_attribs'];
		$allow_spaces = $params['spaces'];
		$regexp = self::filter_regexp($allow_chars,$allow_other);
		
		if ($allowed_tags) {
			$val_ = preg_replace('/<script(\s+[^>]*)?>(.*?)<\/script>/is','',$val_);

			$val_ = strip_tags($val_,'<'.implode('><',$allowed_tags).'>');
			
			$val_ = preg_replace_callback('/([^<>]*[a-z][a-z0-9]*[^<>]*)\s+([a-z][a-z0-9-]*)\s*=\s*"[^"]*"(?=[^<>]*>)/', function ($matches) use ($allowed_attribs) {
				if (in_array($matches[2],$allowed_attribs)) {
					return $matches[0];
				} else {
					return $matches[1];
				}
			}, $val_);

		} else {
			$val_ = strip_tags($val_);
		}

		if (!$allow_spaces) {
			$val_ = preg_replace('/\s/','',$val_);
		}
		
		if (!$allow_other) {
			if ($allow_spaces) {
				$regexp = str_replace('#[^','#[^\s',$regexp);
			}
		}
		
		$filtered = preg_replace($regexp,'',$val_);
		return $filtered;
	}

	
	
	public static function getVars($key = '.*', $default = null, $method = 'post') { // << ,$default = null
		trigger_error(debug::_('request::getVars method is unsafe'),debug::WARNING);
		if ($method === 'post')
			$vars = self::$vars_post;
		else
 			$vars = self::$vars_get;

		$result = array();
		foreach ($vars as $var => $val) {
			if (preg_match('/'.$key.'/', $var))
				$result[$var] = $val;
		}
		return $result;
	}

	public static function getChecked($key, $default = array(), $method = 'post') {
		$var = self::get($key, null, $method);
		if ($var === null) {
			return $default;
		}
		
		$res = array();
		foreach ($var as $id => $val) {
			if ($val) {
				$res[] = $id;
			}
		}
		return $res;
	}

	public static function has($key, $method = 'any') {
		$res = false;
		if (isset(self::$vars_post[$key]) && $method !== 'get') {
			$res = true;
		} else if (isset(self::$vars_get[$key]) && $method !== 'post') {
			$res = true;
		}
		return $res;
	}

	public static function set($key, $val, $method = 'get') {
		if ($method === 'get') {
			self::$vars_get[$key] = $val;
		} else {
			self::$vars_post[$key] = $val;
		}
		return true;
	}

	private static function fileErrorMessage($code) {
		switch ($code) {
			case UPLOAD_ERR_INI_SIZE:
				$message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
				break;
			case UPLOAD_ERR_PARTIAL:
				$message = "The uploaded file was only partially uploaded";
				break;
			case UPLOAD_ERR_NO_FILE:
				$message = "No file was uploaded";
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message = "Missing a temporary folder";
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message = "Failed to write file to disk";
				break;
			case UPLOAD_ERR_EXTENSION:
				$message = "File upload stopped by extension";
				break;

			default:
				$message = "Unknown upload error";
				break;
		}
		return $message;
	}

	public static function getFiles($request_name, $default = false) {
		if (!isset($_FILES[$request_name])) {
			return $default;
		}

		if (!is_array($_FILES[$request_name])) {
			return array($_FILES[$request_name]);
		}
		
		$files = array();
		$keys = array_keys($_FILES[$request_name]);
		$n_files = count($_FILES[$request_name]['tmp_name']);

		if ($n_files === 0) {
			return $default;
		}
		
		for ($i = 0; $i < $n_files; $i++) {
			$file = array();
			$skip_file = false;
			foreach ($keys as $key) {
				$file[$key] = $_FILES[$request_name][$key][$i];
				if ($key === 'error') {
					$err = $file[$key];
					if ($err !== 0) {
						if ($err !== UPLOAD_ERR_NO_FILE) {
							trigger_error(debug::_('FILES_GET_FILES_UPLOAD_ERROR',self::fileErrorMessage($file[$key]),$request_name.'['.$i.']'),debug::WARNING);
						}
						$skip_file = true;
						continue;
					}
				}
			}
			if (!$skip_file) {
				$files[] = $file;
			}
		}
		
		return $files;
	}
	
	public static function getFile($request_name, $default = false) {
		if (!isset($_FILES[$request_name])) {
			return $default;
		}
		if (is_array($_FILES[$request_name]['tmp_name'])) {
			$keys = array_keys($_FILES[$request_name]);
			$n_files = count($_FILES[$request_name]['tmp_name']);

			if ($n_files === 0) {
				return $default;
			}
			
			$file = array();
			foreach ($keys as $key) {
				$file[$key] = $_FILES[$request_name][$key][0];
			}
			return $file;
		}
		if ($_FILES[$request_name]['error'] !== 0) {
			$err = $_FILES[$request_name]['error'];
			if ($err !== UPLOAD_ERR_NO_FILE) {
				trigger_error(debug::_('FILES_GET_FILE_UPLOAD_ERROR',self::fileErrorMessage($err),$request_name),debug::WARNING);
			}
			return $default;
		}
		return $_FILES[$request_name];
	}
	
	public static function hasFile($request_name) {
		return isset($_FILES[$request_name]);
	}
	
}
