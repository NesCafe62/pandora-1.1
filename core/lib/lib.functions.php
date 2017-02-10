<?php
defined ("CORE_EXEC") or die('Access Denied');


function starts_with($x, $s) { // checks if $x string starts with $s
	return (strpos($x,$s) === 0);
}

function ends_with($x, $s) { // checks if $x string ends with $s
	return (strrpos($x,$s) === strlen($x)-strlen($s));
}

function remove_left($x, $s) { // removes $s from the begining of $x string and retruns result
	if ($s !== '') {
		if (starts_with($x,$s)) $x = substr($x,strlen($s));
		if ($x === false) $x = '';
	}
	return $x;
}

function remove_right($x, $s) { // removes $s from the end of $x string and retruns result
	if ($s !== '') {
		if (ends_with($x,$s)) $x = substr($x,0,strlen($x)-strlen($s));
		if ($x === false) $x = '';
	}
	return $x;
}

function length($arr) {
	if (!is_array($arr)) {
		trigger_error(debug::_('FUNCTIONS_LENGTH_ARG_IS_NOT_ARRAY',gettype($arr)),debug::WARNING);
		return false;
	}
	return count($arr);
}


function last(&$arr, $default = null) {
	$n = count($arr);
	if ($n > 0) {
		return $arr[$n-1];
	} else {
		return $default;
	}
}

function first_key(&$arr) {
	reset($arr);
	return key($arr);
}

function first(&$arr, $default = null) {
	$first_key = first_key($arr);
	if ($first_key === null) {
		return $default;
	}
	return $arr[$first_key];
}

function extract_keys(&$arr, $keys) {
	$is_arr = is_array($arr);
	if ($is_arr) {
		$arr2 = array();
	} else {
		$arr2 = new stdclass();
	}
	foreach ($keys as $key) {
		if ($is_arr) {
			$arr2[$key] = $arr[$key];
			unset($arr[$key]);
		} else {
			$arr2->$key = $arr->$key;
			unset($arr->$key);
		}
	}
	return $arr2;
}

/*
$items = each(
	$plugin::getItems('students.rbooks', array(
		'where' => array('students.id' => 2)
	)),
	function($item) {
		$item->rbook = $item->id_rbook;
	}
);
*/
/*
function each_item($items, $func) {
	$_items = array();
	foreach ($items as &$item) {
		$res = $func($item);
		if ($res !== false) {
			$_items[] = $item;
		}
	}
	return $_items;
} */


// for stdClass cloning (!not recursive)
function clone_obj($obj) {
	return (object) ((array) $obj);
}

function array_to_list(&$arr,$keys) {
	$list = array();
	foreach ($keys as $key) {
		$val = '';
		if (isset($arr[$key])) {
			$val = $arr[$key];
		}
		$list[] = $val;
	}
	return $list;
}

/*
// checks if class has method
// $class_method = 'class::method'
// if $self_declared is true also checks if class declares this method itself
function class_has_method($class_method, $self_declared = false) {
	$has_method = is_callable($class_method, false, $callable_name);
	if ($has_method && $self_declared) {
		$has_method = ($callable_name === $class_method);
	}
	return $has_method;
}
*/

// get file extension
function getExtension($path) {
	return pathinfo($path, PATHINFO_EXTENSION);
}

// extend array to required length by adding '' (empty string) elements
function extend_arr($arr, $length, $from_left = true) {
	if (!is_array($arr)) {
		trigger_error(debug::_('FUNCTIONS_EXTEND_ARR_FIRST_ARG_MUST_BE_ARRAY',$arr),debug::WARNING);
		return null;
	}
	$l = count($arr);
	for ($i = 0; $i < $length-$l; $i++) {
		if ($from_left) {
			$arr[] = '';
		} else {
			array_unshift($arr,'');
		}
	}
	return $arr;
}

// extend source array with destination array by keys
function extend($src, $dst) {
	$is_obj = is_object($src);
	// if ($is_arr) {
	if (is_object($dst)) {
		$dst = (array)$dst;
	}
	foreach ($dst as $key => $val) {
		if (!$is_obj) {
			if (!isset($src[$key])) {
				$src[$key] = $val;
			}
		} else {
			if (!isset($src->$key)) {
				$src->$key = $val;
			}
		}
	}
	return $src;
}

function extend_values($src, $dst) {
	$values = [];
	foreach ($dst as $key => $val) {
		if (isset($src[$key])) {
			$val = $src[$key];
		}
		$values[] = $val;
	}
	return $values;
}


/*
// extend source array with destination array by keys and returns list
function extend_list($src, $dst) {
	$values = array();
	foreach ($dst as $key => $val) {
		if (isset($src[$key])) {
			$val = $src[$key];
		}
		$values[] = $val;
	}
	return $values;
} */


// exapmle 1:
//		code: split_first('?','app/test/index.php?c=1')
//		returns: 'app/test/index.php'
function split_first($divider, $s) {
	$pos = strpos($s,$divider);
	$x = $s;
	if ($pos !== false) {
		$x = substr($s,0,$pos);
	}
	return $x;
}

function split_str($divider, $s, $from_left = true, $add_empty = true) { // splits $s string into 2 strings by $divider and returns array
	if ($from_left) {
		$pos = strpos($s,$divider);
	} else {
		$pos = strrpos($s,$divider);
	}
	if ($pos === false) {
		if ($add_empty) {
			if ($from_left) {
				return array($s,'');
			} else {
				return array('',$s);
			}
		} else {
			return array($s);
		}
	}
	$arr = array(
		substr($s,0,$pos),
		substr($s,$pos+1)
	);
	return $arr;
}


function split_left($divider, $s, $from_left = true) { // splits $s string into 2 strings by $divider and returns left part
	$arr = split_str($divider,$s,$from_left);
	return $arr[0];
}

function split_right($divider, $s, $from_left = true) { // splits $s string into 2 strings by $divider and returns right part
	$arr = split_str($divider,$s,$from_left);
	return $arr[1];
}


function is_function($func) {
	// return is_object($func) && (get_class($func) === 'Closure');
	return is_object($func) && ($func instanceof Closure);
}

function static_method_exists($classname, $method) {
	if (!is_string($classname)) {
		trigger_error(debug::_('FUNCTIONS_STATIC_METHOD_EXISTS_FIRST_ARG_MUST_BE_STRING',$classname),debug::WARNING);
		return null;
	}
	if ($classname == '') {
		trigger_error(debug::_('FUNCTIONS_STATIC_METHOD_EXISTS_FIRST_ARG_EMPTY_STRING'),debug::WARNING);
		return null;
	}
	// check if method exist
	if (!method_exists($classname, $method)) {
		return false;
	}
	// check if method is static
	$reflection = new ReflectionMethod($classname, $method);
	return $reflection->isStatic();
}


function render_args($func, $args) {
	ob_start();
	$res = call_user_func_array($func,$args); // $func($args);
	$html = ob_get_clean();
	if ($res && is_string($res)) {
		$html = $res;
	}
	return $html;
}

function render($func) {
	$args = func_get_args();
	array_shift($args);
	
	return render_args($func,$args);
}



function upper($s) {
	// var_dump($s);
	// var_dump(mb_strtoupper($s));
	return mb_strtoupper($s); //, 'UTF-8');
}

function lower($s) {
	return mb_strtolower($s); // , 'UTF-8');
}


function mb_lcfirst($string) {
	return mb_strtolower(mb_substr($string, 0, 1)).mb_substr($string, 1);
}

function mb_ucfirst($string) {
	return mb_strtoupper(mb_substr($string, 0, 1)).mb_substr($string, 1);
}

// mb_strtolower($game_name, 'UTF-8');

/* function mb_strcmp($s1, $s2, $encoding = 'windows-1251') {
	return strcmp(
		mb_convert_encoding($s1, 'utf-8', $encoding),
		mb_convert_encoding($s2, 'utf-8', $encoding)
	);
} */

function getlocale($category) {
	return setlocale($category, null);
}

function mb_strcmp($s1, $s2, $locale = 'ru_RU.UTF-8') {
	if ($locale !== null) {
		$last_locale = setlocale(LC_COLLATE, null);
		setlocale(LC_COLLATE, $locale);
	}
	$res = strcoll($s1, $s2);
	if ($locale !== null) {
		setlocale(LC_COLLATE, $last_locale);
	}
	return $res;
}





function array_to_object($arr) {
	return (object)$arr;
}

function object_to_array($obj) {
	return (array)$obj;
}

function rows_to_objects($rows) {
	foreach ($rows as &$row) {
		$row = (object)$row;
	}
	return $rows;
}

function rows_to_arrays($rows) {
	foreach ($rows as &$row) {
		$row = (array)$row;
	}
	return $rows;
}

// split_str('?',$);


// xplod_();


// xplod();
// function xplod($divider, $s) {
//	;
// }
