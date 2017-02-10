<?php
defined ("CORE_EXEC") or die('Access Denied');

// list() = $widget::params(array( ));

// styles::import('core.widgets/widget.clock/css/widget.list.less');

// scripts::import('/core.widgets/widget.clock/js/widget.clock.js');

$widget::script('widget.clock.js');

// $widget::style('widget.list.less');


// $layout = 'ul'; li
// $layout = 'div'; div



echo '<div class="widget-clock">';
	echo date("H:i");
	// echo '12:37';
echo '</div>';
