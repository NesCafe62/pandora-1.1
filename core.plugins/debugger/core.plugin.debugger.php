<?php
namespace core;
defined ("CORE_EXEC") or die('Access Denied');

use \console;

use \core;
use \route;
use \request;
use \debug;

use \strings;


class debugger extends plugin {

	// [section: Routing]
	protected static function routing($routes) {
		return extend(array(
			':get{$debug=on}' => function($vars) { // , $self) {
				self::action('console.open');
			},
			':get{$debug}' => function($vars) { // , $self) {
				self::action('console.close');
			}
		), $routes);
	}

	// [section: Actions]
	protected static function actions($actions) {
//		$self = self::plugin_name();
		
		return self::extendActions(array(
			'console.open:public' => function($self) {
				$self::setConsoleEnabled(true);
			},
			'console.close:public' => function($self) {
				$self::setConsoleEnabled(false);
				if (!core::is_ajax()) {
					if (request::has('debug')) {
						core::redirect(route::url_removeParams('debug'));
					}
				}
			}
		), $actions);
	}

	// [section: Events]
	protected static $registerEvents = array(
		'afterBody',
		'beforeAjax',
		'afterAjax'
	);

	public static function onBeforeAjax($plugin, $action) {
		debug::addLog(
			array(
				'label' => 'ajax:',
				'level' => 'console',
				'message' => 'plugin: \''.$plugin.'\' action: \''.$action.'\'',
				'override' => true
			),
			'action',
			true
		);
	}

	public static function onAfterAjax(&$res) {
		$res_ = $res;
		if (isset($res['msg']) && ($res['msg'] === '') ) {
			$res_ = $res['result'];
		}
		debug::addLog(
			array(
				'label' => '',
				'level' => 'console',
				'message' => '<pre>'.strings::dump($res_).'</pre>',
				'override' => true
			),
			'result'
		);
		$messages = self::getLogMessages();
		if ($messages) {
			if ($res === 'empty') {
				$res = array(
					'msg' => '',
					'result' => false
				);
			}
			$res['debug_messages'] = self::view('messages', array(
				'messages' => $messages
			));
		}
	}

	public static function onAfterBody(&$html) {
		$debug_state = self::isConsoleEnabled();
		if ($debug_state) {
			$html .= self::view('console');
		}
	}


	// [section: Main]
	protected static function setConsoleEnabled($enabled = true) {
		self::session()->set('console.enabled',$enabled);
	}

	public static function isConsoleEnabled() {
		return self::session()->get('console.enabled',false);
	}


	public static function setLogLevel($log_level = 'default') {
		self::session()->set('console.log_level',$log_level);
	}

	// debugger::set('log_level','default sql');

	public static function getLogLevel() {
		$default_log_level = self::get('log_level','default');
		return self::session()->get('console.log_level',$default_log_level);
	}
	
	public static function getLogMessages() {
		$log_level = self::getLogLevel();
		$messages = debug::getLog($log_level); // 'all'); // 'default' // sql

		foreach ($messages as &$msg) {
			$channel = $msg['channel'];
			if ($channel === 'system' && $channel === 'system') {
				$channel = $msg['level'];
			}

			$message = $msg['message'];
			
			$channel_class = $channel;
			if (in_array($channel,array('plugin','event','routing','action','result','sql'))) {
				$channel_class = 'log';
			}
			
			$classes = array($channel_class);
			if ($msg['label'] !== '') {
				$message = '<span class="label">'.$msg['label'].'</span>'.$message;
			} else {
				$classes[] = 'no-label';
			}
			
			$line = $msg['line'];
			if ($line !== '') {
				$line = self::lang('console_message_line',$line);
			}
			$msg = array(
				'channel' => ucfirst($channel),
				'message' => $message,
				'file' => $msg['file'],
				'line' => $line,
				'classes' => implode(' ',$classes)
			);
		}
		return $messages;
	}

}
