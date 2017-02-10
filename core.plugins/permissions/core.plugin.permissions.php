<?php
namespace core;
defined ("CORE_EXEC") or die('Access Denied');


use \console;

use \core;
use \files;
// use \route;
// use \request;
// use \debug;

class permissions extends plugin {

	// [section: Events]
	protected static $registerEvents = array(
		'init',
		'actionAccess',
		'getGroups'
	);

	public static function onGetGroups($user = null) {
		return array();
	}

	private static $permissions = array();
	public static function onInit() {
		$path = core::get_app_path();
		$filename = $path.'permissions.cfg';

		if (file_exists($filename)) {
			$permissions = files::read_cfg($filename,true);
			
			// console::log($permissions);
			
			foreach ($permissions as $plg => &$plg_perms) {
				// console::log($plg,'plg');
				// console::log($plg_perms,'perms');
				if (!is_array($plg_perms)) {
					$plg_perms = array($plg_perms);
				}
				
				$default_perms = array();
				if (isset($plg_perms[''])) {
					$default_perms = array('' => $plg_perms['']);
					unset($plg_perms['']);
				}
				// console::log(1);
				
				foreach ($plg_perms as $key => $val) {
					if ($key[0] != '#') {
						unset($permissions[$plg][$key]);
						$default_perms[$key] = $val;
					}
				}
				if (isset($permissions[$plg][''])) {
					unset($permissions[$plg]['']);
				}
				if (count($default_perms) > 0) {
					$permissions[$plg] = array_merge(array('#default' => $default_perms), $permissions[$plg]);
				}
			}
			
			self::$permissions = $permissions;
		}
		
		// $r = self::checkPermission('marks','marks.edit.save');
		// console::log($r,'permission marks/marks.edit.save');
	}

	public static function checkPermission($plg, $action) {
		$groups = self::getGroups(); // array('moderator','prep');
		//console::log($groups,'permission groups');
		// self::$permissions;
		
		// $plg = 'marks'; $action = 'marks.group.get';

		$access = false;

		if (!isset(self::$permissions[$plg])) {
			return false;
		}
		$plg_perms = self::$permissions[$plg];
		if (!in_array('default',$groups)) {
			$groups[] = 'default';
		}

		foreach ($groups as $group) {
			$group = '#'.$group;
			if (!isset($plg_perms[$group])) {
				continue;
			}
			$group_perms = $plg_perms[$group];
			if (!is_array($group_perms)) {
				$group_perms = array($group_perms);
			}
			foreach ($group_perms as $validator => $validator_perms) {
				if ($validator === '') {
					$valid = true;
				} else {
					// $validator; // запуск функции валидатора
					// console::log($validator,'check validator');
					$valid = true; // false
				}
				if (!$valid) {
					continue;
				}
				
				if (!is_array($validator_perms)) {
					$validator_perms = array($validator_perms);
				}
				foreach ($validator_perms as $perm) {
					$perm = remove_right($perm,'.*').'.';
					if (starts_with($action.'.', $perm)) {
						return true;
					}
				}
			}
		}
		
		return $access;
	}

	private static $permission_groups = null;
	public static function getGroups() {
		if (self::$permission_groups === null) {
			self::$permission_groups = self::triggerEvent('getGroups');
		}
		return self::$permission_groups;
	}

	public static function checkGroup($groups) {
		$perm_groups = self::getGroups();
		if (is_string($groups)) {
			$groups = explode(' ',trim($groups));
		}
		$res_groups = array_intersect($groups, $perm_groups);
		return (count($res_groups) > 0);
	}

	public static function onActionAccess($plg_action, &$access) {
		list($plg,$action) = split_str('/',$plg_action);

		$access = self::checkPermission($plg, $action);
		console::log('plugin: \''.$plg.'\' action: \''.$action.'\' access: \''.(($access)?'true':'false').'\'','permissions::onActionAccess'); // 'permissions::onActionAccess &#10004;');
	}

}
