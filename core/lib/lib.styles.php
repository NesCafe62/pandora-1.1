<?php
defined ("CORE_EXEC") or die('Access Denied');

// styles::add();

// styles::import('dsfdf.less');
// styles::css();
// styles::less();

// styles::import('app/css/template.less');

class styles {

	private static $styles = array();
	private static $raw_styles = array();

	private static $style_files = array();

	public static function add($style) {
		self::$raw_styles[] = $style;
	}

	/* public static function style($style, $context = 'app') {
		$arr = explode('.',$style);
		$ext = array_pop($arr);
		$s = implode('.',$arr);
		$path = str_replace('.','/',$context).'/'.$s;
		if ($ext == 'less') {
			\lessc::ccompile($path.'.less',$path.'.css');
		}
		$style = '<link rel="stylesheet" type="text/css" href="'.$path.'.css"/>';
		self::add($style);
		return '';
	} */

	public static function getStyles() {
		ksort(self::$styles);
		$styles_html = '';
		foreach (self::$styles as $p => $styles) {
			$styles_html .= implode('',$styles);
		}
		$styles_html .= implode('',self::$raw_styles);
		return $styles_html;
	}

	private static function add_style($filename, $priority = '') {
		if (isset(self::$style_files[$filename])) {
			return;
		}
		
		if ($priority && is_numeric($priority)) {
			$priority = (int)$priority;
		} else {
			switch ($priority) {
				// case '':
				default:
					$priority = 0;
					break;
				case 'app':
					$priority = 5;
					break;
				case 'core':
					$priority = 10;
					break;
			}
		}
		
		self::$style_files[$filename] = true;
		$style = '<link rel="stylesheet" type="text/css" href="'.$filename.'"/>';
		if (!isset(self::$styles[$priority])) self::$styles[$priority] = array();
		self::$styles[$priority][] = $style;
	}
	

	private static function _find_incs($filename) {
		$path = pathinfo($filename, PATHINFO_DIRNAME);
		
		$lines = file_get_contents($filename,false,null,0,300);
		
		$lines = preg_replace('#//.*?(?=\n)#','',$lines);
		if (strpos($lines,'/*') !== false) {
			$lines = preg_replace('#/\*(?:[^*]*(?:\*(?!/))*)*\*/#','',$lines);
		}

		preg_match_all('#@import.*?(url\("?|")([^")\n]*)#',$lines,$matches,PREG_SET_ORDER);
		
		$includes = array();
		
		foreach ($matches as $match) {
			$inc = $match[2];
			if (!ends_with($match[1],'"')) $inc = trim($inc);
			
			if ($inc !== '') {
				if (!starts_with($inc,'/')) {
					$inc = $path.'/'.$inc;
				}
				
				if (is_file($inc)) {
					$inc = remove_left(realpath($inc),core::base_dir());
					$includes[] = $inc;
				}
			}
		}
		
		return $includes;
	}
	
	private static function compile_less($filename) {
		$includes = self::_find_incs($filename);
		$force_compile = false;
		$update_time = false;

		$filetime = filemtime($filename);

		while ( !$force_compile && (count($includes) > 0) ) {
			$incs = array();
			foreach ($includes as $inc) {
				$inc_time = filemtime($inc);
				if ($inc_time > $filetime) {
					$force_compile = true;
					$update_time = true;
					break;
				}
				$incs = array_merge($incs, self::_find_incs($inc));
			}
			if (!$force_compile) {
				$includes = $incs;
			}
		}
		
		if ($force_compile) {
			$css_file = less::fcompile($filename);
			if ($update_time) {
				// console::log($filename);
				touch($filename, $inc_time + 1);
			}
		} else {
			$css_file = less::ccompile($filename);
		}
		
		return $css_file;
	}
	
	// styles::import('template.less');
	public static function import($filename, $priority = '') {
		$base = '';
		if (starts_with($filename,'/')) {
			$filename = remove_left($filename,'/');
			$base = '/';
		}

		$ext = getExtension($filename);
		if ($ext === 'less') { // ends_with($filename,'.less')) {
			$css_filename = $base.remove_right($filename,'.less').'.css';
			if (isset(self::$style_files[$css_filename])) {
				return;
			}
			if (!is_file($filename)) {
				trigger_error(debug::_('STYLES_IMPORT_FILE_NOT_EXISTS',$filename),debug::WARNING);
				self::add_style($css_filename, $priority);
				return;
			}
			$filename = self::compile_less($filename); // less::ccompile($filename);
		} else if ($ext !== 'css') { // !ends_with($filename,'.css')) {
			trigger_error(debug::_('STYLES_IMPORT_WRONG_EXTENSION',$filename),debug::WARNING);
			return;
		}
		// if (!is_file($filename)) { должен ругаться
		self::add_style($base.$filename, $priority);
		return;
	}

	public static function css($filename, $priority = '') {
		$ext = getExtension($filename);
		if ($ext !== 'css') {
			trigger_error(debug::_('STYLES_CSS_WRONG_EXTENSION',$filename),debug::WARNING);
			return;
		}
		self::add_style($filename, $priority);
		return;
	}

	public static function less($filename, $priority = '') {
		$ext = getExtension($filename);
		if ($ext !== 'less') {
			trigger_error(debug::_('STYLES_LESS_WRONG_EXTENSION',$filename),debug::WARNING);
			return;
		}
		if (!is_file($filename)) {
			trigger_error(debug::_('STYLES_LESS_FILE_NOT_EXISTS',$filename),debug::WARNING);
			$css_filename = remove_right($filename,'.less').'.css';
			self::add_style($css_filename, $priority);
			return;
		}
		$filename = self::compile_less($filename); // $filename = less::ccompile($filename);
		self::add_style($filename, $priority);
		return;
	}

}
