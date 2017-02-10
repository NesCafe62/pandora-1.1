<?php
defined ("CORE_EXEC") or die('Access Denied');

list($elements, $layout, $options, $item_func, $classes, $height_fix) = $widget::params(array(
	'elements' => array(),
	'layout' => 'div',
	'options' => array(),
	'item' => null,
	'class' => '',
	'height-fix' => false
));

list($aligns, $theme, $active_class) = $widget::params(array(
	'align' => 'left',
	'theme' => '',
	'active_class' => 'active'
),$options);

// var_dump($aligns);

$aligns = explode(' ',$aligns);

$direction = 'horizontal';
if ( in_array('vertical',$aligns) && !in_array('horizontal',$aligns) ) {
	$direction = '';
}

$align = ''; // 'left';
if (!in_array('left',$aligns)) {
	if ( in_array('right',$aligns) ) {
		$align = 'right';
	} else if ( in_array('middle',$aligns) ) {
		$align = 'middle';
	}
}

// 	'align' => 'left',
// 	'theme' => 'flat',

$widget::style('widget.list.less');

if (is_string($classes)) {
	if ($classes === '') {
		$classes = array();
	} else {
		$classes = explode(' ',$classes);
	}
}

// $layout = 'ul'; li
// $layout = 'div'; div


if ($align) {
	$classes[] = 'align-'.$align;
}

if ($direction) {
	$classes[] = $direction;
}

/* if (($align !== 'left') && in_array($align, array('right', 'middle'))) {
	$classes[] = 'align-'.$align;
}
if (($v_align !== 'horizontal') && in_array($v_align, array('horizontal', 'vertical'))) {
	$classes[] = 'align-'.$align;
} */

$tag = 'div'; $sub_tag = 'div';

if ($layout === 'ul') {
	$tag = 'ul'; $sub_tag = 'li';
}

$is_func = is_function($item_func);

if ($theme) {
	$classes[] = 'theme-'.$theme;
}

$height_limiter = '';
if ($height_fix) {
	$height_limiter = '<div class="height-fix"></div>';
}

echo '<'.$tag.' class="list'.( (count($classes) > 0) ? ' '.implode(' ',$classes) : '' ).'">';

	foreach ($elements as $key => $element) {
		$element_html = $element;
		if (is_function($element)) {
			ob_start();
			$res = $element($key);
			$element_html = ob_get_clean();
			if ( ($element_html === '') && is_string($res) ) {
				$element_html = $res;
			}
		}

		$class = '';
		if ($key && is_string($key)) $class = $key;
		
		$active = false;
		if ($is_func) {
			ob_start();
			$res = $item_func($element_html, $key, $active, $class);
			$el_html = ob_get_clean();
			if ($el_html !== '') {
				$element_html = $el_html;
			} else if (is_string($res)) {
				$element_html = $res;
			}
		}
		
		if ($class) $class = ' '.$class;
		
		echo '<'.$sub_tag.' class="item'.$class.( ($active) ? ' '.$active_class : '' ).'">';
			echo $height_limiter;

			echo $element_html;
		echo '</'.$sub_tag.'>';
	}

echo '</'.$tag.'>';
