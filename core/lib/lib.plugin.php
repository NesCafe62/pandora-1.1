<?php
namespace core;
defined ("CORE_EXEC") or die('Access Denied');

use \libs;
use \core;
use \debug;

use \console;

libs::load('static');

use \plugins;

use \scripts;
use \styles;

use \strings;

abstract class plugin extends \base_static {

	// [Section: main]
	
	// prevent cunstruction
	final private function  __construct() { }


	// onBeforeRouting
	// onAfterRouting


	// onBeforeRoute
	// onAfterRoute


	// [Section: events]
	
	/* const DEFAULT_PRIORITY = 10;

	final public static function registerEvent($event_name, $priority = self::DEFAULT_PRIORITY) {
		\plugins::registerEvent(self::name(),$event_name,$priority);
	}

	final public static function registerEvents($events) {
		foreach ($events as $key => $val) {
			if (is_numeric($key)) {
				list($event,$priority) = xplod2(':',$val);
				if ($priority === '') $priority = self::DEFAULT_PRIORITY;
			} else {
				$event = $key;
				$priority = $val;
			}
			\plugins::registerEvent(self::name(),$event,$priority);
		}
	} */

	const DEFAULT_EVENT_PRIORITY = 10;

	final public static function registerEvent($event, $priority = self::DEFAULT_EVENT_PRIORITY) {
		plugins::registerEvent(self::name(),$event,$priority);
	}

	final public static function registerEvents($events) {
		foreach ($events as $key => $val) {
			if (is_string($key)) {
				$event = $key;
				$priority = $val;
			} else {
				list($event,$priority) = extend_arr(explode(':',$val),2); // xplod2(':',$val);
				if ($priority === '') $priority = self::DEFAULT_PRIORITY;
			}
			plugins::registerEvent(self::name(),$event,$priority);
		}
	}

	final public static function hasEvent($event) { // self::triggerEvent()
		// $event_method = 'on'.ucfirst($event);

		return plugins::hasPluginEvent(self::name(),$event);
	}
	
	final public static function triggerEventArgs($event, $params = array()) { // self::triggerEvent() // , $params = null
		$event_method = 'on'.ucfirst($event);

		$plg_name = self::name();
		if (!self::has_method($event_method,$plg_name)) {
			trigger_error(debug::_('PLUGIN_TRIGGER_EVENT_NOT_FOUND',$plg_name.'::'.$event_method),debug::WARNING);
			return false;
		}

		/* $msg = array(
			'label' => $label,
			'message' => $dump,
			// 'level' => 'notice',
			'file' => $file,
			'line' => $line
		); */
		
		debug::addLog(array(
			'label' => $plg_name,
			'message' => $event_method
		), 'event');
		
		return self::call_method($event_method,$params,$plg_name);
	}

	final public static function triggerEvent($event) { // self::triggerEvent() // , $params = null
		$event_method = 'on'.ucfirst($event);

		// $params = func_get_args_ref();
		$params = func_get_args();
		array_shift($params);
		
		$plg_name = self::name();
		if (!self::has_method($event_method,$plg_name)) {
			trigger_error(debug::_('PLUGIN_TRIGGER_EVENT_NOT_FOUND',$plg_name.'::'.$event_method),debug::WARNING);
			return false;
		}
		// var_dump($params);
		// return call_user_func_array($plg_name.'::'.$event_method,$params);
		// $params2 = $params;
		// return $res;
		
		debug::addLog(array(
			'label' => $plg_name,
			'message' => $event_method
		), 'event');
		
		return self::call_method($event_method,$params,$plg_name);
		// return true;
	}

	final public static function parentName($name = '', $with_namespace = true) {
		if ($name == '') $name = self::name();
		$parent_name = get_parent_class($name);
		if (!$with_namespace) {
			$parent_name = self::cut_namespace($parent_name);
		}
		return $parent_name;
	}

	final public static function getEvents() {
		$plg_name = self::name();

		$legacy_plugins = self::legacy_plugins($plg_name);
		$events = array();
		foreach($legacy_plugins as $plg) {
			if (isset($plg::$registerEvents)) {
				foreach($plg::$registerEvents as $event) {
					list($event,$priority) = extend_arr(explode(':',$event),2);
					if ($priority === '') {
						$priority = self::DEFAULT_EVENT_PRIORITY;
					} else {
						$priority = intval($priority);
					}
					$events[$event] = $priority;
				}
			}
		}
		// asort($events);
		return $events;
	}

	final public static function getVars() {
		$plg_name = self::name();

		$legacy_plugins = self::legacy_plugins($plg_name);
		$vars = array();
		foreach($legacy_plugins as $plg) {
			if (isset($plg::$set_vars)) {
				$vars = extend($plg::$set_vars, $vars);
			}
		}
		
		return $vars;
	}

	final public static function dumpVars() {
		$plg_name = self::name();

		$vars = '';
		foreach (self::_vars() as $key => $val) {
			// $dump = str_replace('  ','    ',strings::dump($val));
			$dump = strings::dump($val);
			$vars .= '<div class="row"><span class="label">'.$key.':</span><pre>'.$dump.'</pre></div>';
		}

		$caller_level = 1;
		$m = debug::get_caller_method($caller_level);
		$file = '/'.remove_left($m['file'],core::base_dir());
		$line = $m['line'];
		
		debug::addLog(array(
			'message' => '<div class="row">plugin \''.$plg_name.'\': vars</div>'.$vars,
			'file' => $file,
			'line' => $line
		), 'console');
	}

	final public static function getRouting() {
		$plg_name = self::name();
		
		$legacy_plugins = self::legacy_plugins($plg_name);
		$routes = array();
		foreach($legacy_plugins as $plg) {
			$plugin_method = $plg.'::routing';
			if ( is_callable($plugin_method, false, $callable_name) && ($callable_name === $plugin_method) ) {
				$ref = new \ReflectionMethod($plg, 'routing');
				$declared_plg = $ref->getDeclaringClass()->name;
				if ($plg === $declared_plg) {
					$routes = $plg::routing($routes);
				}
			}
		}
		return $routes;
	}

	final private static function getActions() {
		$plg_name = self::name();
		
		$legacy_plugins = self::legacy_plugins($plg_name);
		$actions = array();
		foreach($legacy_plugins as $plg) {
			$plugin_method = $plg.'::actions';
			if ( is_callable($plugin_method, false, $callable_name) && ($callable_name === $plugin_method) ) {
				$ref = new \ReflectionMethod($plg, 'actions');
				$declared_plg = $ref->getDeclaringClass()->name;
				if ($plg === $declared_plg) {
					$actions = $plg::actions($actions);
				}
			}
		}
		return $actions;
	}

	private static $plugins_actions = array();

	final public static function initActions() {
		$plg_name = self::name();
		
		// self::$plugins_actions[$plg_name] = $plg_name::getActions();
		$actions = array();
		foreach ($plg_name::getActions() as $action_name => $action) {
			list($name, $modifiers) = extend_arr(split_str(':',$action_name),2);
			$modifiers = explode(':',$modifiers);

			// $is_public = ($modifier === 'public');
			// $is_ajax = ($modifier === 'ajax');
			$is_public = in_array('public',$modifiers);
			$is_ajax = in_array('ajax',$modifiers);
			$actions[$name] = array(
				$action, $is_public, $is_ajax
			);
		}

		self::$plugins_actions[$plg_name] = $actions;
	}

	// предобработка массива action-ов с учетом модификаторов доступа
	final private static function list_actions($actions) {
		$res = array();
		
		foreach ($actions as $action_name => $value) {
			$set_func_name = false;
			if (is_numeric($action_name)) {
				$action_name = $value;
				$set_func_name = true;
			}
			list($name, $modifier) = extend_arr(split_str(':',$action_name),2);
			if ($set_func_name) {
				$value = '';
				foreach(explode('.',$name) as $segment) {
					if ($value !== '') $segment = ucfirst($segment);
					$value .= $segment;
				} 
			}
			$res[$name] = array($action_name, $value);
		}
		
		return $res;
	}
	
	final protected static function extendActions($actions, $parent_actions) {
		$list_actions = extend(
			self::list_actions($actions),
			self::list_actions($parent_actions)
		);
		
		$res = array();
		foreach ($list_actions as $name => $action) {
			list($action_name, $value) = ($action);
			$res[$action_name] = $value;
		}
		
		return $res;
	}

	final public static function plugin_name() {
		$name = get_called_class();
		$name = remove_left($name,'core\\');
		return $name;
	}
	
	final public static function action($action, $params = array(), $check_public = false) {
		$plg_name = self::plugin_name();

		array_unshift($params,$plg_name);

		if ( !isset(self::$plugins_actions[$plg_name]) || !isset(self::$plugins_actions[$plg_name][$action]) ) {
			if (debug::getMode()) {
				trigger_error(debug::_('PLUGIN_ACTION_PLUGIN_HAS_NOT_ACTION',$plg_name.'/'.$action),debug::WARNING);
			}
			return null;
		}
		
		list($func, $is_public, $is_ajax) = self::$plugins_actions[$plg_name][$action];
		if ($check_public && !$is_public) {
			if (debug::getMode()) {
				trigger_error(debug::_('PLUGIN_ACTION_HAS_PRIVATE_ACCESS',$plg_name.'/'.$action),debug::WARNING);
			}
			return null;
		}
		if ($is_ajax && !core::is_ajax()) {
			if (debug::getMode()) {
				trigger_error(debug::_('PLUGIN_ACTION_HAS_AJAX_ACCESS',$plg_name.'/'.$action),debug::WARNING);
			}
			return null;
		}

		if (plugins::hasEvent('actionAccess')) {
			// check permission access to action
			
			$access = false;
			plugins::triggerEvent('actionAccess', array($plg_name.'/'.$action, &$access));
			if (!$access) {
				trigger_error(debug::_('PLUGIN_ACTION_PERMISSION_ACCESS_DENIED',$plg_name.'/'.$action),debug::WARNING);
				return null;
			}
			
		}

		if (is_string($func)) {
			
			if (strpos($func,':') !== false) {
				// self::triggerEvent('routeMethod',array(&$plg, &$routing));
				list($plg_name,$action_method) = split_str(':',$func);
				$action_method = 'action'.ucfirst($action_method);
			}
			else{
				// $routing_method = 'route'.ucfirst($routing);
				$action_method = 'action'.ucfirst($func);
			}
			
			// $action_method = 'action'.ucfirst($func);
			
			/* $action_method = 'action';
			foreach(explode('.',$func) as $segment) {
				$action_method .= ucfirst($segment);
			} */
			
			if (!$plg_name::has_method($action_method, $plg_name)) {
				trigger_error(debug::_('PLUGIN_ACTION_METHOD_NOT_EXISTS',$plg_name.'::'.$action_method),debug::WARNING);
				return null;
			}

			debug::addLog(array(
				'label' => $plg_name,
				'message' => $action_method
			), 'action');
			
			$res = $plg_name::call_method($action_method, $params, $plg_name);
			
		} else if (is_function($func)) {
			// лямбда-функция
			debug::addLog(array(
				'label' => $plg_name,
				'message' => 'action: \'lambda_function\''
			), 'action');
			
			$res = call_user_func_array($func, $params); // $func($params); // $route_vars); // call action
			
		} else {
			trigger_error(debug::_('PLUGIN_ACTION_CALLABLE_EXPECTED',$plg_name.' '.$func),debug::WARNING);
			return null;
		}

		return $res;
	}
	

	final protected static function legacy_plugins($plg) {
		$legacy_plugins = array();
		while (($plg !== 'core\plugin') && ($plg !== null)) {
			array_unshift($legacy_plugins, $plg);
			$plg = self::parentName($plg);
		}
		return $legacy_plugins;
	}

	final public static function path($plg_name = null) {
		if ($plg_name === null) {
			$plg_name = self::name();
		}
		return self::get('path','',$plg_name).'/';
	}

	final public static function res_path($plg_name = null) {
		if ($plg_name === null) {
			$plg_name = self::name();
		}
		$app = core::app();
		return $app::get('res_path').'/'.$plg_name.'/';
	}

	final public static function routeAction($action) {
		return array( 'action' => self::getAction($action) );
	}

	final public static function getAction($action) {
		return str_replace('\\','.',self::get('alias')).'/'.$action;
	}
	
	final public static function session() {
		$plugin = self::plugin_name();
		return $plugin::get('session');
	}

	private static function render_template() {
		plugins::triggerEvent('renderTemplate',array(self::$render_tpl_path));

		if (!file_exists(self::$render_tpl_path)) {
			trigger_error(debug::_('APP_TEMPLATE_NOT_FOUND',self::$render_tpl_name.', '.self::$render_tpl_path),debug::WARNING);
			return '';
		}

		$app = self::$app;

		ob_start();
		require(self::$render_tpl_path);
		return ob_get_clean();
	}


	private static $render_view_path = '';
	private static $render_view_name = '';
	
	private static $last_params = null;
	
	private static $_key = '';
	private static $_val = '';

	final private static function renderView() {
		// self::$render_view_path = self::get('_view_path');
		plugins::triggerEvent('renderTemplate',array(self::$render_view_path));

		if (!file_exists(self::$render_view_path)) {
			trigger_error(debug::_('APP_TEMPLATE_NOT_FOUND',self::$render_view_name.', '.self::$render_view_path),debug::WARNING);
			return '';
		}

		self::$last_params = last(self::$view_params);
		if (is_array(self::$last_params)) {
			foreach (self::$last_params as $__key => self::$_val) {
				if (!in_array($__key ,array('app','plugin','parent'))) {
					$$__key = self::$_val;
				}
			}
		}

		$app = core::app();
		$plugin = self::name();
		$parent = self::parentName();
		
		ob_start();
		require(self::$render_view_path);
		return ob_get_clean();
	}
	
	private static $_inc_filename = null;
	private static $_syntax_msg = '';
	
	private static function _include() {
		//$namespace = self::get('plg_namespace');
		//require_once(self::get('plg_filename'));
//		$namespace = self::$_plg_namespace;

		/* self::$_syntax_msg = shell_exec('php -l '.self::$_inc_filename);
		if (strpos(self::$_syntax_msg,'Parse error') !== false) {
			$errors = explode("\n",trim(self::$_syntax_msg));
			array_pop($errors);
			foreach ($errors as $err) {
				debug::addLog(debug::parse_message($err));
			}
			return false;
		} */

		include_once(self::$_inc_filename);

		return true;
	}

	final public static function import($lib_name) { // lib/lib
		if (!ends_with($lib_name,'.php')) {
			$lib_name .= '.php';
		}
		$filename = self::findLegacy($lib_name);
		if ($filename === null) {
			return false;
		}
		
		self::$_inc_filename = $filename;
		
		$res = self::_include();

		self::$_inc_filename = null;
		
		if (!$res) {
			$plg_name = self::name();
			trigger_error(debug::_('PLUGIN_IMPORT_FILE_PARSE_SYNTAX',$plg_name.' '.$filename),debug::WARNING);
			return false;
		}
		// require_once($filename);

		return true;
	}

	// search file and if missed check in parent plugins
	final public static function findLegacy($filename) {
		$plg_name = self::name();
		$plg = $plg_name;
		$file_path = null;

		while (($plg !== 'core\plugin') && ($plg !== null)) {
			// trigger_error(debug::_('plg',$plg),debug::WARNING);
//			console::log($plg);
//			console::log(self::has('path',$plg));
			if (self::has('path',$plg)) {
				$path = self::path($plg);

				// trigger_error(debug::_('path',$path),debug::WARNING);
				
				$_file = $path.$filename; // .'.php';

				if (is_file($_file)) {
					$file_path = $_file;
					break;
				}
			}
			$plg = self::parentName($plg);
		}

		return $file_path;

//		if ($file === null) {
//			if (!$no_warning) trigger_error(\debug::_('PLUGIN_NOVIEW',$plg_name.'::view('.$view.')'),\debug::WARNING);
//			return '';
//		}
	}
	
	final public static function getConfig($var_name, $default = null) {
		return plugins::getConfig(self::plugin_name(), $var_name, $default);
	}

	final public static function lang($const_name) {
		// ($const_name);
		// $const_name = strtoupper(self::name(false).'_'.$const_name);
		$args = func_get_args();
		array_shift($args);
		$params = $args;

		if (isset($params[0]) && is_array($params[0])) {
			$params = $params[0];
		}

		return plugins::lang(self::name(),$const_name,$params); //$const_name;
	}

/* 	private static function _num_lang_const($number) { // $const_name,
		if ($number > 10 && $number < 20) {
			$x = 0;
		} else {
			$s = ($number % 10);
			if ($s >= 5 || $s == 0) {
				$x = 0;
			} else if ($s == 1) {
				$x = 1;
			} else {
				$x = 2;
			}
		}
		return $x;
	} */

	final public static function num_lang($const_name) {
		$args = func_get_args();
		array_shift($args);
		$params = $args;
		if (isset($params[0]) && !is_scalar($params[0])) {
			$params = $params[0];
		}

		$number = 0;
		if (isset($params[0])) {
			$number = $params[0];
		}
		if (is_array($const_name) && isset($const_name[1])) {
			list($const_name,$number) = $const_name;
		}

		$const_name = $const_name.'_'.strings::num_lang((int)$number); // self::_num_lang_const((int)$number); // $const_name,
		return plugins::lang(self::name(),$const_name,$params);
	}



	private static $view_params = array();

	final public static function param($param, $default = false) {
		$view_params = last(self::$view_params);
		//if ($view_params === null ||
		//	is_scalar($view_params) ||
		if (!is_array($view_params) ||
			!isset($view_params[$param])
		) {
			return $default;
		}
		return $view_params[$param];
	}

	final public static function params($defaults = null, $view_params = null) {
		if ($view_params === null) {
			$view_params = last(self::$view_params);
		}
		if (!is_array($defaults)) {
			return $view_params;
		}
		$res = array();
		foreach ($defaults as $key => $value) {
			if (isset($view_params[$key])) {
				$value = $view_params[$key];
			}
			$res[] = $value;
		}
		return $res;

/*		if ($params == null) {
			return last(self::$view_params);
		} else {
			$res = array();
			foreach ($params as $param => $default) {
				$res[] = self::param($param,$default);
			}
		}
		return $res; */
	}

	final public static function view($view, $params = array()) {
		$plg_name = self::name();

		self::$render_view_name = $view;
		
		$filename = 'views/'.$view.'.php';
		$tpl_filename = $filename;
		plugins::triggerEvent('templateGetName',array(&$tpl_filename));
		
		$view_path = self::findLegacy($tpl_filename);
		if ($view_path === null) {
			$view_path = self::findLegacy($filename);
		} else {
			$view_path = remove_right($view_path,$tpl_filename).$filename;
		}
		if ($view_path === null) {
			$plg_filename = self::path($plg_name).$filename;
			// trigger_error(debug::dumpCaller(2),debug::WARNING);
			trigger_error(debug::_('PLUGIN_VIEW_LEGACY_NOT_FOUND','"'.$plg_filename.'" '.$plg_name.'::view('.$view.')'),debug::WARNING);
			return '';
		}
		// plugins::triggerEvent('beforeTemplate',array(&$view_path));
		
		self::$view_params[] = $params;
		self::$render_view_path = $view_path;
		// self::set('_view_path',$view_path);
		// debug::timer_start('plugin:'.$plg_name.'.view:'.$view_path);
		$html = self::renderView();
		// debug::timer_stop('plugin:'.$plg_name.'.view:'.$view_path, 'plugin '.$plg_name.'::view('.$view.')');
		// self::clear('_view_path');
		self::$render_view_path = '';
		array_pop(self::$view_params);

		return $html;
	}
	
	final public static function script($script) {
		$filename = 'js/'.$script;
		$script_path = self::findLegacy($filename);
		if ($script_path === null) {
			$plg_name = self::name();
			$script_path = self::path().$filename;
			trigger_error(debug::_('PLUGIN_SCRIPT_NOT_FOUND','"'.$filename.'" '.$plg_name.'::style('.$script.')'),debug::NOTICE);
		}
		scripts::import('/'.$script_path);
	}
	
	final public static function style($style) {
		// $widget = last(self::$widgets);
		$filename = 'css/'.$style;
		$style_path = self::findLegacy($filename);
		if ($style_path === null) {
			$plg_name = self::name();
			$style_path = self::path().$filename;
			trigger_error(debug::_('PLUGIN_STYLE_NOT_FOUND','"'.$filename.'" '.$plg_name.'::style('.$style.')'),debug::NOTICE);
		}
		styles::import('/'.$style_path);
	}

}
