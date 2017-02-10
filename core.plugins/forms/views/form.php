<?php
defined ("CORE_EXEC") or die('Access Denied');

list($action, $class, $name, $method, $attrs, $fields, $toolbar) = $plugin::params(array(
	'action' => '',
	'class' => '',
	'name' => '',
	'method' => 'post',
	'attr' => array(),
	'fields' => array(),
	'toolbar' => null
));

// echo '<form name="'..'" action="" method="post">'; // enctype="mutlipart-formdata"

if ($name) {
	$attrs['name'] = $name;
}


$classes = array();

if (isset($attrs['class']) && is_string($attrs['class'])) {
	$classes = explode(' ',$attrs['class']);
}

if (is_string($class)) {
	$class = explode(' ',$class);
}

$classes = array_merge(array('form'),$classes,$class);


$attrs['class'] = implode(' ',$classes);

if ($method) {
	$attrs['method'] = $method;
}

$attrs['action'] = route::app();

$attrs = '';
foreach ($attrs as $name => $val) {
	$attrs .= ' '.$name.'="'.$val.'"';
}

$toolbar_html = '';
$toolbar_top = false;
if ($toolbar) {
	if (isset($toolbar['position'])) {
		$toolbar_top = ($toolbar['position'] == 'top');
		unset($toolbar['position']);
	}
	$toolbar_html = $plugin::view('toolbar',$toolbar);
}

echo '<form'.$attrs.'>';

	if ($toolbar_top) {
		echo $toolbar_html;
	}

	foreach ($fields as $fld_name => $field) {
		;
	}

	if (!$toolbar_top) {
		echo $toolbar_html;
	}
	
	echo '<input type="hidden" name="action" value="'.$action.'"/>';

echo '</form>';
