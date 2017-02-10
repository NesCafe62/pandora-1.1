<?php
namespace core;
defined ("CORE_EXEC") or die('Access Denied');

use \console;

/* breadcrumbs::setPath([
	['Home','/path/to','class'],
	['News','/path/to/news'],
	['News title',''], //no-url | current page
]);

breadcrumbs::push('fdf','/fdf/dffs','class');

// $arr = breadcrumbs::getPath();
// breadcrumbs::view('breadcrumbs');
breadcrumbs::render();

$arr = [
	['gdfgfg','/dsdf/fsdfdf'],
	['dsgdfg','/dsdf/fsdfdf'],
]; */

class breadcrumbs extends plugin {

	private static $items = [];
	private static $default_items = [];

	protected static $set_vars = [
		'items.init' => [
			'Главная', '/'
		]
	];

	// [section: Events]
	protected static $registerEvents = array(
		'init'
	);

	public static function onInit() {
		self::push([self::get('items.init')]);
		self::$default_items = self::$items;
	}

	public static function push($label, $url = '', $class = '') {
		if (is_array($label)) {
			$items = $label;
		} else {
			$items = [[$label, $url, $class]];
		}
		self::pushItems($items);
	}

	public static function pushItems($path_items) {
		foreach ($path_items as &$item) {
			list($label, $url, $class) = extend_arr($item,3);
			$item = (object)[
				'label' => $label,
				'url' => $url,
				'class' => $class
			];
		}
		self::$items = array_merge(self::$items, $path_items);
	}

	public static function setPath($path_items) {
		self::$items = self::$default_items;
		self::pushItems($path_items);
	}

	public static function getPath() {
		return self::items;
	}
	
	public static function render() {
		if (!self::$items || (count(self::$items) <= count(self::$default_items)) ) {
			return '';
		}
		return self::view('breadcrumbs',['path_items' => self::$items]);
	}

}

