<?php
defined ("CORE_EXEC") or die('Access Denied');

// styles::import('/core.widgets/widget.table/css/widget.table.less');

list($name, $value) = $widget::params(array(
	'name' => 'editor',
	'value' => ''
));

// scripts::import('/ext/trumbo/trumbowyg.min.js');
scripts::import('/ext/trumbo/trumbowyg.js');

scripts::import('/ext/trumbo/langs/ru.min.js');

$widget::script('widget.editor_trumbo.js');

styles::import('/ext/trumbo/ui/trumbowyg.css');

echo '<textarea class="editor-trumbo" name="'.$name.'">';
	echo $value;
echo '</textarea>';
