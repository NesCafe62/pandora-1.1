<?php
defined ("CORE_EXEC") or die('Access Denied');

list($messages) = $plugin::params(array(
	'messages' => array()
));

foreach ($messages as $msg) {
	// echo div('.messages-item.'.$msg['level'].' {');

	/* $classes = '';
	if ($msg['label'] === '') {
		$classes = ' no-label';
	} */
	echo '<div class="messages-item '.$msg['classes'].'">'; // .$msg['level'].$classes.'">';
		echo '<div class="channel">'.$msg['channel'].'</div>';
		echo '<div class="message-wrap">';
			foreach ($msg as $key => $val) {
				if ( ($key === 'classes') || ($key === 'channel') ) continue;
				// if ($key === 'level') continue;
				// if (($key === 'label') && ($val === '')) continue;
				echo '<div class="'.$key.'">'.$val.'</div>'; // div('.'.$key,$val);
			}
		echo '</div>';
	echo '</div>';
	// echo div('}');
}