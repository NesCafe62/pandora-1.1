<?php
defined ("CORE_EXEC") or die('Access Denied');

class scripts {

	private static $scripts = array();

	private static $script_files = array();

	public static function add($script) {
		self::$scripts[] = $script;
	}
	
	
	// private static $oldest_filetame = null;
	
	private static function _find_incs($filename, $check_time = false) {
		$filename = remove_left($filename,'/');
		if (!is_file($filename)) {
			return false;
		}
		
		/* if ($check_time) {
			$file_time = filemtime($src);
			if (self::$oldest_filetame === null || $file_time > self::$oldest_filetame) {
				self::$oldest_filetame = $file_time;
			}
		} */
		
		$lines = file_get_contents($filename,false,null,0,300);
		
		preg_match("/#required\s*{([^}]*)}/i",$lines,$matches);
		if (!$matches) return false;
		
		$l = trim(preg_replace('/\s*\/+\s+/',' ',$matches[1]));
		if ($l !== '') {
			$includes = explode(' ',$l);
			if (count($includes) > 0) {
				return $includes;
			}
		}
		
		/* $arr = explode(' ',$l);
		$includes = array();
		foreach($arr as $inc) {
			if ($inc !== '') $includes[] = $inc;
		}
		if (count($includes) > 0) {
			return $includes;
		} */
		return false;
	}

	private static function add_script($filename) {
		if (isset(self::$script_files[$filename])) {
			return;
		}

		$_filename = remove_left($filename,'/');
		if (is_file($_filename)) {
			$filename = '/'.$_filename;
		}
		
		self::$script_files[$filename] = true;
		
		if (!starts_with($filename,'http://') && !starts_with($filename,'//')) {
			$includes = self::_find_incs($filename);
			if ($includes) {
				foreach($includes as $inc) {
					// core:jquery.validate		>> core/js/jquery.validate.js
					// core.preloader ->
						// core:core.preloader	>> core/js/core.preloader.js
					// app:scripts				>> app/js/scripts.js
					// app.test:scripts			>> app/app.test/js/sripts.js
					// auth.test1:md5			>> auth\test1::findLegacy('js/md5.js')
					// widget.window:window		>> core.widgets/widget.window/js/widget.window.js
					
					if (starts_with($inc,'core.') && strpos($inc,':') === false) {
						$inc = 'core:'.$inc;
					}

					list($context, $file) = split_str(':',$inc);
					if ($context === 'core') {
						// core
						$include_file = 'core/js/'.$file.'.js';
					} else if (starts_with($context,'app')) {
						// app
						$include_file = core::app_path($context).'/js/'.$file.'.js';
					} else if (starts_with($context,'widget')) {
						$widget_name = remove_left($context,'widget.');
						$include_file = 'core.widgets/widget.'.$widget_name.'/js/widget.'.$file.'.js';
					} else {
						// plugin
						$plugin_name = str_replace('.','\\',$context);
						if (!class_exists($plugin_name)) {
							trigger_error(debug::_('SCRIPTS_ADD_SCRIPT_PLUGIN_NOT_FOUND',$plugin_name.' '.$inc),debug::WARNING);
							continue;
						}
						$script_file = 'js/'.$file.'.js';
						$include_file = $plugin_name::findLegacy($script_file);
						if ($include_file === null) {
							trigger_error(debug::_('SCRIPTS_ADD_SCRIPT_PLG_SCRIPT_NOT_FOUND',$plugin_name.' '.$script_file.' '.$inc),debug::WARNING);
							continue;
						}
					}
					
					if (file_exists($include_file)) {
						self::add_script('/'.$include_file);
					} else {
						trigger_error(debug::_('SCRIPTS_ADD_SCRIPT_FILE_NOT_EXISTS',$include_file.' '.$inc),debug::WARNING);
					}
				}
			}
		}
		
		$script = '<script type="text/javascript" src="'.$filename.'"></script>';
		self::$scripts[] = $script;
	}

	public static function import($filename) {
		$ext = getExtension($filename);
		if ($ext !== 'js') {
			trigger_error(debug::_('SCRIPTS_IMPORT_WRONG_EXTENSION',$filename),debug::WARNING);
			return '';
		}
		self::add_script($filename);
		return '';
	}


	public static function getScripts() {
		return implode('',self::$scripts);
	}



	private static $global_params = array();

	public static function getParams() {
		return self::$global_params;
	}

	public static function param($param, $value) {
		$param_path = explode('.',$param);
		$node = &self::$global_params;
		foreach($param_path as $segment) {
			if (!isset($node[$segment])) {
				$node[$segment] = array();
			}
			$node = &$node[$segment];
		}
		$node = $value;
	}
	
}
