<?php
namespace core;

use \console;

use \debug;

defined ("CORE_EXEC") or die('Access Denied');

class plg_table extends plugin {

	public static function getItems($table_query, $params = array(), $default = array()) {
		list($table,$query) = extend_arr(split_str('.',$table_query),2);
		
		$err = false;
		if ($table === '') {
			$msg = 'PLG_TABLE_GET_ITEMS_TABLE_NOT_SET';
			$err = true;
		} else if ($query === '') {
			$msg = 'PLG_TABLE_GET_ITEMS_QUERY_NOT_SET';
			$err = true;
		}
		
		if ($err) {
			$plg_name = self::name();
			trigger_error(debug::_($msg,$plg_name.'::getItems('.$table_query.')'),debug::WARNING);
			return $default;
		}

		// $table_class2 = 'table_'.$table;
		$table_class = 'table'.ucfirst($table);
		$table_class2 = 'table_'.$table;
		
		if (!class_exists($table_class)) {
			$core_table_class = 'core\\'.$table_class;
			if (class_exists($core_table_class)) {
				$table_class = $core_table_class;
			}
		}

		/* deprecated { */
		if (!class_exists($table_class2)) {
			$core_table_class = 'core\\'.$table_class2;
			if (class_exists($core_table_class)) {
				$table_class2 = $core_table_class;
			}
		}
		/* } */
		
		if (!class_exists($table_class) && !class_exists($table_class2)) {
			$plg_name = self::plugin_name();
			trigger_error(debug::_('PLG_TABLE_GET_ITEMS_TABLE_CALSS_NOT_EXISTS',$plg_name.'::getItems('.$table_query.')',$table_class),debug::WARNING);
			return $default;
		}
		
		/* deprecated { */
		if (!class_exists($table_class) && class_exists($table_class2)) {
			$table_class = $table_class2;
			// !! trigger_error(debug::_('PLG_TABLE_GET_ITEMS_TABLE_CALSS_NAME_DEPRECATED',$table_class2),debug::DEPRECATED);
		}
		/* } */
		
		return $table_class::getItems($query,$params,$default);
		
		// $items = $table_class::getItems($query,);
		
		// return $items;
	}

	public static function getItem($table_query, $params = array(), $default = false) { // '__default_val') {
		list($table,$query) = extend_arr(split_str('.',$table_query),2);

		//if ($default === '__default_val') {
		//	$default = new \stdClass();
		//}

		$err = false;
		if ($table === '') {
			$msg = 'PLG_TABLE_GET_ITEM_TABLE_NOT_SET';
			$err = true;
		} else if ($query === '') {
			$msg = 'PLG_TABLE_GET_ITEM_QUERY_NOT_SET';
			$err = true;
		}
		
		if ($err) {
			$plg_name = self::name();
			trigger_error(debug::_($msg,$plg_name.'::getItem('.$table_query.')'),debug::WARNING);
			return $default;
		}

		// $table_class = 'table_'.$table;
		$table_class = 'table'.ucfirst($table);
		$table_class2 = 'table_'.$table;
		
		if (!class_exists($table_class)) {
			$core_table_class = 'core\\'.$table_class;
			if (class_exists($core_table_class)) {
				$table_class = $core_table_class;
			}
		}
		
		/* deprecated { */
		if (!class_exists($table_class2)) {
			$core_table_class = 'core\\'.$table_class2;
			if (class_exists($core_table_class)) {
				$table_class2 = $core_table_class;
			}
		}
		/* } */
		
		if (!class_exists($table_class) && !class_exists($table_class2)) {
			$plg_name = self::plugin_name();
			trigger_error(debug::_('PLG_TABLE_GET_ITEM_TABLE_CALSS_NOT_EXISTS',$plg_name.'::getItem('.$table_query.')',$table_class),debug::WARNING);
			return $default;
		}
		
		/* deprecated { */
		if (!class_exists($table_class) && class_exists($table_class2)) {
			$table_class = $table_class2;
			// !! trigger_error(debug::_('PLG_TABLE_GET_ITEM_TABLE_CALSS_NAME_DEPRECATED',$table_class2),debug::DEPRECATED);
		}
		/* } */
		
		return $table_class::getItem($query,$params,$default);
		
		// $items = $table_class::getItems($query,);
		
		// return $items;
	}

}
