<?php
defined ("CORE_EXEC") or die('Access Denied');

libs::load('plugin');

use core\plugin;

class plugins { // extends base_static {

	/* 
	public static function view($plg_name, $view, $no_warning = false) {
		if (!class_exists($plg_name)) {
			if (!$no_warning) trigger_error(debug::_('PLUGIN_NOTEXISTS',$plg_name),debug::WARNING);
			return '';
		}
		return self::call($plg_name,'view',array($view,$no_warning),$no_warning);
	} */

	public static function view($plg_name, $view, $params = array()) {
		if (!class_exists($plg_name)) {
			trigger_error(debug::_('PLUGIN_CLASS_NOTEXIST',$plg_name),debug::WARNING);
			return '';
		}

		$res = call_user_func_array($plg_name.'::view',array($view,$params));
		return $res;
		
		// return self::call($plg_name,'view',array($view,$no_warning),$no_warning);
	}

	private static $included_plugins = array();
	private static $plugins = array(); // plugins to init

	private static $_plg_filename = null;
	private static $_syntax_msg = '';
//	private static $_plg_namespace = null;

	private static $_plg_context = '';


	private static function _include() {
		//$namespace = self::get('plg_namespace');
		//require_once(self::get('plg_filename'));
//		$namespace = self::$_plg_namespace;

/*		self::$_syntax_msg = shell_exec('php -l '.self::$_plg_filename);
		if (strpos(self::$_syntax_msg,'Parse error') !== false) {
			$errors = explode("\n",trim(self::$_syntax_msg));
			array_pop($errors);
			foreach ($errors as $err) {
				debug::addLog(debug::parse_message($err));
			}
			return false;
		} */

		include_once(self::$_plg_filename);
		// include_once

		return true;
	}

	
	private static function _include_plugin($_plg_name, $plg, $plg_path, $plg_file, $namespace_to_core) {
		self::$_plg_filename = $plg_file;
		
		if (!self::_include()) {
			// $err = true; break;
			trigger_error(debug::_('PLUGIN_LOAD_FILE_PARSE_SYNTAX',$_plg_name.' '.$plg_file),debug::WARNING);
			return false;
		}
		
		if (!class_exists($_plg_name)) {
			trigger_error(debug::_('PLUGIN_LOAD_CLASS_NOT_FOUND',$_plg_name.' '.$plg_file),debug::WARNING);
			return false;
			// $err = true; break;
		}
		// if ($is_core && $namespace_to_core) {
		if ($namespace_to_core) {
			$plg_global = $plg_path.'/plugin.global.php';
			
			/* if ($plg_alias === 'plg.table') {
				var_dump($plg_global);
			} */
			
			if (!is_file($plg_global)) {
				$_global = '<?php'."\n";
				$_global .= 'defined ("CORE_EXEC") or die(\'Access Denied\');'."\n"."\n";
				$_global .= 'class '.$plg.' extends core\\'.$plg.' { }'."\n";
				// $_global .= 'use core\\'.$plg.';'."\n";
				file_put_contents($plg_global,$_global);
			}

			// console::log($plg,'plg_name');
			// console::log($plg_path,'set path');
			
			// when there is no app-plugin extends from this core-plugin
			// на тот случай когда от core не наследуется плагин в app
			plugin::set('path',$plg_path,$plg);
			
			self::$_plg_filename = $plg_global;
			if (!self::_include()) {
				trigger_error(debug::_('PLUGIN_LOAD_FILE_PARSE_SYNTAX',$_plg_name.' '.$plg_file),debug::WARNING);
				return false;
				// $err = true; break;
			}
		}
		
		return true;
	}

	// 
	private static function loadPlugin($plg_path, $plg_filename, $plg, $plg_alias, $namespace = '\\') { // , $plg_alias
		$plg_file = $plg_path.'/'.$plg_filename.'.php';
		$plg = str_replace('.','_',$plg);
		$plg_name = $plg;
		$namespace_to_core = ($namespace === 'core');
		if ($namespace_to_core) {
			$plg_name = 'core.'.$plg;
		}
		$arr = explode('.',$plg_path);
		$is_core = ($arr[0] === 'core');
		// $arr = explode('.',$plg);
		// $plg_name = array_pop($arr);
		// $plg_namespace = implode('\\',$arr).'\\';

		if (!is_file($plg_file)) {
			trigger_error(debug::_('PLUGIN_LOAD_FILE_NOT_FOUND',$plg_file),debug::WARNING);
			return false;
		}

		if (isset(self::$included_plugins[$plg_file])) {
			return true; // plugin already loaded
		}
		self::$included_plugins[$plg_file] = true;

		if (isset(self::$plugins[$plg])) {
			if ($is_core || (!$is_core && !self::$plugins[$plg]['is_core'])) {
				trigger_error(debug::_('PLUGIN_LOAD_PLUGIN_NAME_ALREADY_IN_USE',$plg),debug::WARNING);
				return false;
			}
		}

		$_plg_name = $plg;
		if (starts_with($plg_path,'core.plugins/')) {
			$_plg_name = 'core.'.$plg;
		}
		$_plg_name = str_replace('.','\\',$_plg_name);


		if (class_exists($_plg_name)) {
			$msg = debug::_('PLUGIN_LOAD_CLASS_NAME_ALREADY_IN_USE',$_plg_name);
			if (libs::class_loaded($_plg_name,$lib_filename)) {
				$msg = debug::_('PLUGIN_LOAD_CLASS_NAME_USED_IN_LIB',$lib_filename);
			} else if (core::app_exists($_plg_name,$app_filename)) {
				$msg = debug::_('PLUGIN_LOAD_CLASS_NAME_USED_IN_APP',$app_filename);
			}
			trigger_error($msg,debug::WARNING);
			return false;
		}
		
		plugin::set('path',$plg_path,$_plg_name);
		
		// plugin::set('alias',$plg_alias,$_plg_name);

		// console::log($_plg_name,'plg_name'); // LOG PLUGIN PATH
		// console::log($plg_path,'plg_path');
		// console::log($plg_alias,'plg_alias');
		
		$plg_params = array(
			'is_core' => $is_core,
			'name' => $plg_name,
			// 'namespace' => $plg_namespace,
			'path' => $plg_path,
			'alias' => $plg_alias
		);
		self::$plugins[$plg] = $plg_params;  // !!!



//		self::set('plg_filename',$plg_file);
//		self::set('plg_namespace',$namespace);


		// self::$_plg_filename = $plg_file;
		
		//  console::log($plg_file); // LOG PLUGIN PATH
/*		var_dump($plg_path); echo '<br/>';
		var_dump($plg_filename); echo '<br/>';
		var_dump($plg); echo '<br/>';
		var_dump($plg_alias); echo '<br/>';
		var_dump($plg_file); echo '<br/>';
		echo '<br/>'; */
		
//		self::$_plg_namespace = $namespace;

		$plg_context = self::$_plg_context;
		// console::log(split_left(':',$plg_alias));
		self::$_plg_context = split_left(':',$plg_alias);
		$included = self::_include_plugin($_plg_name, $plg, $plg_path, $plg_file, $namespace_to_core);
		self::$_plg_context = $plg_context;
		
		self::$_plg_filename = null;
		
		unset(self::$plugins[$plg]);
		
		if (!$included) {
			unset(self::$included_plugins[$plg_file]);
			// trigger_error(debug::_('PLUGINS_LOAD_PLUGIN_ERROR',$_plg_name,$plg_file),debug::ERROR);
			return false;
		}

		self::$plugins[$plg] = $plg_params; // !!!

	
//		$err = false;
		
		
		/* if ($err) {
			unset(self::$plugins[$plg]);
			unset(self::$included_plugins[$plg_file]);
			return false;
		} */
		
//		self::$_plg_namespace = null;

		// check class (with namespace) for existing

		return true;
	}

	// plugins::load('app');
	// array(
	//		'auth',
	//		'tabs'
	// );

	// check core.auth first and load if exist
	// auth.php -> plugins::import('app.tasks:tabs');
	
	// plugins::load(); // -> plugins::load('core');
	// array(
	//		'core.auth',
	//		'core.dev'
	// );
	// load not yet loaded plugins

	// init all plugins

	

	// path = 'app';
	// dir = 'app/ plugins/';

	// path = 'app.tasks';
	// dir = 'app/app.tasks/ plugins/';

	// path = '';
	// path = 'core';
	// dir = 'core.plugins/';
	public static function load($path = '') {
		if ($path === '') {
			$path = 'core';
		}
		$plg_prefix = 'plugin';
		$namespace = '\\';
		
		list($first_segment) = explode('.',$path);
		$is_app = ($first_segment === 'app');
		if ($is_app) {
			$app_alias = $path;
			$base_path = core::app_path($path).'/';
		} else {
			if ($path !== 'core') {
				trigger_error(debug::_('PLUGINS_LOAD_PATH_UNACCEPTABLE',$path),debug::WARNING);
				return false;
			}
			$plg_prefix = 'core.plugin';
			$namespace = 'core';
			$base_path = 'core.';
		}
		$dir = $base_path.'plugins';
		
		// $path = 'core/lib';
		$plugins = files::getFolders($dir,true);

		// $plg_prefix = 'plugin';
		$res = true;
		foreach ($plugins as $plg_path) {
			$arr = explode('/',$plg_path);
			$plg_name = array_pop($arr);
			
	// $plg = 'auth';
	// $path = 'core.plugins/auth/core.plugin.auth.php';

	// $plg = 'app:auth';
	// $path = 'app/plugins/auth/plugin.auth.php';

	// $plg = 'app.tasks:auth.tabs';
	// $path = 'app/app.tasks/ plugins/auth.tabs/ plugin.tabs.php';

			
			$plg_alias = $plg_name; // '11'; // !!!!

			$plg_filename = $plg_prefix.'.'.$plg_name;

			// $plg_file = $plg_path.'/'.$plg_filename;
			
			$r = true;
			if ($is_app) {
				$plg_alias = $app_alias.':'.$plg_name;

				$plg_core_filename = 'core.plugin.'.$plg_name;
				$plg_core_path = 'core.plugins/'.$plg_name;
				$is_in_core = is_dir($plg_core_path);
				
				if (is_file($plg_path.'/enabled.false')) {
					$r = false;
				} else if ($is_in_core) {
					if (is_file($plg_core_path.'/enabled.false')) {
						// app plugin disabled but core plugin enabled
						// you should disable core plugin instead if your app plugin extends from core plugin
						trigger_error(debug::_('PLUGINS_LOAD_APP_PLUGIN_DISABLED',$plg_core_path),debug::WARNING);
						$r = false;
					} else {
						$r = self::loadPlugin($plg_core_path,$plg_core_filename,$plg_name,$plg_alias); // ,$plg_alias);
					}
				}
			} else {
				/*
				// loading core plugin
				// possible should check if disabled but app plugin exists and enabled
				if (is_file($plg_path.'/enabled.false')) {
					;
				} */
				if (is_file($plg_path.'/enabled.false')) {
					$r = false;
				}
			}
			
			if ($r) {
				$res = self::loadPlugin($plg_path,$plg_filename,$plg_name,$plg_alias,$namespace) && $res; // ,$plg_alias
			}
		}

		return $res;
	}

	private static $plugin_langs = array();

/*	public static function multi_lang($plg_name, $const_name, $, $params = array()) {
	
		$params[0];
		return self::lang($plg_name,$const_name,$params);
	} */

	public static function lang($plg_name, $const_name, $params = array()) {
		/* $res = $language[$_var];
		if ($var_params !== '') {
			$arr = $var_params;
			array_unshift($arr,$res);
			$res = call_user_func_array('sprintf',$arr); */
						
		$const_name = strtoupper(plugin::cut_namespace($plg_name).'_'.$const_name);
		if (!isset(self::$plugin_langs[$plg_name]) || !isset(self::$plugin_langs[$plg_name][$const_name])) {
			$res = $const_name;
			if (isset($params[0])) {
				$res .= ' {'.implode(',',$params).'}';
			}
			return $res;
		}
		array_unshift($params,self::$plugin_langs[$plg_name][$const_name]);
		return call_user_func_array('sprintf',$params);
		// return self::$plugin_langs[$plg_name][$const_name];
	}


	private static function pluginLoadLang($plg_name) {
		$lang = core::lang(); // 'ru';
		$plg = $plg_name;
		
		$lang_path = '/lang/'.$lang.'.ini';
		
		$lang_files = array();
		while (($plg !== 'core\plugin') && ($plg !== null)) {
			if (plugin::has('path',$plg)) {
				$path = plugin::path($plg);
				$lang_file = $path.$lang_path;
				if (is_file($lang_file)) {
					array_unshift($lang_files,$lang_file);
					// $file_path = $_file;
				}
			}
			$plg = plugin::parentName($plg);
		}
		
		$plugin_langs = array();
		foreach($lang_files as $lang_file) {
			$consts = files::read_ini($lang_file);
			$plugin_langs = extend($plugin_langs,$consts);
		}
		return $plugin_langs;
	}

	private static function pluginLoadConfig($plg_name) {
		$plg = $plg_name::name(false);
		
		$config_path = core::get_app_path().'plugins.config';

		if (!is_dir($config_path)) {
			// files::createDir($app_path.'plugins.config');
			return array();
		}
		$config_file = $config_path.'/'.$plg.'.cfg';
		if (!is_file($config_file)) {
			return array();
		}
		$config = files::read_cfg($config_file);
		if (!$config) {
			return array();
		}
		return $config['config'];
	}

	/* public static function triggerEvent($plugin, $event = '', $params = array()) {
		;
	}

	private static function _triggerEvent($event, $params) {
		;
	}

	private static function _triggerPlgEvent($plugin, $event, $params) {
		;
	} */

	private static $plugins_events = array();

	private static $event_sort = true;

	private static function sortEventsPriority() {
		foreach (self::$plugins_events as $event => $plugins) {
			asort(self::$plugins_events[$event]);
		}
	}

	public static function registerEvent($plg_name, $event, $priority = plugin::DEFAULT_EVENT_PRIORITY) {
		if (!isset(self::$plugins_events[$event])) {
			self::$plugins_events[$event] = array();
		}
		self::$plugins_events[$event][$plg_name] = $priority;
		if (self::$event_sort) asort(self::$plugins_events[$event]);
	}

	public static function triggerEvent($event, $params = array()) {
		if (!isset(self::$plugins_events[$event])) { // no plugins declares this event
			return false;
		}
		$plugins = self::$plugins_events[$event];
		if (count($plugins) == 0) {
			return false;
		}

		$res = true;
		$is_single = (count($plugins) == 1);


		$event_method = 'on'.ucfirst($event);

		foreach ($plugins as $plg_name => $priority) {
			/* if (!$plg_name::has_method($event_method,$plg_name)) {
				trigger_error(debug::_('PLUGIN_TRIGGER_EVENT_NOT_FOUND',$plg_name.'::'.$event_method),debug::WARNING);
				$r = false;
			} else {
				debug::addLog(array(
					'label' => $plg_name,
					'message' => $event_method
				), 'event');
				
				$r = $plg_name::call_method($event_method,$params,$plg_name);
			}

			$res = $r && $res; */

			$r = $plg_name::triggerEventArgs($event,$params);
			if ($res === null) $res = true;
			
			if ($is_single) {
				$res = $r;
			} else {
				$res = $r && $res;
			}
			// $res = $plg_name::triggerEvent($event,$params) && $res;
		}
		return $res;
	}

	public static function hasEvent($event) {
		return isset(self::$plugins_events[$event]) && (count(self::$plugins_events[$event]) > 0);
	}

	public static function hasPluginEvent($plg_name, $event) {
		return isset(self::$plugins_events[$event]) && isset(self::$plugins_events[$event][$plg_name]);
	}

	private static function triggerPlgEvent($plg_name, $event, $params = array()) {
		// $plg_name;

		// $method = 'on'.ucfirst($event);
		// $plugin_method = $plg_name.'::'.$method;
		// call_user_func_array($plugin_method,$params);

		array_unshift($params,$event);
		return call_user_func_array($plg_name.'::triggerEvent',$params);
		// return $plg_name::triggerEvent($event,$params);
	}



	// private static $plugins_routng = array();
	
	private static $plugins_config = array();

	private static $app_session_prefix;
	
	//
	private static function initPlugin($plg_name,$plg_path,$plg_alias) { //,$plg_alias) {
		// call plugin event onInit;
		// $plg_name.'::onInit';
		
		// $plg_alias = $plg_name::get('alias','');
		// console::log($plg_name.'('.$plg_path.') '.$plg_alias,'plugin alias');
		
		// console::log('plugin: '.$plg_name);
		// console::log('plg_path: '.$plg_path);
		// console::log('alias: '.$plg_alias);
		
		debug::addLog(array(
			'label' => $plg_name,
			'message' => 'loaded'
		), 'plugin');
		
//		plugin::set('path',$plg_path,$plg_name);
		// $plg_name::set('',new session($plg_name));
		// $app = core::app();
		// $session_prefix = $app::get('session_prefix','');
		// self::$app_session_prefix;

		// self::$plugins_events

		$events = $plg_name::getEvents();
		foreach ($events as $event => $priority) {
			self::registerEvent($plg_name,$event,$priority);
			
			/* if (!isset(self::$plugins_events[$event])) {
				self::$plugins_events[$event] = array();
			}
			self::$plugins_events[$event][$plg_name] = $priority; */
		}

		// $plg_name::set('path',$plg_path);
		$parent = $plg_name::parentName();
		if (($parent !== 'core\plugin') && ($parent !== null)) {
			$core_path = $parent::get('path');
			$plg_name::set('core.path',$core_path);
		}

		$vars = $plg_name::getVars();
		$system_vars = array('session','alias','path','core.path');
		foreach ($vars as $var => $val) {
			if (!in_array($var,$system_vars)) {
				$plg_name::set($var, $val);
			}
		}
		// console::log($plg_name,'set_vars');
		// console::log($vars);
		
		// $set_vars
		
		$plg_name::set('alias',$plg_alias);
		$plg_name::set('session',new session(self::$app_session_prefix.':'.$plg_name));
		
		self::$plugin_langs[$plg_name] = self::pluginLoadLang($plg_name);
		self::$plugins_config[$plg_name] = self::pluginLoadConfig($plg_name);
		// self::$plugins_routng[$plg_name] = $plg_name::getRouting();
		$plg_name::initActions();
		// self::$plugins_actions[$plg_name] = $plg_name::getActions();
		// $plg_name::set('action',$plg_alias);

		// if (self::hasEvent($plg_name,'init')) {
		if (self::hasPluginEvent($plg_name,'init')) {
			$plg_name::triggerEvent('init'); // ,array());
		}
		// console::log($plg_name.'::onInit','init plugin');
	}
	
	public static function getConfig($plg_name, $var_name, $default = null) {
		if ( ( !isset(self::$plugins_config[$plg_name]) || !isset(self::$plugins_config[$plg_name][$var_name]) ) ) {
			return $default;
		}
		$val = self::$plugins_config[$plg_name][$var_name];
		$_val = strtolower($val);
		if ($_val === 'true') {
			$val = true;
		} else if ($_val === 'false') {
			$val = false;
		} else if ($_val === 'null') {
			$val = null;
		}
		return $val;
	}

	private static function init_plugins() {
		self::$event_sort = false;
		foreach (self::$plugins as $plg => $plugin) {
			//$plugin['is_core'];
			// $plg_name = str_replace('.','\\',$plugin['name']);
			$plg_name = str_replace('.','\\',$plg);
			$plg_path = $plugin['path'];
			$plg_alias = $plugin['alias'];
			self::initPlugin($plg_name,$plg_path,$plg_alias); // ,$plg_alias);
		}
		self::$event_sort = true;
		
		// self::$initialised = true; << todo позволить позднюю подгрузку плагинов -> вызывать событие инициализации если общая инициализация уже была проведена
		
		self::sortEventsPriority();
	}

	private static function call_action($ajax) {
		$action_val = request::get('action','','any');
		if ($action_val === '') return '';

		list($plg_alias,$action) = split_str('/',$action_val);

		list($plg_name) = self::alias_to_plg($plg_alias);

		// list($app,$plg_alias) = split_str(':',$plg);

		// $plg_name = '';
		if ($ajax) {
			$res = false;
			// $res_ = false;
			
			if (plugins::import($plg_alias)) {
			
				self::init_plugins();

				self::triggerEvent('beforeAjax', array($plg_name, $action));

				$res = $plg_name::action($action,array(),true);
				// $res_ = $res;
				if ($res === null) $res = false;
			}

			if ($res === false) {
				$res = 'empty';
			} else if (!isset($res['msg'])) {
				$res = array(
					'msg' => '',
					// 'debug_log' => array(), << if debug enabled and user has access
					'result' => $res
				);
			}

			self::triggerEvent('afterAjax',array(&$res));

			while (ob_get_level() > 0) {
				ob_end_clean();
			}
			echo json_encode($res, JSON_UNESCAPED_UNICODE);
			// var_dump(debug::getLog());
			exit;
			
		} else {
			$plg_name::action($action,array(),true);
		}
	}

	public static function init($ajax = false) {
		$app = core::app();
		self::$app_session_prefix = $app::get('session_prefix','');

		if (!$ajax) {
			self::init_plugins();
			
			self::match_routings();
		}

		self::call_action($ajax);
	}


	private static function match_routings() {
		$relative_url = core::getRoutes(); // explode('/',remove_left(core::relative_url(),'/'));
		$matched_routings = array();

		$app = core::app();
		$route_strict = $app::get_static('route_strict',false);

		// console::log($relative_url);
		
		// console::log(self::$plugins_routng);
		
/*		foreach (self::$plugins as $plg => $plugin) {
			$plg_name = str_replace('.','\\',$plg);
			self::$plugins_routng[$plg_name] = $plg_name::getRouting();
		} */
		// foreach (self::$plugins_routng as $plg => $routings) {
		foreach (self::$plugins as $plg => $plugin) {
			$plg = str_replace('.','\\',$plg);
			$routings = $plg::getRouting();

			foreach ($routings as $condition => $routing) {
				$match = true;
				
				list($url_path, $get, $post) = extend_arr(explode(':',$condition),3);
				$url_path = rtrim($url_path,' ');
				if (starts_with($post,'get{')) {
					list($post,$get) = array($get,$post);
				}

				if ($get !== '') {
					list($method,$get_vars) = split_str('{',$get);
					if ($method !== 'get') {
						trigger_error(debug::_('PLUGIN_ROUTING_WRONG_CONDITION_FORMAT',$plg.' '.$condition),debug::WARNING);
						continue;
					}
					$get_vars = explode(',',split_left('}',$get_vars,false));
					
					foreach ($get_vars as $var) {
						$var = remove_left($var,'$');
						if (strpos($var,'=') !== false) {
							list($var,$val) = split_str('=',$var);
							if (request::get($var,null,'get') !== $val) {
								$match = false;
							}
						} else {
							if (!request::has($var,'get')) {
								$match = false;
							}
						}
						if (!$match) {
							break;
						}
						/* $r = request::get($var,null,'get');
						if (($r === null) || ($r !== $val)) {
							$match = false;
							break;
						} */
					}
					if (!$match) continue;
				}

				if ($post !== '') {
					list($method,$post_vars) = split_str('{',$post);
					if ($method !== 'post') {
						trigger_error(debug::_('PLUGIN_ROUTING_WRONG_CONDITION_FORMAT',$plg.' '.$condition),debug::WARNING);
						continue;
					}
					$post_vars = explode(',',split_left('}',$post_vars,false));
					
					/* foreach ($post_vars as $var) {
						if (!request::has($var,'post')) {
							$match = false;
							break;
						}
					} */
					foreach ($post_vars as $var) {
						$var = remove_left($var,'$');
						if (strpos($var,'=') !== false) {
							list($var,$val) = split_str('=',$var);
							if (request::get($var,null,'post') !== $val) {
								$match = false;
							}
						} else {
							if (!request::has($var,'post')) {
								$match = false;
							}
						}
						if (!$match) {
							break;
						}
					}
					if (!$match) continue;
				}

				// $relative_url = $_relative_url;
				
				$vars = array();
				if ( ($url_path !== '') && ($url_path !== '*') ) {
					
					$url_mask = explode('/',remove_left($url_path,'/'));
					
					if ($route_strict) {
						if (count($url_mask) != count($relative_url)) {
							continue;
						}
					}

					foreach ($url_mask as $i => $mask_segment) {
						// $url_segment;
						if (!isset($relative_url[$i])) {
							$match = false;
							break;
						}
						
						$url_segment = $relative_url[$i];
						// array_unshift($relative_url);
						if (starts_with($mask_segment,'$')) {
							$vars[remove_left($mask_segment,'$')] = $url_segment;
						} else {
							if ($mask_segment !== $url_segment) {
								$match = false;
								break;
							}
						}
						/* if () {
							$match = false;
							break;
						} */
					}
					if (!$match) continue;
					
				}

				// console::log($plg);
				// console::log($condition);
				// console::log($match);
				
				/* var_dump($plg);
				var_dump($condition);
				var_dump($match); */
				

				//if (!isset($matched_routings[$plg])) {
				//	$matched_routings[$plg] = array();
				//}
				//$matched_routings[$plg][] = array(
				//	'vars' => $vars,
				//	'routing' => $routing
				//);
				
				$matched_routings[$plg] = array(
					'vars' => $vars,
					'routing' => $routing,
					'condition' => $condition
				);
				break;
				
				// $vars;
				// $routing;
				
				// $url_path
				
				// $match = true;
			}
		}
		// console::log($matched_routings,'matched_routings');
		$route_template = '';
		foreach ($matched_routings as $plg => $route) {
			// console::log($plg,'plugin routings');
			
			// $route['vars'];
			// $route['routing'];
			list($route_vars,$routing,$condition) = array_to_list($route,array('vars','routing','condition'));

			if (is_string($routing)) {
				// name of method is $plg class or application template
				if (starts_with($routing,'view:')) {
					$view_name = split_right(':',$routing); // remove_left($routing,'view:');
					$routing = 'plugin:'.str_replace('\\','.',$plg).'.'.$view_name;
					// 'plugin:fdfg.dgdfg:dfgfdg'
//					'view{dfgdgf, view:dgdfg}'
				}

				if (starts_with($routing,'plugin:') || starts_with($routing,'app')) {
					if ($route_template === '') {
					
						debug::addLog(array(
							'label' => $plg,
							'message' => 'app.template: \''.$routing.'\' condition: \''.$condition.'\''
						), 'routing');
						
						$route_template = $routing;
					}
				} else {


					if (strpos($routing,':') !== false) {
						// self::triggerEvent('routeMethod',array(&$plg, &$routing));
						list($plg,$routing_method) = split_str(':',$routing);
					} else {
						$routing_method = 'route'.ucfirst($routing);
					}

					// if (!is_callable($plg.'::'.$routing_method)) { // !method_exists($plg,$routing_method)) {
					if (!$plg::has_method($routing_method,$plg)) { // method_exists($plg,$routing_method)) {
						trigger_error(debug::_('PLUGIN_ROUTING_METHOD_NOT_EXISTS',$plg.' '.$routing_method),debug::WARNING);
						continue;
					}

					debug::addLog(array(
						'label' => $plg.'::'.$routing_method,
						'message' => 'condition: \''.$condition.'\'' // $event_method
					), 'routing');


					$routing = $plg::call_method($routing_method,array($route_vars),$plg); // $routing_method($route_vars); // call routing
					if ($routing != '') {
						if (starts_with($routing,'view:')) {
							$view_name = split_right(':',$routing);
							$routing = 'plugin:'.str_replace('\\','.',$plg).'.'.$view_name;
						}
						$route_template = $routing;
					}
				}
				
				/* if (starts_with($routing,'plugin:')) {
					list($plg_name,$view_name) = split_str('.',remove_left($routing,'plugin:'),false);
					$plg_name = str_replace('.','\\',$plg_name);
					
				} */
				
			} else if (is_function($routing)) { // && ($routing instanceof Closure)) {
				// лямбда-функция
				
				/* debug::addLog(array(
					'label' => $plg,
					'message' => $event_method
				), 'event'); */
				
				// console::log(1);
				debug::addLog(array(
					'label' => $plg,
					'message' => 'routing: \'lambda_function\' condition: \''.$condition.'\'' // $event_method
				), 'routing');

				$routing = $routing($route_vars,$plg); // call routing
				if ($routing != '') {
					if (starts_with($routing,'view:')) {
						$view_name = split_right(':',$routing);
						$routing = 'plugin:'.str_replace('\\','.',$plg).'.'.$view_name;
					}
					$route_template = $routing;
				}
			} else {
				trigger_error(debug::_('PLUGIN_ROUTING_WRONG_VALUE',$plg.' '.$condition),debug::WARNING);
			}
			
			// console::log($route['vars'],'vars');
			// console::log($route['routing'],'routing');
		}
		
		if ($route_template !== '') {
			$app = core::app();
			// var_dump($route_template);
			$app::set('template',$route_template);
		}
	}

	// plugins::import('auth');
	
	// $plg = 'auth';
	// $path = 'core.plugins/auth/core.plugin.auth.php';

	// $plg = 'app:auth';
	// $path = 'app/plugins/auth/plugin.auth.php';

	// $plg = 'app.tasks:auth.tabs';
	// ///  $path = 'app/app.tasks/ plugins/auth/ plugins/tabs/plugin.tabs.php';

	// $path = 'app/app.tasks/ plugins/auth.tabs/ plugin.auth.tabs.php';

	public static function alias_to_plg($plg_alias, $context = '') {
		list($app, $plg) = split_str(':',$plg_alias);
		if ($plg === '') {
			$plg = $app;
			if ($context == '') {
				$context = core::app_name(); // 'app.'.core::app();
			}
			$app = $context; // 'app';
		}

		// $arr = explode(':',$plg_alias);

		/* list($app, $plg) = $arr; if (!isset($arr[1])) {
			$plg = $arr[0];
			$app = 'app'; // core::app_name();
		} */

		$plg_name = str_replace('.','_',$plg);

		$base_app_path = core::app_path($app).'/';
		$plg_path = $base_app_path.'plugins/'.$plg;
		$plg_filename = 'plugin.'.$plg;
		
		return array($plg_name, $plg_path, $plg_filename);
	}
	
	public static function import($plg) {
		if ($plg === '') {
			trigger_error(debug::_('PLUGINS_IMPORT_NAME_NOTSET',$plg),debug::WARNING);
			return false;
		}

		$plg_alias = $plg;

		if (strpos($plg,':') !== false) {
			$plg = split_right(':',$plg);
		}

/*		$arr = explode(':',$plg_alias);
		list($app, $plg) = $arr; if (!isset($arr[1])) {
			$plg = $arr[0];
			$app = core::app_name();
		}

		$plg_name = str_replace('.','_',$plg);


//		$plgs = explode('.',$plg);
//		$segments = array();
//		foreach ($plgs as $p) {
//			$segments[] = 'plugins/'.$p;
//		}
//		$plg_name = array_pop($plgs);

		// $plg
		// $plg_segments = implode('/',$segments);


		$base_app_path = core::app_path($app).'/';
		$plg_app_path = $base_app_path.'plugins/'.$plg; // $plg_segments
		$plg_app_filename = 'plugin.'.$plg;
		// $plg_app_filepath = $plg_app_path.'/'.'plugin.'.$plg_name.'.php';
		*/

		/* if (strpos($plg_alias,':') === false) {
			$context = self::$_plg_context;
			if ($context == '') {
				$context = core::app_name(); // 'app.'.core::app();
			}
			// $plg_alias = core::app().':'.$plg_alias;
			$plg_alias = $context.':'.$plg_alias;
		} else {
			$plg = split_right(':',$plg);
		} */

		if (strpos($plg_alias,':') === false) {
			$plg_alias = core::app().':'.$plg_alias;
		}

		list($plg_name, $plg_app_path, $plg_app_filename) = self::alias_to_plg($plg_alias);

		$is_in_app = is_dir($plg_app_path) && !is_file($plg_app_path.'/enabled.false');


		$plg_core_path = 'core.plugins/'.$plg; // $plg_segments
		$plg_core_filename = 'core.plugin.'.$plg;
		// $plg_core_filepath = $plg_core_path.'/'.'core.plugin.'.$plg_name.'.php';
		$is_in_core = is_dir($plg_core_path);

		if ($is_in_app) {
			$res = true;
/*			if ($is_in_core && is_file($plg_app_path.'/enabled.false')) {
				trigger_error(debug::_('PLUGINS_IMPORT_CORE_PLUGIN_DISABLED',$plg_core_path),debug::WARNING);
				$res = false;
			} */
			if ($is_in_core) {
				// load from core first with namespace = 'core';
				
				if (is_file($plg_core_path.'/enabled.false')) {
					// if core plugin disabled don't load from app
					// trigger_error(debug::_('PLUGINS_IMPORT_CORE_PLUGIN_DISABLED',$plg_core_path),debug::WARNING);
					$res = false;
				} else {
					if (is_file($plg_app_path.'/enabled.false')) {
						// app plugin disabled but core plugin enabled
						// you should disable core plugin instead if your app plugin extends from core plugin
						trigger_error(debug::_('PLUGINS_IMPORT_APP_PLUGIN_DISABLED',$plg_core_path),debug::WARNING);
						$res = false;
					} else {
						// console::log('path: '.$plg_core_path,'loadPlugin [core]');
						$res = self::loadPlugin($plg_core_path,$plg_core_filename,$plg_name,$plg_alias); //,'core'); // ,$plg_alias
					}
				}
			} else {
				// app plugin disabled
				if (is_file($plg_app_path.'/enabled.false')) {
					$res = false;
				}
			}
			if ($res) {
				// load plugin from app
				// console::log('path: '.$plg_app_path,'loadPlugin [app]');
				$res = self::loadPlugin($plg_app_path,$plg_app_filename,$plg_name,$plg_alias); // ,$plg_alias
			}
		} else {
			// load plugin from core with namespace = '\'
			if (!is_file($plg_core_path.'/enabled.false')) {
				$res = self::loadPlugin($plg_core_path,$plg_core_filename,$plg_name,$plg_alias,'core'); // ,$plg_alias
			}
		}
		
		return $res;
	}

	private static $beforeBody = null;
	private static $afterBody = null;

	public static function getBeforeBodyInsert() {
		if (self::$beforeBody === null) {
			$html = '';
			self::triggerEvent('beforeBody',array(&$html));
			// $html .= '';
			self::$beforeBody = $html;
		}
		return self::$beforeBody;
	}

	public static function getAfterBodyInsert() {
		if (self::$afterBody === null) {
			$html = '';
			self::triggerEvent('afterBody',array(&$html));
			// $html .= '';
			self::$afterBody = $html;
		}
		return self::$afterBody;
	}

}
