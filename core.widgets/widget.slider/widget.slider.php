<?php
defined ("CORE_EXEC") or die('Access Denied');

list($element, $slides, $slides_count, $wrap, $insert_before, $insert_after, $is_bg_slide, $active, $active_class, $options, $classes) = $widget::params(array(
	'element' => 'div',
	'slides' => null,
	'slides-count' => null,
	'wrap' => '',
	'insert_before' => '',
	'insert_after' => '',
	'bg-slide' => true,
	'active' => null,
	'active_class' => 'active',
	'options' => array(),
	'class' => ''
));

if (is_string($classes)) {
	$classes = explode(' ',$classes);
}

$classes[] = 'slider';
$classes = implode(' ',$classes);


if (!$slides && ($slides_count !== null) ) {
	$slides = array();
	for ($i = 0; $i < $slides_count; $i++) {
		$slides[] = '';
	}
}

/*
list($elements, $layout, $options, $item_func, $classes, $height_fix) = $widget::params(array(
	'elements' => array(),
	'layout' => 'div',
	'options' => array(),
	'item' => null,
	'class' => '',
	'height-fix' => false
));

list($aligns, $theme) = $widget::params(array(
	'align' => 'left',
	'theme' => ''
),$options); */

$wrap_open = '';
$wrap_close = '';

if ($wrap) {
	$arr = explode(' ',$wrap);
	foreach ($arr as $wrap_el) {
		list($tag,$class) = extend_arr(split_str('.',$wrap_el),2);
		if (!$tag) {
			$tag = 'div';
		}
		$wrap_class = '';
		if ($class) {
			$wrap_class = ' class="'.str_replace('.',' ',$class).'"';
		}
		$wrap_open .= '<'.$tag.$wrap_class.'>';
		$wrap_close .= '</'.$tag.'>';
	}
	
}


// scripts::import('core.widgets/widget.slider/js/widget.slider.js');
$widget::script('widget.slider.js');

$widget::style('widget.slider.less');

$active_id = 1;
if ($active !== null) {
	$active_id = $active;
}

echo '<'.$element.' class="'.$classes.'">';

	$bg_slide = '';
	$slides_html = '';
	
	$i = 0;
	foreach ($slides as $key => $slide) {
		$i++;
		$classes = array('slide', 'slide-'.$i);
		if ($i === $active_id) $classes[] = $active_class; // 'active';
		
		$slide_el = '';
		if ($slide) {
			$slide_el = $slide;
			if (isset($slide['type']) && ($slide['type'] === 'img')) {
				$slide_el = '<img src="'.$slide['src'].'"/>';
			}
		}
		if ($i === 1) $bg_slide = $slide_el;
		$slides_html .= '<div class="'.implode(' ',$classes).'">'.$slide_el.'</div>';
	}

	if ($is_bg_slide) {
		/* $bg_class = '';
		if ($active_id === 0) {
			$bg_class = ' active';
		} */
		// echo '<div class="slide-bg'.$bg_class.'">'.$bg_slide.'</div>';
		echo '<div class="slide-bg">'.$bg_slide.'</div>';
	}
	
	echo $insert_before;
	echo $wrap_open;

		echo $slides_html;

	echo $wrap_close;
	echo $insert_after;

echo '</'.$element.'>';