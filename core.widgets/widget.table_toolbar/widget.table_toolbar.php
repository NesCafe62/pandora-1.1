<?php
defined ("CORE_EXEC") or die('Access Denied');

$toolbar = $widget::getParams();

$toolbar_align = 'right';
if (isset($toolbar['align'])) {
	$toolbar_align = $toolbar['align'];
	unset($toolbar['align']);
}

$class = '';
if (isset($toolbar['class'])) {
	$class = ' '.$toolbar['class'];
	unset($toolbar['class']);
}

if ($toolbar_align) {
	$toolbar_align = ' align-'.$toolbar_align;
}

styles::import('/core.widgets/widget.table/css/widget.table.less');

scripts::import('/core.widgets/widget.table/js/widget.table.js');

echo '<div class="toolbar'.$toolbar_align.$class.'">';

	// 'toolbar' => array(

		// align: left|right

		// type: html
			//
			// 

		// type: label
			// align
			// label

			// or
			
			// $field = label

		// type: button
			// align
			// label
			// name
			// enabled
			// action

			// or
			
			// $field = name

		// type: hidden
			// $field = value

		// type: input
			// align
			// label
			// placeholder
			// disabled
			// value

		// type: checkbox
			// align
			// label
			// disabled
			// checked
			// value

		// type: filter
			// align
			// label
			// placeholder
			// value
			// options

	//	'filter:spec' => array(
	//		'placeholder' => 'Направление',
	//		'options' => $spec_names
	//	),
	//	'line-break',
	//	'filter:year' => array(
	//		'value' => '2016',
	//		'options' => $years
	//	),
	//	'filter:qualification' => array(
	//		// 'value' => -1,
	//		'options' => $qualifications
	//	)
	//),


	foreach ($toolbar as $key => $field) {

		$alias = $key;
		if (!is_string($key) && is_function($field)) {
			$type = 'html';
		} else {
			if (strpos($key,':') !== false) {
				list($type, $alias) = split_str(':',$key); // extend_arr( , 2);
				// $field = extend($field, array('type' => $type));
			}
		}

		if ($field === 'line-break') {
			$type = 'line-break';
		}

		switch ($type) {
			
			case 'line-break':
				
				echo '<br class="divider" />';
				break;
			
			case 'filter':
				$label = '';
				$placeholder = '';
				$align = '';
				$value = '';
				
				// console::log($field);

				if (array_key_exists('options',$field)) {
					list($label, $align, $placeholder, $value, $field_options, $is_disabled, $hidden_attribs) = array_to_list($field,
						array('label', 'align', 'placeholder', 'value', 'options', 'disabled', 'hidden_attribs')
					);
					if ($value === null) $value = '';

				} else {
					$field_options = $field;
				}

				if ($placeholder) {
					$placeholder = ' placeholder="'.$placeholder.'"';
				}

				if ($align) {
					$align = ' align-'.$align;
				}

				if ($hidden_attribs) {
					if (!is_array($hidden_attribs)) {
						$hidden_attribs = explode(' ',trim($hidden_attribs));
					}
					$placeholder .= ' data-hidden_attribs="'.implode(' ',$hidden_attribs).'"';
				}


				$option_value = '';
				$option_attribs = array();
				if (isset($field_options[$value])) {
					$option_value = $field_options[$value];
					
					if (is_array($option_value)) {
						$option_value = $option_value[1];
						
						if ($hidden_attribs) {
							if (isset($option_value[2])) {
								$option_attribs = $option_value[2];
							}
						}
					}
					
				}

				$disabled_class = '';
				if ($is_disabled) {
					$disabled_class = ' disabled';
				}

				echo '<div class="toolbar-item'.$align.' filter filter-'.$alias.'">';
					if ($label) {
						echo '<label>';
							echo '<div class="label">'.$label.'</div>';
					}
							echo '<div class="filter-wrap'.$disabled_class.'">';
								echo '<input type="text"'.$placeholder.' name="filter_'.$alias.'_inp" value="'.$option_value.'">';
								echo '<input type="hidden" name="filter_'.$alias.'" value="'.$value.'">';
								if ($hidden_attribs) {
									foreach ($hidden_attribs as $attr) {
										$attr_val = '';
										if (isset($option_attribs[$attr])) {
											$attr_val = $option_attribs[$attr];
										}
										echo '<input type="hidden" name="filter_'.$alias.'_'.$attr.'" value="'.$attr_val.'">';
									}
								}
								echo '<div class="button-dropdown">';
									echo '<div class="icon"></div>';
								echo '</div>';
								echo '<ul class="dropdown">';
									foreach ($field_options as $val => $option) {
										$option_text = '';
										$attribs = '';
										if (is_array($option)) {
											list($option, $option_text, $attribs) = extend_arr($option,3);
											$option_text = ' data-text="'.$option_text.'"';
										}
										if (is_array($attribs)) {
											foreach ($attribs as $attr => $attr_val) {
												if ($attr !== 'text') {
													$option_text .= ' data-'.$attr.'="'.$attr_val.'"';
												}
											}
										}
										if ($val === '') {
											$option_text .= ' class="default"';
										}
										echo '<li data-val="'.$val.'"'.$option_text.'>'.$option.'</li>';
									}
								echo '</ul>';
							echo '</div>';
					if ($label) {
						echo '</label>';
					}
				echo '</div>';
			
				break;
				
			case 'button':
				$classes = '';
				$attribs = '';
				if (is_string($field)) {
					$field = array(
						'name' => $field
					);
				}

				if (!isset($field['url'])) {
					$field['url'] = '#';
				}
				
				list($label, $align, $btn_classes, $name, $enabled, $target, $is_disabled, $action, $url) = array_to_list($field,
					array('label', 'align', 'class', 'name', 'enabled', 'target', 'disabled', 'action', 'url')
				);
				if ($btn_classes) {
					$btn_classes = ' '.$btn_classes;
				}

				if ($align) {
					$align = ' align-'.$align;
				}

//				if ($enabled === false) {
//					$btn_classes .= ' disabled';
//				} else 
				if (is_string($enabled) && ($enabled !== '')) {
					$attribs = ' data-enabled="'.$enabled.'"';
				}

				if ($is_disabled) {
					$btn_classes .= ' disabled';
				}

				if (starts_with($action,'url:')) {
					$url = remove_left($action,'url:');
					$action = '';
				}

				echo '<div class="toolbar-item'.$align.' toolbar-button button-'.$alias.$classes.'"'.$attribs.'>';
			//		if ($label) {
			//			echo '<label>';
			//				echo '<div class="label">'.$label.'</div>';
			//		}
							// $url = '#';
							/* if (starts_with($action,'url:')) {
								$url = remove_left($action,'url:');
								$action = '';
							} */
							echo '<a'.(($action) ? ' data-button_action="'.$action.'"' : '').(($target) ? ' target="'.$target.'"' : '').' href="'.$url.'" class="button '.$alias.$btn_classes.'">';
							echo $name;
							echo '</a>';
			//		if ($label) {
			//			echo '</label>';
			//		}
				echo '</div>';
				
				break;
			
			case 'hidden':
				$value = $field;
				echo '<input type="hidden" name="'.$alias.'" value="'.$value.'">';
				
				break;
			
			case 'input':
				$class = '';
				$attribs = '';

				list($label, $align, $placeholder, $is_disabled, $value) = array_to_list($field,
					array('label', 'align', 'placeholder', 'disabled', 'value')
				);

				if ($placeholder) {
					$placeholder = ' placeholder="'.$placeholder.'"';
				}

				if ($align) {
					$align = ' align-'.$align;
				}

				if ($is_disabled) {
					$class .= ' disabled';
					// $attribs .= ' disabled="disabled"';
				}


				echo '<div class="toolbar-item'.$align.' toolbar-input input-'.$alias.$class.'">';
					if ($label) {
						echo '<label>';
							echo '<div class="label">'.$label.'</div>';
					}
							echo '<input type="text"'.$placeholder.' name="'.$alias.'" value="'.$value.'"'.$attribs.'>';
					if ($label) {
						echo '</label>';
					}
				echo '</div>';

				break;

			case 'checkbox':
				$class = '';
				$attribs = '';
				
				list($label, $align, $is_disabled, $value, $checked) = array_to_list($field,
					array('label', 'align', 'disabled', 'value', 'checked')
				);

				if ($align) {
					$align = ' align-'.$align;
				}

				if ($is_disabled) {
					$class .= ' disabled';
					// $attribs .= ' disabled="disabled"';
				}

				if ($value !== null) {
					$attribs .= ' value="'.$value.'"';
				}
				if ($checked) {
					$class .= ' checked';
					$attribs .= ' checked="checked"';
				}

				echo '<div class="toolbar-item'.$align.' toolbar-checkbox checkbox-'.$alias.$class.'">';
					if ($label) {
						echo '<label>';
							echo '<div class="label">'.$label.'</div>';
					}
							echo '<input type="checkbox" name="'.$alias.'"'.$attribs.'>';
					if ($label) {
						echo '</label>';
					}
				echo '</div>';

				break;

			case 'html':

				ob_start();
				$res = $field();
				$html = ob_get_clean();

				if ( ($res !== false) || ($html !== '') ) {
					if ( ($html === '') && is_string($res) ) {
						$html = $res;
					}
				}

				if ($alias) {
					$html = '<div class="'.$alias.'">'.$html.'</div>';
				}
				echo $html;

				break;
				
			case 'label':
			default:

				$align = '';
				if (is_array($field)) {
					list($label, $align, $value) = array_to_list($field,
						array('label', 'align', 'value')
					);
				} else {
					$label = $field;
				}

				if (isset($field['value'])) {
					$label .= ':<span class="value">'.$value.'</span>';
				}

				if ($align) {
					$align = ' align-'.$align;
				}

				echo '<div class="toolbar-item'.$align.' toolbar-label label-'.$alias.'">';
					echo $label;
				echo '</div>';
			
				break;
		}
	}

	echo '<div class="clearfix"></div>';
echo '</div>';
