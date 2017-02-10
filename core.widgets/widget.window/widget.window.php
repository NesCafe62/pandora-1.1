<?php
defined ("CORE_EXEC") or die('Access Denied');

list($title,$insert_before, /*$form, */ $content,$width,$height,$id,$class,$show_caption) = $widget::params(array(
	'title' => '',
	'insert_before' => '',
//	'form' => true,
	'content' => '',
	'width' => 750,
	'height' => 0,
	'id' => 'popup_id',
	'class' => '',
	'show_caption' => true
));



// scripts::import('core.widgets/widget.window/js/widget.window.js');
$widget::script('widget.window.js');

$widget::style('widget.window.less');


$window_params = '';
if ($width) {
	$window_params .= 'data-width="'.$width.'" ';
}
if ($height) {
	$window_params .= 'data-height="'.$height.'" ';
}

if (is_array($class)) {
	$class = implode(' ',$class);
}
if ($class) {
	$class = ' '.$class;
}

echo '<div id="'.$id.'" class="window-wrap" style="visibility: hidden">'; // div('#'.$id.'.window-wrap style:"visibility: hidden" {');
	// echo div('.window-overlay');
	echo '<div class="window-overlay"></div>';
	echo '<div class="window'.$class.' effect-fade_bottom" '.$window_params.'>';
	// echo div('.window '.$window_params.'{'); // style:"'.$style.'" {'); //style:"top: 50%'.str_condition($width,$styles.'; width: '.$width.'px').'" {');
		if ($show_caption) {
			echo '<div class="caption">';
			// echo div('.caption {');
				echo '<div class="title">';
				// echo div('.title {');
					echo $title; //$plugin::lang('send_activation_title');
					// echo html_link('.close href:'.route::url('#'),div('.icon'));
					echo '<a class="close" href="#"><div class="icon"></div></a>';
				// echo div('}');
				echo '</div>';
			// echo div('}');
			echo '</div>';
		}
		echo '<div class="window-cont">'; //  form
		// echo div('.cont-wrap'.str_condition($form,'.form').' {'); // '.str_condition($height,'style:"height: '.$height.'px" ').'{');
			echo $insert_before;
			/* if ($form) {
				echo html_('form .show action:""',$cont);
			} else {
				echo $cont;
			} */
			echo $content;
				//echo div('.message',$plugin::lang('send_activation'));
		// echo div('}');
		echo '</div>';
	// echo div('}');
	echo '</div>';
// echo div('}');
echo '</div>';
