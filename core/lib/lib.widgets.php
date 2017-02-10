<?php
defined ("CORE_EXEC") or die('Access Denied');

// $widget::style();

// $widget_path;

class widgets { // extends base_static {

	private static $widget = null;
	
	private static $widget_stack = array();

	final private static function renderView() {
		$widget = 'widgets';
		$widget_name = self::$widget->name;
		// $widget_name = self::get('widget');
		ob_start();
		require(self::$widget->filename);
		// require(self::get('widget_path'));
		return ob_get_clean();
	}
	

	final public static function style($style) {
		$widget_path = self::$widget->path; // self::get('widget'); // last(self::$widgets);
		styles::import('/'.$widget_path.'css/'.$style); // '/core.widgets/widget.'.$widget.'/css/'.$style);
	}
	
	final public static function script($script) {
		$widget_path = self::$widget->path;
		scripts::import('/'.$widget_path.'js/'.$script);
	}

	final public static function param($param, $default = false) {
		$widget_params = self::$widget->params; // last(self::$widget_params);
//		if ($widget_params === null ||
//			is_scalar($widget_params) ||
		if (!is_array($widget_params) ||
			!isset($widget_params[$param])
		) {
			return $default;
		}
		return $widget_params[$param];
	}

	final public static function getParams() {
		return self::$widget->params;
	}

	final public static function params($defaults = null, $widget_params = null) {
		if ($widget_params === null) {
			$widget_params = self::$widget->params; // last(self::$widget_params);
		}
		if (!is_array($defaults)) {
			return $widget_params;
		}
		$res = array();
		foreach ($defaults as $key => $value) {
			if (isset($widget_params[$key])) {
				$value = $widget_params[$key];
			}
			$res[] = $value;
		}
		return $res;

//		if ($params == null) {
//			return $widget_params;
//		} else {
			// / * $res = array();
			// foreach ($params as $param => $default) {
			// 	$res[] = self::param($param,$default);
			// } * /
//		}
//		return $res;
	}

	final public static function render($widget_name, $params = array()) {
		$path = 'core.widgets/widget.'.$widget_name.'/';
		$filename = $path.'widget.'.$widget_name.'.php';
		if (!is_file($filename)) {
			trigger_error(debug::_('WIDGETS_RENDER_WIDGET_NOT_EXISTS','"'.$filename.'" widgets::render('.$widget_name.')'),debug::WARNING);
			return '';
		}
		
		$widget = (object)array(
			'name' => $widget_name,
			'path' => $path,
			'filename' => $filename,
			'params' => $params
		);
		
		self::$widget = $widget;
		self::$widget_stack[] = $widget;

		// debug::timer_start('widget:'.$widget_name);
		$html = self::renderView();
		// debug::timer_stop('widget:'.$widget_name, 'widget '.$widget_name.'::render()');
		
		array_pop(self::$widget_stack);
		if (!isset(self::$widget_stack[0])) {
			self::$widget = null;
		} else {
			self::$widget = last(self::$widget_stack);
		}
		
		return $html;
	}

}
