<?php
defined ("CORE_EXEC") or die('Access Denied');

list($rows, $format, $fields, $row_groups, $prepend_row, $empty_msg, $append_row, $filter_func, $row_func, $toolbar, $options, $caption, $classes) = $widget::params(array( // , $layout
	'items' => array(),
	'format' => 'html',
	'fields' => array(),
	'row-groups' => false,
	'prepend_row' => '',
	'empty-msg' => '',
	'append-row' => '',
	'filter-func' => null,
	'row-func' => null,
	'toolbar' => null,
	// 'layout' => 'table',
	'options' => array(),
	'caption' => '',
	'class' => ''
));

list($theme, $show_heading, $use_wrap, $table_classes, $heading_class, $heading_attribs, $speed_up) = $widget::params(array(
	'theme' => 'flat',
	'show-heading' => true,
	'wrap' => true,
	'table-classes' => '',
	'heading-class' => '',
	'heading-attribs' => array(),
	'speed-up' => false
), $options);

$speed_up_rows = 500;
$speed_up_rows_step = 500;

// 	'theme' => 'flat',

$widget::style('widget.table.less');

// $is_func = is_function($row_func);

if (is_string($fields)) {
	// $fields = explode(',',$fields);
	if (contains($fields,',')) {
		$expr = '/\s*,\s*/';
	} else {
		$expr = '/\s+/';
		$fields = trim($fields);
	}
	$fields = preg_split($expr,$fields);
	// preg_match_all($expr,trim($fields),$matches);
	// $fields = $matches[0];
}


if (is_string($classes)) {
	if ($classes === '') {
		$classes = array();
	} else {
		$classes = explode(' ',$classes);
	}
}

if (is_string($table_classes)) {
	if ($table_classes === '') {
		$table_classes = array();
	} else {
		$table_classes = explode(' ',$table_classes);
	}
}

if (!$use_wrap) {
	$table_classes = array_merge($classes, $table_classes);
}



if ($theme) {
	if ($use_wrap) {
		$classes[] = 'theme-'.$theme;
	}
}

$widget_obj = new stdClass();
$widget_obj->format = $format;

$has_check_all = false;

$is_function = array();
$checkbox_field = array();
if (count($fields) == 0) {
	$fields = array();
	if ($rows) {
		$first_row = first($rows);
		
		foreach ($first_row as $field => $val) {
			// $fields[$field] = $field;
			$fields[] = $field;
			$is_function[] = false;
		}
	}
	$field_titles = $fields;
} else {
	$field_titles = array();
	foreach ($fields as $field_title => $field) {
		$is_func = is_function($field);
		$is_function[] = $is_func;

		if ($field_title === ':check-all') {
			$has_check_all = true;
		}

		$check_id_field = false;
		$check_group_field = '';
		if (!$is_func && starts_with($field,':checkbox')) {
			$check_id_field = 'id';
			if (preg_match('#\[([^]]+)\]#',$field,$matches)) {
				$check_id_field = $matches[1];
			}
			if (preg_match('#:check_group\[([^]]+)\]#',$field,$matches)) {
				$check_group_field = $matches[1];
			}
			$class = 'checkbox-'.$check_id_field;
		}
		$checkbox_field[] = $check_id_field;
		$checkbox_group_field[] = $check_group_field;

		if ($is_func) {
			$row = new stdClass();
			$class = '';
			ob_start();
			$field($widget_obj, $row, $class);
			ob_get_clean();
		} else if (!$check_id_field) {
			$class = $field;
		}
		if ($class === '') {
			$field_titles[] = $field_title;
		} else {
			$field_titles[$class] = $field_title;
		}
	}
}

$items_empty = (count($rows) == 0);


$has_row_groups = ($row_groups) ? true : false;

$has_row_func = is_function($row_func);

$has_filter_func = is_function($filter_func);

if ($format === 'html') {
	if ($toolbar || $speed_up || $has_check_all) {
		// scripts::import('/core/js/core.widget.js');
		// scripts::import('/core.widgets/widget.table/js/widget.table.js');
		$widget::script('widget.table.js');
	}
}

// '<script type="text/javascript" src="http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js"></script>'

if ($format !== 'html') {
	$report_caption = $caption;
	$caption = '';
	$use_wrap = false;
	ob_start();
}


if ($use_wrap) {
	echo '<div class="table-wrap'.( (count($classes) > 0) ? ' '.implode(' ',$classes) : '' ).'">';
}

	if ($toolbar) {

		echo widgets::render('table_toolbar',$toolbar);

	}

	$tbl_classes = ( (count($table_classes) > 0) ? ' '.implode(' ',$table_classes) : '' );
	
	// echo '<table class="table'.( (!$use_wrap && (count($classes) > 0)) ? ' '.implode(' ',$classes) : '' ).$tbl_classes.'">';
	echo '<table class="table'.$tbl_classes.'">';
		echo '<tbody>';

		if ($caption) {
			echo '<caption>'.$caption.'</caption>';
		}

		if (is_function($prepend_row)) {
			ob_start();
			$res = $prepend_row($widget_obj);
			$prepend_row = ob_get_clean();
			if ( ($prepend_row === '') && is_string($res) ) {
				$prepend_row = $res;
			}
		}
		echo $prepend_row;

		$heading_html = '';
		foreach ($field_titles as $class => $title) {
			$row_class = '';

			if ($title === ':check-all') {
				// $title = '<input type="checkbox" checked="" />';
				$check_id_field = remove_left($class,'checkbox-'); // trim($class,':');
				$class = 'checkbox-'.$check_id_field.' check-all';
				/* if (preg_match('#\[([^]]+)\]#',$class,$matches)) {
					$check_id_field = $matches[1];
				} */
				$title = '<input type="checkbox" name="check_all_'.$check_id_field.'" />';
			}
			
			if (is_string($class) && ($class !== '')) {
				$row_class = ' class="field-'.$class.'"';
			}
			$heading_html .= '<th'.$row_class.'>'.$title.'</th>';
		}
		$widget_obj->heading = $heading_html;
		
		if ($show_heading) {
			$row_class = $heading_class;
			$row_attribs = '';
			if (is_array($heading_class)) {
				$row_class = implode(' ',$heading_class);
			}

			foreach ($heading_attribs as $attr => $val) {
				$row_attribs .= ' '.$attr.'="'.$val.'"';
			}
			
			if ($row_class) $row_class = ' class="'.$row_class.'"';
				
			echo '<tr'.$row_class.$row_attribs.'>';
				echo $heading_html;
			echo '</tr>';
		}


		$row_is_object = null;

		$row_group_id = array();

		$row_id = 0;

		$speed_up_hide_rows = false;

		$rows_by_id = array_values($rows);
		
		foreach ($rows as $row_key => $row) {

			// $_row = $row;

			if (is_scalar($row)) {
				$row = array();
			} else {
				if ($row_is_object === null) {
					$row_is_object = !is_array($row);
				}
			}
			
			if (!$row_is_object) {
				$row = array_to_object($row);
			}
			$widget_obj->item = $row;
			$widget_obj->row_id = $row_id;
			$widget_obj->row_key = $row_key;


			//
			$row_html = '';
			$i = 0;
			$row_fields = array();
			foreach ($fields as $field) {
				if ($is_function[$i]) {
					$fld_class = '';

					ob_start();
					$res = $field($widget_obj, $row, $fld_class);
					$val = ob_get_clean();

					if ( ($val === '') && is_string($res) ) {
						$val = $res;
					}
				// } else if (starts_with($field,':checkbox')) {
				} else if (isset($checkbox_field[$i]) && $checkbox_field[$i]) {
					/* $check_id_field = trim($field,':');
					if (preg_match('#\[([^]]+)\]#',$class,$matches)) {
						;
					} */

					$checkbox_attr = '';
					
					$check_id_field = $checkbox_field[$i];
					$check_group_field = $checkbox_group_field[$i];
					$fld_class = 'checkbox-'.$check_id_field;

					// if (!isset($_row->$check_id_field)) {
					//	; // error
					// }
					$checkbox_name = $check_id_field.'_'.($row->$check_id_field);
					$checkbox_name = $check_id_field.'['.($row->$check_id_field).']';

					if ($check_group_field) {
						$check_group_field_val = $row->$check_group_field;
						if ((string)$check_group_field_val !== '') {
							$fld_class .= ' check-group-item';
							$checkbox_attr .= ' data-check-group="'.$check_group_field_val.'"';
						}
					}
					
					$val = '<input type="checkbox" name="'.$checkbox_name.'"'.$checkbox_attr.'/>';
				} else {
					$val = '';
					if (isset($row->$field)) {
						$val = $row->$field;
					}
					$fld_class = $field;
				}
				if ($val === '') $val = '&nbsp;';
				$td_class = '';
				if ($fld_class) {
					$td_class = ' class="field-'.$fld_class.'"';
					$row_fields[$fld_class] = $val;
				} else {
					$row_fields[] = $val;
				}
				$row_html .= '<td'.$td_class.'>';
					$row_html .= $val;
				$row_html .= '</td>';
				$i++;
			}

			$widget_obj->row_fields = $row_fields;
			$widget_obj->row = $row_html;
			// $widget_obj->skiprow = false;


			$show_row = true;
			if ($has_filter_func) {
				$show_row = $filter_func($widget_obj, $row);
			}
			$widget_obj->skiprow = !$show_row;

			//
			$row_groups_html = '';
			$row_group_complete = true;
			if ($has_row_groups) {
				$new_row = false;
				$widget_obj->changing_key = '';
				$group_count = 1;
				$group_rows = array();
				$row_group_complete = false;
				$first_key = null;
				$upper_groups = array();
				foreach ($row_groups as $key => $func) {
					$key_is_set = !is_numeric($key);
					if ($first_key === null) {
						$first_key = $key;
					}
					if ($key_is_set) {
						if ( !isset($row_group_id[$key]) || ($row_group_id[$key] !== $row->$key) ) {
							$row_group_id[$key] = $row->$key;
							if (!$new_row) {
								$widget_obj->changing_key = $key;
								$new_row = true;
							}
						}
					}
					if ($new_row) {
						if ($first_key === $key) {
							$row_group_complete = false;
						}
						$row_class = '';

						if ($key_is_set) {
							$group_count = 0;
							$group_rows = array();
							$r_id = $row_id;
							$upper_key_changed = false;
							while (isset($rows_by_id[$r_id])) {
								$_row = $rows_by_id[$r_id];
								if (is_array($_row)) {
									$_row = array_to_object($_row);
								}
								if ( (!isset($_row->$key)) || ($_row->$key === null) || ($row->$key !== $_row->$key) ) {
									break;
								}
								foreach ($upper_groups as $upper_key) {
									// if ($row->$upper_key !== $_row->$upper_key) {
									if ( (!isset($_row->$upper_key)) || ($_row->$upper_key === null) || ($row->$upper_key !== $_row->$upper_key) ) {
										$upper_key_changed = true;
										break;
									}
								}
								if ($upper_key_changed) {
									break;
								}
								$r_id++;
								$group_rows[] = $_row;
								$group_count++;
							}
						}

						$row_obj = array_to_object(array(
							'class' => array(),
							'attr' => array()
						));

						ob_start();
						$res = $func($widget_obj, $row, $row_obj /* $row_class */, $group_count, $group_rows);
						$_row_html = ob_get_clean();

						if ( ($res !== false) || ($_row_html !== '') ) {
							if ( ($_row_html === '') && is_string($res) ) {
								$_row_html = $res;
							}

							$row_class = $row_obj->class;
							if (is_array($row_obj->class)) {
								$row_class = implode(' ',$row_obj->class);
							}

							$row_attribs = '';
							foreach ($row_obj->attr as $attr => $val) {
								$row_attribs .= ' '.$attr.'="'.$val.'"';
							}
							
							if ($row_class) $row_class = ' class="'.$row_class.'"';
							$row_groups_html .= '<tr'.$row_class.$row_attribs.'>'.$_row_html.'</tr>';
						}
					}
					if ($key_is_set) {
						$upper_groups[] = $key;
					}
				}
			}

			$row_id++;

			if ($speed_up) {
				if (($row_id > $speed_up_rows) && $row_group_complete) {
					$speed_up_rows += $speed_up_rows_step;
					echo '</tbody>';
					echo '<tbody class="speed-up" style="display: none;">';
				}
				/* $data_speed_up = '';
				if ($speed_up_hide_rows) {
					$data_speed_up .= ' data-speed_up="1" style="display: none;"';
				} */
			}


			echo $row_groups_html;

			if ($widget_obj->skiprow) continue;
			

			//
			$row_class = '';
			$row_attribs = '';
			if ($has_row_func) {

				$row_obj = array_to_object(array(
					'class' => array(),
					'attr' => array()
				));

				ob_start();
				$res = $row_func($widget_obj, $row, $row_obj /* $row_class */);
				$_row_html = ob_get_clean();
				
				if ( ($_row_html === '') && is_string($res) ) {
					$_row_html = $res;
				}
				if ($_row_html) {
					$row_html = $_row_html;
				}
				
				$row_class = $row_obj->class;
				if (is_array($row_obj->class)) {
					$row_class = implode(' ',$row_obj->class);
				}

				foreach ($row_obj->attr as $attr => $val) {
					$row_attribs .= ' '.$attr.'="'.$val.'"';
				}
				
				if ($row_class) $row_class = ' class="'.$row_class.'"';
			}
			echo '<tr'.$row_class.$row_attribs.'>'.$row_html.'</tr>';
		}

		if (is_function($append_row)) {
			ob_start();
			$res = $append_row($widget_obj);
			$append_row = ob_get_clean();
			if ( ($append_row === '') && is_string($res) ) {
				$append_row = $res;
			}
		} else if ($empty_msg) {
			$cols_count = count($field_titles);
			if ( ($format === 'html') || $items_empty ) {
				$append_row =
					'<tr class="empty-msg"'.(($items_empty) ? ' style="display: table-row"' : '').'>'.
						'<td colspan="'.$cols_count.'">'.$empty_msg.'</td>'.
					'</tr>';
			}
		}
		echo $append_row;

		echo '</tbody>';
	echo '</table>';

if ($use_wrap) {
	echo '</div>';
}

if ($format !== 'html') {
	$buffer = ob_get_clean();
	if ($format === 'excel') {
		$buffer = str_replace( '<br/>', "\n", $buffer);
		$buffer = strip_tags($buffer,'<table><tr><th><td>');

		$buffer = preg_replace('/\s*(class|style)=".*?"/','',$buffer);

		$buffer = str_replace( array(
			'<table>','</table>','<tr','</tr','<th','</th','<td','</td','index='
		), array(
			'','',"\n".'<Row','</Row',"\n".'<Cell ss:StyleID="bold"','</Cell',"\n".'<Cell','</Cell','ss:Index='
		), $buffer); // '<tbody>','</tbody>', '','',

		$buffer = preg_replace('/<Cell(.*?)>(.*?)<\/Cell>/','<Cell$1><Data ss:Type="String">$2</Data></Cell>',$buffer); // '<Data ss:Type="String"></Data>'


// ,'colspan=','rowspan='
// ,'ss:ExpandedColumnCount=','ss:ExpandedRowCount='
		$buffer = preg_replace_callback('/(colspan|rowspan)="(.*?)"/', function($matches) {
			return (($matches[1] == 'colspan') ? 'ss:MergeAcross' : 'ss:MergeDown').'="'.($matches[2]-1).'"';
		}, $buffer);

		echo '<?xml version="1.0"?>'."\n";
		echo '<?mso-application progid="Excel.Sheet"?>'."\n";
		echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">'."\n";
		echo '	<Styles>'."\n";
		echo '		<Style ss:ID="bold">'."\n";
		echo '			<Font ss:Bold="1"/>'."\n";
		echo '			<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'."\n";
		echo '		</Style>'."\n";
		echo '	</Styles>'."\n";
		echo '	<Worksheet ss:Name="'.$report_caption.'">'."\n";
		echo '		<Table>'."\n";
		$width = '120.00';
		$cols_count = count($field_titles);
		for ($i = 0; $i < $cols_count; $i++) {
			echo '			<Column ss:AutoFitWidth="1" ss:Width="'.$width.'" />'."\n";
		}
		echo $buffer;
		echo '		</Table>'."\n";
		echo '	</Worksheet>'."\n";
		echo '</Workbook>'."\n";
	}
}
