<?php
namespace core;
defined ("CORE_EXEC") or die('Access Denied');

use \libs;
use \core;
use \debug;
use \files;
use \head;

use \plugins;
use \scripts;
use \styles;

libs::load('static');

abstract class app extends \base_static {

	// [Section: main]

	protected static $default_template = 'main';

	protected static $route_strict = false;

	// protected static $session_prefix = '';

	// protected static $config = 'app/config.ini';
	
	private static $config_vars = array();
	
	// prevent cunstruction
	final private function  __construct() { }

	final public static function load($app_base,$app_name,$app_path) {
		$app_base = self::get_static('app_base',$app_base);

		self::set('app_base',$app_base);
		self::set('app_name',$app_name);
		// self::set('app_path',$app_path.'/');
		self::set('app_path',$app_path);

		$session_prefix = self::get_static('session_prefix',$app_name);
		self::set('session_prefix',$session_prefix);

		$config_path = self::get_static('config',$app_path.'config.ini');
		self::set('config_path',$config_path);

		$res_path = self::get_static('res_path',$app_path.'res');
		self::set('res_path',$res_path);
		
		head::base($app_path);
		// head::base(remove_right($app_path,'/'));
		self::init_icon(self::get_static('icon',''));
		self::load_config();
	}

	
	public static function init_icon($icon = '') {
		if ($icon == '') {
			$app_path = self::get('app_path');
			$icon_path = $app_path.'/img/favicon.png';
			if (!is_file($icon_path)) return;
			$icon = '/'.$icon_path;
		}
		if ($icon) {
			if (is_array($icon)) {
				foreach ($icon as $_icon) {
					head::icon($_icon);
				}
			} else {
				head::icon($icon);
			}
		}
	}

	final public static function hasEvent($event) {
		$event_method = 'on'.ucfirst($event);
		$app_name = self::name();
		return self::has_method($event_method,$app_name);
	}

	final public static function triggerEventArgs($event, $params = array()) {
		$event_method = 'on'.ucfirst($event);

		$app_name = self::name();
		if (!self::has_method($event_method,$app_name)) {
			trigger_error(debug::_('APP_TRIGGER_EVENT_NOT_FOUND',$app_name.'::'.$event_method),debug::WARNING);
			return false;
		}

		debug::addLog(array(
			'label' => 'app.'.$app_name,
			'message' => $event_method
		), 'event');

		return self::call_method($event_method,$params,$app_name);
	}

	final public static function triggerEvent($event) {
		$event_method = 'on'.ucfirst($event);

		$params = func_get_args();
		array_shift($params);
		
		$app_name = self::name();
		if (!self::has_method($event_method,$app_name)) {
			trigger_error(debug::_('APP_TRIGGER_EVENT_NOT_FOUND',$app_name.'::'.$event_method),debug::WARNING);
			return false;
		}
		
		debug::addLog(array(
			'label' => 'app.'.$app_name,
			'message' => $event_method
		), 'event');
		
		return self::call_method($event_method,$params,$app_name);
	}


	public static function load_config() {
		$app_name = self::get('app_name');
		$app_path = self::get('app_path');
		$config_file = self::get('config_path'); // self::get_static('config',$app_path.'config.ini');
		if (!is_file($config_file)) {
			trigger_error(debug::_('APP_LOAD_CONFIG_FILE_NOT_FOUND',$app_name.':'.$config_file),debug::WARNING);
			return false;
		}
		$config_vars = files::read_ini($config_file);
		self::$config_vars[$app_name] = $config_vars;
		// self::set('config',$config_vars);
	}
	
	public static function config_get($var, $default = '') {
		$app_name = self::get('app_name');
		if (!isset(self::$config_vars[$app_name])) {
			trigger_error(debug::_('APP_CONFIG_GET_CONFIG_NO_SUCH_KEY',$app_name),debug::WARNING);
			return $default;
		}
		if (isset(self::$config_vars[$app_name][$var])) {
			return self::$config_vars[$app_name][$var];
		} else {
			return $default;
		}
	}
	
	public static function render() {
		// $main_tpl = extend(self::get('default_tpl'),'main');
		// $main_tpl = 'main';
		// $plg_name = self::name();
		// $plg_name::$default_template;
		$main_tpl = self::get_static('default_template','');
		return core::template(self::get('app_name').':'.$main_tpl);
	}

	public static function template($tpl = '') {
		$_tpl = $tpl;
		if ($tpl === '') {
			$tpl = self::get('template','');
			if ($tpl === '') {
				// no template set
				return '';
			}
		}
		
		if (strpos($tpl,':') === false) {
			$tpl = self::get('app_name').':'.$tpl;
		}
		return core::template($tpl);
	}

	// app: app/app.tasks ->
	// $plg = 'tabs';
	// path: 'app/app.tasks/ plugins/tabs/plugin.tabs.php'

	// app: app ->
	// $plg = 'tabs';
	// path: 'app/ plugins/tabs/plugin.tabs.php'
	public static function importPlugin($plg) {
		// $app_path = self::get('app_path');
		$app_name = self::get('app_name');
		plugins::import($app_name.':'.$plg);
	}

	final public static function script($script) {
		$filename = 'js/'.$script;
		scripts::import('/'.self::get('app_path').$filename);
	}
	
	final public static function style($style) {
		$filename = 'css/'.$style;
		styles::import('/'.self::get('app_path').$filename);
	}



/*	public static function accessEvent() {
		return true;
	}

	protected static function accessActions() {
		return array(
			'plg:auth' => array(
				'action:action1' => function() {
					;
				},
				'action:action2' => function() {
					;
				}
			)
		);
	}
	
	// plugin function
	public static function accessActions() {
		return array(
			'action:action1' => function() {
				;
			}
		);
	}
	//

	public static function actionAccessEvent($plg,$action) {
		$actions = self::call('accessActions');
		if (is_scalar($actions)) {
			trigger_error(\debug::_('APP_WRONG_ACTIONS',$),\debug::WARNING); // trigger_error('accessActions must return array');
			return false;
		}
		
		if (isset($actions[$plg]) && isset($actions[$plg][$action])) {
			// use plugin settings
			return plugins::accessAction($plg,$action);
		} else {
			// use default
			// trigger_error(\debug::_('APP_NOTEMPLATE',$),\debug::WARNING); // trigger_error('action not found');
		}
	} */

}
