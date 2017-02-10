<?php
defined ("CORE_EXEC") or die('Access Denied');

$plugin::style('plugin.debugger.less');

// console::log(request::get('action'),'action');

// 'action' => 'app.tasks:debugger/close';

// $dsff= $plugin::action('close');

// $close_action = route::url(array('action' => $fgfg)); // $plugin::routeAction('close'));

scripts::import('/core/js/jquery.min.js');

// scripts::import('/core.plugins/debugger/js/plugin.debugger.js');

$plugin::script('plugin.debugger.js');

scripts::import('/core/js/core.actions.js');

// $url = route::app('/materials/4545');
// build_url($url,array('action' => ''));


$messages = $plugin::getLogMessages();

$theme = 'dark';

if ($theme) {
	$theme = ' theme-'.$theme;
}

scripts::param('debug_mode',true);

echo '<div class="debugger-height"></div>';
echo '<div class="debugger-console'.$theme.'">';
	echo '<div class="resizing"></div>';
	echo '<div class="resize"></div>';
	echo '<div class="caption">';
		echo '<div class="title">'.$plugin::lang('console_title').'</div>';
		// echo '<a class="close" href="'.route::url($plugin::routeAction('close')).'">'; //  data-action="'.$plugin::getAction('close').'"
		// echo '<a class="close" data-action="'.$plugin::getAction('close').'" href="'.route::url($plugin::routeAction('close')).'">'; // for no-js variant
		echo '<a class="close" href="'.route::url('#').'">'; // /?debug=on&action=core\debugger/close">';
			// data-action="'.$plugin::getAction('console.close').'"
		// echo '<a class="close" data-act_ion="'.$plugin::getAction('close').'" href="'.route::url(auth_ldap2::routeAction('close')).'">'; // /?debug=on&action=core\debugger/close">';
			echo '<div class="icon"></div>';
		echo '</a>';

/*
		$actionClose = $plugin::fdgfg();
		a.close href:$actionClose {
			div.icon
		}
*/
	
	echo '</div>';
	echo '<div class="debug-content">';
		echo '<div class="messages">';
			//
			/*	$log[] = array(
					'level' => 'notice',
					'type' => 'Log',
					'message' => $msg['var'],
					'file' => '',
					'line' => ''
				); */
			
			// level:	Console
			// message:	label: message
			
			// file:	app/templates/main.php
			// :		строка
			// line:	62

			// level:	Fatal error
			// message:	error description
			// file:	app/templates/main.php
			// :		строка
			// line:	62

			echo $plugin::view('messages', array(
				'messages' => $messages
			));

		echo '</div>';
	echo '</div>';
echo '</div>';
