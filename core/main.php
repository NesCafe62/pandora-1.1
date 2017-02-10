<?php
define ("CORE_EXEC","1");

ini_set("display_errors","1");
error_reporting(E_ALL);

mb_internal_encoding('UTF-8');

define("DS",DIRECTORY_SEPARATOR);

class console {

//	const NOTICE = E_USER_NOTICE;
//	const ERROR = E_USER_ERROR;
//	const WARNING = E_USER_WARNING;

	// private static $logs = array();

	public static function log($x, $label = '', $channel = 'console') {
		// echo '<pre>';
/*		ob_start();
		if ($label) {
			$l = max(16-strlen($label),0);
			echo $label.':'.str_repeat('&nbsp',$l).'&nbsp';
		}
		var_dump($x);
		// $logs['console'][] = ob_get_clean();
		$row = ob_get_clean();
		debug::addLog($row,'console'); */

		/* ob_start();
		var_dump($x);
		$dump = htmlspecialchars(ob_get_clean()); */

		if (!class_exists('strings')) {
			libs::load('strings');
		}

		$dump = '<pre>'.strings::dump($x).'</pre>';
		// str_replace('  ', '    ', preg_replace('/=>\s*/',' => ',
		
		/* if ($label) {
			$l = max(16-strlen($label),0);
			$label = $label.':'.str_repeat('&nbsp',$l).'&nbsp';
		} */
		if ($label) {
			$label = $label.':';
		}
		
		$caller_level = 1;
		// $m = debug::get_caller_method($caller_level);
		list($file,$line) = debug::get_caller_method($caller_level);
		// $file = '/'.remove_left($m['file'],core::base_dir());
		// $line = $m['line'];
		
		$msg = array(
			'label' => $label,
			'message' => $dump,
			// 'level' => 'notice',
			'file' => $file,
			'line' => $line
		);
		debug::addLog($msg,$channel);
		// echo '</pre>';
	}

	public static function getLog() {
/*		$rows = debug::getLog('console');
		$log = '';
		foreach ($rows as $row) {
			$log .= '<pre>'.$row.'</pre>';
		} */
		return debug::getLog('console'); // $log;
	}

//	public static function err($err) {
//		$args = func_get_args();
//		array_shift($args);
//		$var = 'ERR_'.$err;
//		$params = $args;
//		foreach ($params as &$param) {
//			$param = "'".$param."'";
//		}
	/*	if (isset(self::$lang[$var])) {
			$val = self::$lang[$var];
			if (count($params) > 0) {
				array_unshift($params,$val);
				$res = call_user_func_array('sprintf',$params);
			} else {
				$res = $val;
			}
		} else { */
//			array_unshift($params,$var);
//			$res = implode(' ',$params);
		// }
//		return $res;
//	}

}


class debug {

	// [Section: main]
	
	const NOTICE = E_USER_NOTICE;
	const ERROR = E_USER_ERROR;
	const WARNING = E_USER_WARNING;
	const DEPRECATED = E_USER_DEPRECATED;
//	const CONSOLE = 3;
	
//	public static function err() {
//		$args = func_get_args();
//		$err = array_shift($args);
//		$msg = array_shift($args);
//		;
//	}



	public static function get_caller_method($level = 2) {
		$traces = debug_backtrace();

		$file = null;
		$line = null;
		if (isset($traces[$level])) {
			$m = $traces[$level];
			$file = '/'.remove_left($m['file'],core::base_dir());
			$line = $m['line'];
		}

		return array($file,$line);
	}

	public static function dumpCaller($level = 1) { // , $caller = null) {
		$level++;

		$traces = debug_backtrace();
		if (!isset($traces[$level])) {
			return '';
		}

		$m = $traces[$level];
		$file = '/'.remove_left($m['file'],core::base_dir());
		$line = $m['line'];

		return '[console] '.$file.' line '.$line;
	}


	public static function searchInFiles($text, $path = '/', $mask = '*.php', $remove_comments = true) {
		if ($path === '') {
			$path = '/';
		}
		$arr = files::searchInFiles($text,$path,$mask,$remove_comments);
		// $res = '<div class="row">searching <pre>\''.$text.'\'</pre> in <pre>\''.$mask.'\'</pre> files <pre>\''.$path.'\'</pre> </div>';
		$res = '<div class="row">';
			$res .= '<span class="label">search:</span>';
			$res .= '<pre>\''.$text.'\' path: <div class="file">\''.$path.'\'</div>files: <div class="file">\''.$mask.'\'</div></pre>';
		$res .= '</div>';
		$last_file = '';
		foreach ($arr as $s) {
			$res .= '<div class="row">';
				$res .= '<div style="display: inline-block; min-width: 700px; margin-bottom: 1px;">';
					$res .= '<div class="line">строка &nbsp;<span style="display: inline-block; min-width: 40px;">'.$s['line_number'].'</span></div>';
					$res .= '<pre style="margin-right: 10px" class="block">'.htmlspecialchars(trim($s['line'])).'</pre>';
				$res .= '</div>';
				
				$file = '--';
				if ($s['file'] !== $last_file) {
					$last_file = $s['file'];
					$file = $s['file'];
				}
				$res .= '<div class="file">'.$file.'</div>';
				
				// '<div style="display: inline;" class="line">строка <span>'.$s['line_number'].'</span></div>';
					
			$res .= '</div>';
		}
		return '[console] '.$res;
	}


/*	private static $modes = array();
	private static $debug_log = array();

	private static $lang = array(); */

/*	public static function _($err) {
		$args = func_get_args();
		array_shift($args);
		$var = 'ERR_'.$err;
		$params = $args;
		foreach ($params as &$param) {
			$param = "'".$param."'";
		}
		if (isset(self::$lang[$var])) {
			$val = self::$lang[$var];
			if (count($params) > 0) {
				array_unshift($params,$val);
				$res = call_user_func_array('sprintf',$params);
			} else {
				$res = $val;
			}
		} else {
			array_unshift($params,$var);
			$res = implode(' ',$params);
		}
		return $res;
	} */

	public static function _($err) {
		$args = func_get_args();
		array_shift($args);
		$var = 'ERR_'.$err;
		$params = $args;
		foreach ($params as &$param) {
			$param = "'".$param."'";
		}
		array_unshift($params,$var);
		$res = implode(' ',$params);
		return $res;
	}


	private static $debug_log = array();

	public static function addLog($msg, $channel = '', $prepend = false) { // channels: system, console, routing, sql, event, action // loglevels: error, warning, notice, strict, deprecated, log, console
		if ($channel === '') $channel = 'console';
		/* $msg = array(
			'channel' => $channel,
			'label' => $msg['label'],
			'message' => $msg['message'],
			'level' => $msg['level'],
			'file' => $msg['file'],
			'line' => $msg['line']
		); */
		$level = 'log';
		if ($channel === 'console') {
			$level = 'console';
		}
		if ($channel === 'system') {
			$level = 'notice';
		}
		$msg = array_merge(array('channel' => $channel),$msg);
		// $msg['channel'] = $channel;

		$override = false;
		if (self::is_channel($channel)) {
			$override = true;
		}

		$msg = extend($msg, array(
			'channel' => '',
			'message' => '',
			'label' => '',
			'level' => $level,
			'file' => '',
			'line' => '',
			'override' => $override
		));
		if (!$prepend) {
			self::$debug_log[] = $msg;
		} else {
			self::$debug_log = array_merge(array($msg), self::$debug_log);
		}
	}
	
	public static function parse_message($err_message) {
		preg_match('/(.*) in ([^\s]+) on line (\d+)$/', $err_message, $matches);
		return array(
			'channel' => 'system',
			'label' => '',
			'message' => $matches[1],
			'level' => 'error',
			'file' => $matches[2],
			'line' => $matches[3]
		);
	}

/*	public static function addLog($row, $channel) {
		if (!isset(self::$debug_log[$channel])) {
			self::$debug_log[$channel] = array();
		}
		self::$debug_log[$channel][] = $row;
	} */

/*	public static function getLog($channel) {
		if (!isset(self::$debug_log[$channel])) {
			// trigger_error(debug::_('DEBUG_GETLOG_CHANNEL_NOTEXIST',$channel),debug::WARNING);
			return array();
		}
		return self::$debug_log[$channel];
	} */

	public static function init($start_time) {

		// self::timer_start('page.generated');
		$timer = new timer(false);
		$timer->reset($start_time);
		self::$timers['page.total'] = $timer;

		$e = error_get_last();
		if ($e) {
			self::addLog(array(
				'label' => '',
				'message' => $e['message'],
				'type' => $e['type'],
				'level' => self::errorLevel($e['type']),
				'file' => $e['file'],
				'line' => $e['line']
			),'system');
			error_clear_last();
		}

		// self::loadLang();
		register_shutdown_function(array('debug', '_shutdownHandler'));
//		set_exception_handler(array('debug','_exceptionHandler'));
		set_error_handler(array('debug','_errorHandler'));
	}

	public static function getMode() {
		return true;
	}
	
	private static $log_session = null;

	public static function init_session() {
		self::$log_session = new session('debug_log');

		$messages = self::$log_session->get('log',null);
		if ($messages !== null) {
			self::$debug_log = array_merge($messages, self::$debug_log);
		}
		
		self::$log_session->clear('log');
	}

	public static function save_log_session() {
		self::$log_session->set('log',self::$debug_log);
	}

	private static $ignore_errors = false;


	public static function getErrorTypeName($type) {
		$err_types = array(
			E_STRICT => 'strict',
			E_DEPRECATED => 'deprecated',
			E_USER_DEPRECATED => 'deprecated',
			E_NOTICE => 'notice',
			E_USER_NOTICE => 'notice',
			E_WARNING => 'warning',
			E_USER_WARNING => 'warning',
			E_PARSE => 'fatal error', // 'parse error'
			E_USER_ERROR => 'error',
			E_RECOVERABLE_ERROR => 'recoverable error',
			E_CORE_ERROR => 'core error',
			E_COMPILE_ERROR => 'fatal error', // 'compile error'
			E_CORE_WARNING => 'core warning',
			E_COMPILE_WARNING => 'compile warning',
			E_ERROR => 'fatal error'
		);

		return ucfirst($err_types[$type]);
	}

	public static function _out_error($e) {
		$msg = isset($e['message']) ? $e['message'] : '';
		$file = isset($e['file']) ? $e['file'] : '';
		$line = isset($e['line']) ? $e['line'] : '';
		// $file = '/'.remove_left($file,core::base_dir());
		$channel = $e['channel'];
		if ($e['channel'] === 'system') {
			$channel = self::getErrorTypeName($e['type']); // $err_levels[$e['type']];
		}
		// $channel = $e['type'];

		if (isset($e['label']) && ($e['label'] !== '')) {
			$msg = '<span class="label">'.$e['label'].'</span> '.$msg;
		}

		$html = '<b>'.ucfirst($channel).'</b>: '.$msg;
		if ($file !== '') {
			$html .= ' in <b>'.$file.'</b>';
		}
		if ($line !== '') {
			$html .= ' on line <b>'.$line.'</b>';
		}

		return $html;
	}

	public static function _out_errors() {
		$log = self::getLog();
		$out = '';
		foreach ($log as $msg) {
			$out .= self::_out_error($msg).'<br/>';
		}
		return $out;
	}

	public static function _errTemplate() {
		$root = core::base_dir();
		$messages = self::getLog();
		require(self::$err_tpl_filename);
	}
	
	private static $err_tpl_filename = '';

	public static function _shutdownHandler() {
		$e = error_get_last();
		if ($e) {
			// self::_errorHandler($e['type'], $e['message'], $e['file'], $e['line']);

			if (self::$ignore_errors) return;
			while (ob_get_level() > 0) {
				ob_end_clean();
			}
			self::$ignore_errors = true;

			/* if (class_exists('plugins')) {
				plugins::broadcastEvent('runtimeError',array($e));
			} */
//			self::$ignore_errors = false;
			// log;
			// $app_path = core::getAppPath();
			// $root = core::getRoot().'/';
			$root = core::base_dir();
			// $err_template = $root.$app_path.'error.php';
			$app_path = core::get_app_path();
			$err_template = $root.$app_path.'error.php';
			if (!is_file($err_template)) {
				$err_template = $root.'core/error.php'; // $root.'core/error.php';
			}
			if (is_file($err_template)) {
				self::$err_tpl_filename = $err_template;
				self::_errTemplate();
			} else {
				echo self::_out_errors();
				// echo self::_out_error($e);
			}
			self::$ignore_errors = false;
		}
	}


	private static $channel_modes = array();
	private static $channel_mode_stack = array();

	public static function channel($channel, $state = true) { // $mode) {
		self::$channel_modes[$channel] = (bool)$state; //($mode === 'on') || ($mode === true) || ( !is_string($mode) && ((int)$mode > 0) );
	}

	public static function channelPush($channel, $state = true) {
		if (!isset(self::$channel_mode_stack[$channel])) {
			self::$channel_mode_stack[$channel] = array();
		}
		array_push(self::$channel_mode_stack[$channel], isset(self::$channel_modes[$channel]) && self::$channel_modes[$channel]);

		self::$channel_modes[$channel] = (bool)$state;
	}

	public static function channelPop($channel) {
		if ( !isset(self::$channel_mode_stack[$channel]) || (count(self::$channel_mode_stack[$channel]) <= 0) ) {
			trigger_error(debug::_('DEBUG_MODE_POP_CHANNEL_WAS_NOT_PUSHED',$channel),debug::NOTICE);
			return;
		}
		
		self::$channel_modes[$channel] = array_pop(self::$channel_mode_stack[$channel]);
	}

	private static function is_channel($channel) {
		return isset(self::$channel_modes[$channel]) && self::$channel_modes[$channel];
	}

	public static function errorLevel($type) {
		$err_levels = array(
			E_STRICT => 'strict',
			E_DEPRECATED => 'deprecated',
			E_USER_DEPRECATED => 'deprecated',
			E_NOTICE => 'notice',
			E_USER_NOTICE => 'notice',
			E_WARNING => 'warning',
			E_USER_WARNING => 'warning',
			E_PARSE => 'error',
			E_USER_ERROR => 'error',
			E_RECOVERABLE_ERROR => 'error',
			E_CORE_ERROR => 'error',
			E_COMPILE_ERROR => 'error',
			E_CORE_WARNING => 'error',
			E_COMPILE_WARNING => 'error',
			E_ERROR => 'error'
		);
		return $err_levels[$type];
	}

	public static function _errorHandler($type, $message, $file, $line) { // , $context) {
		/* self::$debug_log[] = array(
			'type' => 'err',
			'code' => $code,
			'message' => $message,
			'file' => $file,
			'line' => $line,
			'context' => $context
		); */

		$level = self::errorLevel($type); // $err_levels[$type];
		$channel = 'system';
		
		if ($message[0] === '[') {
			list($level,$message) = split_str(']',$message);
			$level = remove_left($level,'[');
			$channel = $level;
			$message = remove_left($message,' ');
		}
		
		$file = '/'.remove_left($file,core::base_dir());

		self::addLog(
			array(
				'label' => '',
				'message' => $message,
				'type' => $type,
				'level' => $level,
				'file' => $file,
				'line' => $line
			),
			$channel
		);
		return null;
	}


	private static $timers = array();

	public static function timer_start($timer = '', $label = '') {
		if (isset(self::$timers[$timer])) {
			self::$timers[$timer]->start();
		} else {
			self::$timers[$timer] = new timer(); // autostart
		}

		$caller_level = 1;
		list($file, $line) = self::get_caller_method($caller_level);
		if ($label) {
			$label = $label.':';
			$msg = array(
				'label' => $label,
				'message' => 'timer start ['.$timer.']',
				'file' => $file,
				'line' => $line
			);
			self::addLog($msg,'console');
		}
		return self::$timers[$timer];
	}

	public static function timer_stop($timer = '', $label = '') {
		$elapsed = self::$timers[$timer]->stop();
		unset(self::$timers[$timer]);

		if ($label) {
			$label = $label.':';
		}

		$caller_level = 1;
		list($file, $line) = self::get_caller_method($caller_level);
		if ($label == '') {
			$label = 'timer stop ['.$timer.']:';
		}
		$msg = array(
			'label' => $label,
			'message' => strings::format_seconds($elapsed),
			'file' => $file,
			'line' => $line
		);
		self::addLog($msg,'console');
	}

//	debug::timer_start('timer1');

//	debug::timer_stop('timer1','label');


	/*
		sql;

		debug::mode('sql','on');
		sql;
		debug::mode('sql','off');


		sql;

		$errors = debug::getLog('default'); // system default plugin event redirect console sql
	*/



	// default =
		// +system.* (= system)
		// +redirect (= system)
		
		// +console

	// error =
		// +system.error

	// warning =
		// +system.error
		// +system.warning

	// notice =
		// +system.error
		// +system.warning
		// +system.notice

	// strict =
		// +system.error
		// +system.warning
		// +system.notice
		// +system.deprecated
		// +system.strict

	// system =
		// +system.* (= strict)
		// +redirect


	// console =
		// +console
	
	// plugin =
		// +plugin

	// event =
		// +event

	// action =
		// +action

	// routing =
		// +routing

	// redirect =
		// +redirect

	// sql =
		// +sql



	
	// debugger::set('log_level','warning console');

	
	// channels: system, console, routing, sql, event, action // loglevels: warning, error, notice, strict, deprecated, log, console
	public static function getLog($channels = 'system console') {
		// $channels = str_replace('default','system console',$channels);
		$_channels = explode(' ',$channels);

		$channels = array();
		if (in_array('all',$_channels)) {
			/* foreach (array('system','console', 'plugin','event','action','routing','redirect','sql') as $ch) { // 'plugin','event','action','routing','redirect','sql'
				$channels[$ch] = true;
			} */
			return self::$debug_log;
		}
		if (in_array('default',$_channels)) {
			$_channels[] = 'system';
			$_channels[] = 'console';
			$_channels[] = 'redirect';
		}
		if (in_array('system',$_channels) || in_array('console',$_channels)) {
			$_channels[] = 'redirect';
		}
		
		if (in_array('warning',$_channels)) {
			$_channels[] = 'error';
		}
		if (in_array('notice',$_channels)) {
			$_channels[] = 'error';
			$_channels[] = 'warning';
		}
		if (
			in_array('strict',$_channels) ||
			in_array('deprecated',$_channels) ||
			( in_array('system',$_channels) && !in_array('error',$_channels) )
		) {
			$_channels[] = 'error';
			$_channels[] = 'warning';
			$_channels[] = 'notice';
			$_channels[] = 'deprecated';
			$_channels[] = 'strict';
		}

		foreach ($_channels as $ch) {
			if (($ch === 'default') || ($ch === 'system')) continue;
			$channels[$ch] = true;
		}

		$messages = array();
		foreach (self::$debug_log as $msg) {
			$ch = $msg['channel'];
			if ($ch === 'system') {
				$ch = $msg['level'];
			}
			if ( $msg['override'] || ($ch === '') || ( (isset($channels[$ch])) && ($channels[$ch]) ) ) {
				// $msg['channel'] = ucfirst($msg['channel']);
				$messages[] = $msg;
			}
		}
		return $messages;
	}

}

class libs {

	private static $libs = array();
	private static $lib_classes = array();

	private static function add_lib($lib_path) {
		self::$libs[$lib_path] = true;
		
		$filename = pathinfo($lib_path,PATHINFO_FILENAME);
		if (starts_with($filename,'lib.')) {
			$lib_class = 'core\\'.remove_left($filename,'lib.');
			if (class_exists($lib_class)) {
				$lib_classes[$lib_class] = $lib_path;
			}
		}
	}

	private static function loaded($lib_path) {
		return
			isset(self::$libs[$lib_path]) &&
			(self::$libs[$lib_path] === true);
	}

	public static function class_loaded($lib_class, &$lib_filename) {
		$loaded = isset(self::$lib_classes[$lib_class]) && self::$lib_classes[$lib_class];
		if ($loaded) {
			$lib_filename = self::$lib_classes[$lib_class];
		}
		return $loaded;
	}

	// app.adm:plugin/dsfdf.fsf

	public static function loadAll($path = '') {
		self::load('files');

		if ($path === '') $path = 'core/lib';
		$libs = files::getFiles($path,'lib.*.php');

		foreach ($libs as $lib_path) {
			if (!self::loaded($lib_path)) {
				require_once($lib_path);
				self::add_lib($lib_path);
			}
		}
		return true;
	}


	// #doc {
	//		$0: 'core/{$path}/lib.{$lib}.php'
	//		eng: includes lib module by path {$0}
	//		ru: подключает библиотеку используя путь {$0}
	
	//		param: $lib
	//			eng: lib module file name
	//			ru: имя файла модуля
	
	//		param: $path
	//			eng: path to lib file relative to core/ separated with '.'
	//			ru: путь к файлу модуля относительно каталога core/ разделенный точками
	
	//		returns: void
	
	//		usage1: libs::load('less')
	//			$0: '/core/lib/lib.less.php'
	//			eng: loads {$0}
	//			ru: подключает {$0}
	
	//		usage2: libs::load('less','lib.less')
	//			$0: '/core/lib/less/lib.less.php'
	//			eng: loads {$0}
	//			ru: подключает {$0}
	
	// }
	public static function load($lib = '', $path = 'lib') {
		$path = str_replace('.','/',$path); // replace 'lib.some_folder' with 'lib/some_folder'
		$path = 'core/'.$path;

		if ($lib === '' || $lib === 'all') return self::loadAll($path);

//        console::log($lib);
		$lib = 'lib.'.$lib;

		//$lib_path = '/var/www/beavers.games/'.$path.'/'.$lib.'.php';
        $lib_path = $path.'/'.$lib.'.php';
//		console::log($lib_path);
		if (!file_exists($lib_path)) {
			trigger_error(debug::_('LIBS_LOAD_FILE_NOT_FOUND',$lib_path),debug::WARNING);
			return false;
		}

		if (self::loaded($lib_path)) return true; // lib is already loaded
		require_once($lib_path);
		self::add_lib($lib_path);

		return true;
	}

}

class core {

/*	private static $app = null;
	private static $app_base = 'app/';

	private static function init() {
		modules::load(); // same as modules::loadAll() or modules::load('all')
		self::$app = self::loadApp();
	}

	private static function app() {
		return self::$app;
	} */

//	public static function loadApp($app = 'app') {
		// 'app' 'app/main.php class=app'
		// 'app/tasks' 'app/tasks/main.php class=tasks'
/*		if ($path !== 'app') $path = 'app/'.$path;
		$app = last_xplod('/',$path);
		$app_path = $path.'/main.php';
		if (!file_exists($app_path)) {
			trigger_error(debug::_('CORE_LOAD_APP',$app_path),debug::WARNING);
			return false;
		}
		require_once($app_path);
		if (!class_exists($app)) {
			trigger_error(debug::_('CORE_APP_CLASS_NOTFOUND',$app_path),debug::WARNING);
			return false;
		}
		$path .= '/';
		self::$app_path = $path;
		$app::set('path',$path);
		$res = $app::load();

		return $res; */
//	}


/*	private static function render() {
		$app = self::$app;
		$app::render();
		// echo templar::render('app/main.php');
	}

	// core::template('app.page');
	public static function template($context) {
		list($app,$template) = explode('.',$context);
//		plugins::broadcastEvent('beforeTemplate');
//		$buffer = $app::template($template);
//		plugins::broadcastEvent('afterTemplate');
//		return $buffer;
		return $app::template($template);
	} */

	private static $app = null;
	private static $base_dir = '/';
	private static $app_name = 'app';
	private static $app_path = '/app';
	private static $app_base_url = '/app';
	private static $app_relative_url = '';
	// private static $url_segments = array();
	
	private static $url = '';
	private static $request_params = '';


	// !!! hosts -> 89.108.107.58  u-238.ru
	
	public static $ajax = false;
	
	public static function is_ajax() {
		return self::$ajax;
	}
	
	
	private static function init() {

		ob_start();

		$start_time = microtime(true);

		
		libs::load('functions');
		libs::load('timer');

		debug::init($start_time);

		// debug::timer_start('core.init');

		$arr = explode(DS,__DIR__);
		array_pop($arr);
		$base_dir = implode('/',$arr);
		self::$base_dir = $base_dir.'/';
		chdir($base_dir);

		libs::load(); // load all libs

//		$start_time2 = debug::timer_start('page.generated');

//		console::log($start_time2 - $start_time,'start_offset');

		server::init();

		request::init();
		// session::init(); // auto-initialized when called get or set
		debug::init_session();

		head::init();
		
		$current_app = self::routing();


		// 'app'; // 'app.tasks';
		
		
		// var_dump($current_app);
		
		if (!self::loadApp($current_app)) {
			self::loadApp('app');
		}

		/* trigger_error(debug::_('MSG_TEST_ERROR','error text'),debug::ERROR);
		trigger_error(debug::_('MSG_TEST_WARNING','warning text'),debug::WARNING);
		trigger_error(debug::_('MSG_TEST_NOTICE','notice text'),debug::NOTICE);
		console::log('log text','label'); */
		
		self::init_db();
		// config_get();
		
		self::$ajax = request::has('ajax','post');
		
		if (self::$ajax) {
			// $res = false;

			/* $action = request::get('action','','post');
			$action_plugin = request::get('plugin','','post');
			if ( ($action !== '') && ($action_plugin !== '') ) {
				
				list($app_name,$plugin) = split_str('/',$action_plugin,false);
				if ($app_name === '') {
					$app_name = 'app';
				}
				
				plugins::import($plugin);
				
			}

			if ($res === false) $res = 'empty';

			while (ob_get_level() > 0) {
				ob_end_clean();
			}
			echo json_encode($res);
			
			// self::finalize();
			exit; */
			
			self::triggerAppEvent('beforeAjax');

			plugins::init(true); // ajax

			return;
		}

		// debug::timer_stop('core.init','core init');

		// debug::timer_start('plugins.load');

		// if not ajax

        debug::timer_start('plugins_load');
        plugins::load(self::app_name());
        debug::timer_stop('plugins_load');

		self::triggerAppEvent('beforeInit');


//        debug::timer_start('plugins_load');
		plugins::load('core');
//        debug::timer_stop('plugins_load');
		// debug::timer_stop('plugins.load','plugins load');

		plugins::init();

		self::triggerAppEvent('init');
		// if ($app::hasMethod('onInit')) {
		//	$app::onInit();
		// }


		// debug::timer_stop('core.init','core init');

		// ob_get_clean();
		ob_end_clean();
	}

	public static function triggerAppEvent($event, $params = array(), $warning = false) {
		$app = self::app();
		if (!$warning && !$app::hasEvent($event)) {
			return false;
		}
		return $app::triggerEventArgs($event, $params);
	}

	private static function init_db() {
		$app = self::app();
		$enable_transactions = $app::config_get('db_enable_transactions',false);
		$db_params = array(
			'host' => $app::config_get('db_host',''),
			'user' => $app::config_get('db_user',''),
			'pass' => $app::config_get('db_pass',''),
			'dbname' => $app::config_get('db_database',''),
			'enable_transactions' => $enable_transactions,
			'port' => $app::config_get('db_port',null),
			'encoding' => $app::config_get('db_encoding','utf8')
		);
		db::connect($db_params);
	}
	

	//  full_url: u-238.ru/quanta/docs/page1
	//  app: quanta
	//  app_path: app.quanta
	//  app_dir: app/app.quanta
	//  app_controller: app/app.quanta/app.quanta.php
	//	app_base_url: /quanta
	//	app_relative_url: /docs/page1 -> array('docs','page1') to plugin action

	private static function routing() {
		// list($,$) = route::init(); // split_str('?',server::get('request_uri'));
		// $routing = explode('/');
		list($url,$requset_params) = route::init();


		$url = remove_left(remove_right($url,'/'),'/');
		self::$url = '/'.$url;
		self::$request_params = $requset_params;
		
		// $source_url = server::get('request_uri');
		
		// $url = split_first('?',server::get('request_uri')); // get url without params


		// console::log(self::$url,'url'); ### LOG URL

		// $url = remove_left($url,'/');
		$url_segments = explode('/',$url);

		// $current_app = парсинг слешей(/) и проверка каталогов app.*;
		$path = '';
		$segments = array('app');
		$current_app = 'app';
		// console::log(self::$url_segments,'url_segments');
		foreach ($url_segments as $i => $segment) {
			// if ( ($i > 0) || ($segment !== 'app') ) $segment = 'app.'.$segment;
			$segments[] = 'app.'.$segment;
			$app_path = self::_app_path($segments,true);
			if (!is_dir($app_path) || is_file($app_path.'/enabled.false')) {
				break;
			}
			$current_app .= '.'.$segment;
		}

		return $current_app;
	}

	private static function _app_path($segments, $dir = true) {
		$app_path = implode('/',$segments);
		if (!$dir) {
			$app_path .= '/'.array_pop($segments).'.php';
		}
		return $app_path;
	}

	// app_path('app'); -> // ['app'] '/app'

	// app_path('app.test'); -> // ['app','app.test'] '/app/app.test'

	// app_path('app.test.test2'); -> // ['app','app.test','app.test2'] '/app/app.test/app.test2'

	public static function app_path($app_name, $dir = true) {
		$arr = explode('.',$app_name);
		
		$segments = array();
		foreach ($arr as $i => $segment) {
			// if ( ($i > 0) || ($segment !== 'app') ) $segment = 'app.'.$segment;
			if ($i > 0) $segment = 'app.'.$segment;
			$segments[] = $segment;
		}

		return self::_app_path($segments, $dir);
	}

	// internal redirect

	public static function app_exists($app_name, &$app_filepath) {
		if (starts_with($app_name,'core\\')) {
			// return false;
			$app_name = remove_left($app_name,'core\\');
		}
		$app_path = 'app';
		if ($app_name !== 'app') {
			$app_name = 'app.'.$app_name;
			$app_path = 'app/'.$app_name;
		}
		$app_filepath = $app_path.'/'.$app_name.'.php';
		return is_file($app_filepath);
	}
	
	private static function loadApp($app_name = 'app') { // app  or  app.tasks.test1
//		$arr = explode('.',$app_name);

		// $n_apps = count($arr);
//		$segments = array();
		//for ($i = 0; $i < $n_apps; $i++) {
			//$dir_name = implode('.',array_slice($arr,0,$i+1));
			//$segments[] = $dir_name;
		//}

		$arr = explode('.',$app_name);
		$segments = array();
		foreach ($arr as $i => $segment) {
			// if ( ($i > 0) || ($segment !== 'app') ) $segment = 'app.'.$segment;
			if ($i > 0) $segment = 'app.'.$segment;
			$segments[] = $segment;
		}

//		// $app_filepath = implode('/',$segments).'/'.$segment.'.php'; //   app/app.php  or  app/app.tasks/app.test1/app.test1.php
		// $app_path = self::_app_path($segments,true);
		$app_path = self::_app_path($segments,true).'/';
		$app = array_pop($arr);
		
		$app_file = $app;
		if ($app !== 'app') $app_file = 'app.'.$app;
		
		// $app_filepath = $app_path.'/'.$app_file.'.php';
		$app_filepath = $app_path.$app_file.'.php';

//		$app_filepath = self::app_path($app_name,false);

		// console::log($app_filepath,'app_filepath');
		if (!is_file($app_filepath)) { // !file_exists($app_filepath)) {
			// console::err('');
			trigger_error(debug::_('CORE_LOADAPP_NOT_FOUND',$app_filepath),debug::WARNING);
			return false;
		}
		require_once($app_filepath);

		$arr = explode('.',$app_name);
		array_shift($arr);
		// $app_base = implode('/',$arr).'/';
		$app_base = '/'.implode('/',$arr);

		self::$app = $app;
		// self::$app_base_url = $app_base;
		// self::$app_name = $app_name;
		// self::$app_path = $app_path;

		// console::log(self::$app_relative_url,'relative_url'); ### LOG RELATIVE URL

		// console::log($app_base,'app_base_url'); ### LOG APP BASE URL
		
		$res = $app::load($app_base,$app_name,$app_path);

		self::$app_base_url = $app::get('app_base');
		self::$app_name = $app::get('app_name');
		self::$app_path = $app::get('app_path');

		$_app_base = self::$app_base_url;
		if ($_app_base === '/') $_app_base = '';
		self::$app_relative_url = '/'.remove_left(remove_left(self::$url,$_app_base),'/');

		return true;
	}

	private static $render_tpl_name = '';
	private static $render_tpl_path = '';
	private static $render_tpl_compile_path = '';

	private static function render_template() {
		// plugins::broadcastEvent('renderTemplate',array($template_path));
		// onRenderTemplate
		
		// onTemplateGetName
		// onBeforeTemplate
		
		// self::$render_tpl_compile_path = self::$render_tpl_path;
		plugins::triggerEvent('renderTemplate',array(self::$render_tpl_path));
		
//		if (self::$render_tpl_compile_path !== self::$render_tpl_path) {
			// plugins::triggerEvent('renderTemplate',array(self::$render_tpl_path));
			
//			self::$render_tpl_path = self::$render_tpl_compile_path;
//		}

		if (!file_exists(self::$render_tpl_path)) {
			trigger_error(debug::_('APP_TEMPLATE_NOT_FOUND',self::$render_tpl_name.', '.self::$render_tpl_path),debug::WARNING);
			return '';
		}

		$app = self::$app;

		ob_start();
		require(self::$render_tpl_path);
		return ob_get_clean();
	}

	// 'app:page'; -> app/templates/page.php
	// 'app.quanta:page'; -> app/app.quanta/templates/page.php
	// 'plugin:authors.page'; -> core.plugins/authors/tpl/page.php
	//                        -> app/plugins/authors/tpl/page.php
	//                        -> app/app.quanta/plugins/authors/tpl/page.php
	public static function template($name, $params = null) {
		if ($params === null) $params = array();
		
		$app = self::$app;
		if ($name === '') {
			// current template for current application
			$name = $app::get('template','');
		}
		// var_dump($name);
		if (starts_with($name,'plugin:')) {
			// plugin view {plugin}:{view}
			
			$plg_view = split_right(':',$name); // explode(':',$name);

			list($plg, $view) = split_str('.',$plg_view,false);

			// $arr2 = explode('.',$plg_view);
			// $view = array_pop($arr2);
			// $plg = implode('.',$arr2);
			// list($plg, $view) = extend_arr($arr2,2); // if (!isset($arr[1])) $view = '';

			// $params = $plg::getViewParams();
			$params = $app::get('view_params',array());

			console::log('plugin:'.$plg.' view:'.$view,'app::template');
			
			$html = plugins::view($plg,$view,$params);
			
		} else {
			if (!starts_with($name,'app')) {
				// current application template with name {$context}
				$name = $app::get('app_name').':'.$name;
			}
			$arr = explode(':',$name);
			list($app, $tpl_name) = extend_arr($arr,2); // if (!isset($arr[1])) $tpl_name = '';

			$template = self::app_path($app).'/templates/'.$tpl_name.'.php';

			// console::log($template,'template ['.$name.']'); LOG TEMPLATE

			// var_dump($template);
			self::$render_tpl_name = $name;
			self::$render_tpl_path = $template;
			$html = self::render_template();
			self::$render_tpl_name = '';
			self::$render_tpl_path = '';

		}
		
		return $html;
	}

	public static function app() {
		return self::$app;
	}

	public static function app_base() {
		return self::$app_base_url;
	}
	
	public static function relative_url() {
		return self::$app_relative_url;
	}

	public static function app_name() {
		return self::$app_name;
	}

	public static function get_app_path() {
		return self::$app_path;
	}

	public static function getRoutes() {
		return explode('/',remove_left(self::$app_relative_url,'/'));
	}
	


	public static function base_dir() {
		return self::$base_dir;
	}


	private static $lang = 'ru';

	public static function lang() {
		return self::$lang;
	}

	public static function setLang($lang) {
		self::$lang = $lang;
	}


	public static function redirect($url, $params = null) {
		if (core::is_ajax()) {
			return;
		}
		
		$url = route::url_removeParams('action', route::parse_url($url));
		
		if (!starts_with($url,'/')) $url = '/'.$url;
		
		$url = 'http://'.server::get('server_name').$url;

		/* ob_start();
		var_dump($params);
		$post_params = ob_get_clean(); */
		$_params = ($params !== null) ? $params : array();
		$post_params = '<pre>params: '.strings::dump($_params).'</pre>';

		plugins::triggerEvent('beforeRedirect');

		debug::addLog(array(
			'label' => $url,
			'message' => $post_params // 'params: '.$post_params // $event_method
		), 'redirect');

		debug::save_log_session();

		// self::finalize();

		if ($params === null) {
			header('Location: '.$url);
		} else {
			while (ob_get_level() > 0) {
				ob_end_clean();
			}
			$html = '<html>';
				$html .= '<head></head>';
				$html .= '<body onLoad="javascript:document.getElementById(\'form\').submit();">';
					$html .= '<form id="form" method="post" action="'.$url.'">';
					foreach ($params as $param => $val) {
						$html .= '<input type="hidden" name="'.$param.'" value="'.htmlspecialchars($val).'"/>';
					}
					$html .= '</form>';
				$html .= '</body>';
			$html .= '</html>';
			echo $html;
		}
		exit;
	}
	

	
	private static $headers = array();
	private static $charset = 'utf-8';
	private static $content_type = 'text/html';

	private static function start_buffer() {
		ob_start();
	}

	// [plugin events]
	// onRenderTemplate
	
	// onTemplateGetName
	// onBeforeTemplate

	// onBeforeBody
	// onAfterBody

	private static function render_buffer() {
		$buffer = ob_get_clean();
		$replaces = array('<include:head />','<include:scripts />','<include:body_start />','<include:body_end />');
		/* $insert_before = '';
		$insert_after = '';
		if (strpos($buffer,'<include:body_') !== false) {
			$insert_before = plugins::getBeforeBodyInsert();
			$insert_after = plugins::getAfterBodyInsert();
		} */

		 debug::timer_stop('page.generated','page generated');

		 debug::timer_stop('page.total','page total');
		
		// $app_base = self::app_base();
		
		$script_params = scripts::getParams();
		$script_params['app_base'] = self::app_base();
		
		// $global_vars = '<script type="text/javascript">var global_params = {"app_base":"'.$app_base.'"} </script>';
		$global_params = '<script type="text/javascript">var global_params = '.json_encode($script_params).' </script>';
		
		$insert_before = plugins::getBeforeBodyInsert();
		$insert_after = plugins::getAfterBodyInsert();
		$replaces_to = array(head::getHead(),$global_params.scripts::getScripts(),$insert_before,$insert_after); // console::getLog()
		// self::session()->get('debug',false)
		echo str_replace($replaces,$replaces_to,$buffer);
	}
	
	
	// TODO: move headers functionality to lib.headers
	private static function render_headers() {
		$charset_header = self::$content_type.'; charset='.self::$charset;

		self::setHeader('Content-Type', $charset_header);
		head::meta('Content-Type', $charset_header);
		
		// header('Content-Type: text/html; charset=utf-8');
		foreach (self::$headers as $header => $val) {
			header($header.': '.$val);
		}
	}
	
	public static function setCharset($charset) {
		if ($charset) {
			self::$charset = $charset;
		}
	}
	
	public static function setContentType($content_type) {
		if ($content_type) {
			self::$content_type = $content_type;
		}
	}
	
	// core::setHeader(..., ...);
	// note: dont use this function to set content-type or charset, use core::setCharset and core::setContentType instead
	public static function setHeader($header, $value) {
		self::$headers[$header] = $value;
	}

	public static function secret() {
		$app = self::app();
		return $app::config_get('secret','');
	}

	public static function checkToken($token) {
		return ( request::get($token,'0','post') === '1' );
	}

	private static function secureToken($s) {
		$token = implode(':', array(
			self::secret(),
			server::get('http_user_agent'),
			session_id(),
			$s
		));

		return md5($token);
	}

	public static function getToken($s) {
		$secure_token = '';
		$res = plugins::triggerEvent('getSecureToken', array($s, &$secure_token));
		if (!$res) {
			$secure_token = self::secureToken($s);
		}
		return $secure_token;
	}
	
	
	private static function render() {
		debug::timer_start('page.generated');

		self::start_buffer();
		
		$app = self::$app;
//		echo head::docType()."\n";
//		echo $app::render();
		$buffer = $app::render();
		
		plugins::triggerEvent('appRender', array(&$buffer));
		echo head::docType()."\n";
		echo $buffer;
		
		self::render_headers();

		self::render_buffer();
	}

	// private static function finalize() {
		// session::save();
	// }

	public static function run() {
		self::init();
		self::render();
		// self::finalize();
		return true;
	}

}
