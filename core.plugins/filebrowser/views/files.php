<?php
defined ("CORE_EXEC") or die('Access Denied');

$plugin::style('plugin.filebrowser.less');

// scripts::import('core/js/core.actions.js');

// $messages = $plugin::getLogMessages();

$files = $plugin::getFiles('/');


$theme = 'dark';

if ($theme) {
	$theme = ' theme-'.$theme;
}

echo '<div class="filebrowser-files '.$theme.'">';
	foreach ($files as $file) {
		echo '<div class="item">';
			echo 'file';
		echo '</div>';
	}
echo '</div>';
