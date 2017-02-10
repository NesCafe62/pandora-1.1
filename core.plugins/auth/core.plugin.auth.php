<?php
namespace core;
defined ("CORE_EXEC") or die('Access Denied');

use \console;
use \files;
use \core;
use \server;
use \route;
use \request;


use \table;


use \base_static;
use \db;
use \debug;


use \plugins;
plugins::import('plg.table');


// use \debug;

// use \db;

/*
class authActions extends pluginActions {

	;

}

class authEvents extends pluginEvents {

	;

}

class authRouting extends pluginRouting {

	;

} */

class table_users extends table {

	protected static $plugin = 'auth';

	protected static function query_user() {
		return array(
			'table' => 'users',
			'fields' => 'id, name, login, create_date, access'
		);
	}

}


// ------------------

/*
class active_query {

	private $query_params = [];

	public function __construct($class_name, $table_name, $fields) {
		if (!$table_name) {
			trigger_error(debug::_('ACTIVE_TABLE_FIND_TABLE_NAME_NOT_SET',$class_name),debug::WARNING);
			return;
		}
		if (!is_array($fields) || !$fields) {
			trigger_error(debug::_('ACTIVE_TABLE_FIND_FIELDS_WRONG_FORMAT',$class_name),debug::WARNING);
			return;
		}
		$this->query_params = [
			'tbale' => $table_name,
			'fields' => $fields
		];
	}

	public function where($conds) {
		$this->query_params['where'] = $conds;
	}

	public function order_by($order_by) {
		$this->query_params['order by'] = $order_by;
	}

	public function group_by($group_by) {
		$this->query_params['group_by'] = $group_by;
	}

	public function all() {
		return db::items($this->query_params);
	}

	public function one() {
		return db::item($this->query_params);
	}

}

class activeTable extends base_static {

	public static function find() {
		return new active_query(self::name(),self::get_static('table_name',null), self::get_static('fields',null));
	}

}


class tableThemes extends activeTable {

	private static $table_name = 'themes';

//	private static $fields = [
//		'id' => 'ID',
//		'title' => 'Title',
//		'subsection_id' => 'Subsection ID',
//		'user_id' => 'User ID',
//		'created_at' => 'Created At'
//	];

	private static $fields = ['id','title','subsection_id','user_id','created_at'];

} */

/*
$items = tableThemes::find()
		->where(['subsection_id' => $id]) // ->order_by() ->group_by()
		->all(); */

// ------------------

class auth extends \plg_table {



	protected static $set_vars = array(
		'url.login' => '/login',
		'url.logout' => '/logout',
		'url.register' => '/register',

		'register.allow' => false,
		'session.keep_alive' => false,

		// 'logout.redirect' => '/',
		// 'login.redirect' => '/',
		// 'register.redirect' => '/'
	);




	// [section: Routing]
	protected static function routing($routes) {
		$self = self::plugin_name();
		
		if (self::getConfig('manage.users',false)) {
			$routes['/users'] = 'view:manage_users';
		}
		
		// self::getConfig('/users');

		// 'manage.users' => true
		
		// url.admin_users => '/users'
		// /users => view:admin_users

		$routes_ = array(
			'url.login' => 'view:form_login',
			'url.logout' => function() { // $vars, $self) {
				self::action('user.logout');
			}
		);
		
		if ($self::get('register.allow',false)) {
			$routes_['url.register'] = 'view:form_register';
		}
		
		foreach($routes_ as $url => $route) {
			$route_url = $self::get($url);
			if ( ($route_url !== null) && (remove_left($route_url,'/') !== '') ) {
				$routes[ $route_url ] = $route;
			}
		}
		/* $routes[ $plg::get('url.login') ] = 'view:form_login';
		$routes[ $plg::get('url.logout') ] = 'action:logout';
		$routes[ $plg::get('url.register') ] = 'view:register'; */

		return $routes;
		
/*		return extend(array(
			$plg::get('url.login') => 'view:form_login',
			$plg::get('url.logout') => 'action:logout',
			$plg::get('url.register') => 'view:register'

			// '/register' => 'view:register' // -> 'plugin:auth.register'
			
			// '/user/$id:get{uid}:post{debug,catid}' => '',
//			'/user/profile/$id' => function($vars) {
				// $vars['id'];
				// console::log($vars);
//			},
//			'/user/$id' => 'user'
		), $routes); */
	}




	// [section: Actions]
	protected static function actions($actions) {
		$self = self::plugin_name();
		$plg_actions = array();

		$plg_actions['user.login:public'] = 'login';
		$plg_actions['user.logout:public'] = 'logout';
		if ($self::get('register.allow',false)) {
			$plg_actions['user.register:public'] = 'register';
		}
		if ($self::get('session.keep_alive',false)) {
			$plg_actions['session.update:public:ajax'] = 'sessionUpdate';
		}
		return self::extendActions($plg_actions, $actions);
	}

	
	protected static function registerUser($fields) {
		// if (!$fields['login'] || !$name || !$email || !$pass || $pass) {
//		console::log(2);
//		return;
		foreach (array(
			'login','name','email','pass'
		) as $key) {
			if (!isset($fields[$key]) || !$fields[$key]) {
				trigger_error(debug::_('AUTH_REGISTER_USER_FIELD_NOT_SET',$key),debug::WARNING);
				return false;
			}
		}
		unset($fields['id']);
		$fields['pass'] = $fields['pass'];
		return db::insert('users',$fields);
	}

	protected static function actionRegister($self) {
		if (!$self::get('register.allow',false)) {
			return;
		}

		$login = request::get('login','','post');
		$name = request::get('name','','post');
		$email = request::get('email','','post');
		$pass = request::get('password','','post');
		$rpass = request::get('rpassword','','post');
		
		if (!$login) {
			trigger_error(debug::_('AUTH_REGISTER_USER_FIELD_NOT_SET','login'),debug::WARNING);
			return false;
		} else if (!$email) {
			trigger_error(debug::_('AUTH_REGISTER_USER_FIELD_NOT_SET','email'),debug::WARNING);
			return false;
		} else if (!$pass) {
			trigger_error(debug::_('AUTH_REGISTER_USER_FIELD_NOT_SET','password'),debug::WARNING);
			return false;
		} else if ($pass !== $rpass) {
			trigger_error(debug::_('AUTH_REGISTER_USER_WRONG_FIELD','rpassword'),debug::WARNING);
			return false;
		}
		$pass = md5($pass);
		
//		console::log(3);
		// return;
		$res = $self::registerUser(array(
			'login' => $login,
			'name' => $name,
			'email' => $email,
			'pass' => $pass
		));
//		return;
		if (!$res) {
			trigger_error(debug::_('AUTH_REGISTER_FAILED'),debug::WARNING);
			return false;
		}

		$url = route::app(self::get('register.redirect',''));

		$url = self::triggerEvent('register',$url);

		/* if (!$login || !$name || !$email || !$pass || ($pass !== $rpass)) {
			return false;
		} */
		
		core::redirect($url);
	}

	protected static function actionLogin() {
		// $self = self::name();
		$token = core::getToken(self::getAction('user.login'));
		if (!core::checkToken($token)) {
			self::set('message','user_login_wrong_token');
			return '';
		}

		$login = request::get('login','','post');
		$pass = request::get('password','','post');

		$msg = '';
		if ($login == '') {
			$msg = 'user_login_login_empty';
		}

		if ($pass == '') {
			$msg = 'user_login_password_empty';
		}

		if ($msg !== '') {
			self::set('message',$msg);
			return '';
		}

		$user = self::authoriseUser($login, $pass);
		
		if ($user) {
			self::$user = $user;
			$url = route::app(self::get('login.redirect',''));
			$url = self::triggerEvent('login',$url);
			core::redirect($url);
		}
	}

	protected static function actionSessionUpdate() {
		return self::triggerEvent('sessionUpdate');
	}

	protected static function actionLogout() {
		self::session()->clear('user.id');
		$url = route::app(self::get('logout.redirect',''));
		$url = self::triggerEvent('logout',$url);
		core::redirect($url);
	}




	// [section: Events]
	protected static $registerEvents = array(
		'init',
		'authorise',
		'login',
		'logout',
		'register',
		'resetToken',
		'getSecureToken',
		'updateUser'
	);

	public static function onInit() {
		// self::test();
	}

	public static function onSessionUpdate() {
		//
	}

	public static function onAuthorise($user, $source) {
		// console::log($user,'event auth.authorise');
		// console::log($source,'source'); // 'login' | 'session'
		return true;
	}

	public static function onRegister($redirect_url = '') {
		if (!$redirect_url) {
			$redirect_url = route::url();
		}
		return $redirect_url;
	}

	public static function onLogin($redirect_url = '') {
		if (!$redirect_url) {
			$redirect_url = route::url();
		}
		return $redirect_url;
	}

	public static function onLogout($redirect_url = '') {
		if (!$redirect_url) {
			$redirect_url = route::url();
		}
		return $redirect_url;
	}

	public static function onResetToken() {
		self::$secure_token = null;
	}

	private static $secure_token = null;

	public static function onGetSecureToken($s, &$secure_token) {
		if (!self::$secure_token) {
			$id_user = 0;

			$user = self::getUser();
			if ($user) {
				$id_user = $user->id;
			}

			$token = implode(':', array(
				core::secret(),
				server::get('http_user_agent'),
				session_id(),
				$s,
				$id_user
			));
			
			self::$secure_token = md5($token);
		}
		$secure_token = self::$secure_token;
	}

	public static function onUpdateUser($user,$fields) {
		//
	}


	private static $user = null;

	protected static function authoriseUser($login, $pass, $md5 = true) {
		if ( ($login == '') || ($pass == '') ) {
			return false;
		}
		
		$user = self::getItem('users.user', array(
			'where' => array('login' => $login),
			'&fields' => 'pass'
		), false);

		if ($md5) {
			$pass = md5($pass);
		}
		if (!$user || (!isset($user->pass)) || ($user->pass == '') || ($user->pass !== $pass) ) {
			return false;
		}
		// unset($user->pass);
		// return $user;
		// authorise

		if (!self::triggerEvent('authorise',$user,'login')) {
			return false;
		}

		self::session()->set('user.id',$user->id);

		return $user;
	}

	public static function getUserById($id_user, $default = false) {
		if (!$id_user) {
			return $default;
		}
		return self::getItem('users.user',array('id' => $id_user), $default);
	}

	public static function getUser() {
		if (self::$user === null) {
			$id_user = self::session()->get('user.id',false);
			$plg_name = self::name();
			$user = $plg_name::getUserById($id_user,false);
			if ($user) {
				// authorise
				if (self::triggerEvent('authorise',$user,'session')) {
					self::$user = $user;
				}
			}
		}
		return self::$user;
	}


	public static function updateUser($fields, $id_user = null) {
		if (!$id_user) {
			$user = self::$user;
		} else {
			$user = self::getUserById($id_user);
			if (!$user) {
				return false;
			}
		}
		self::triggerEvent('updateUser',$user,$fields);
		return $user;
	}

	// public static function onInit() {
		// $provider = 'auth_'.self::config_get('provider','db'); // 'db'; // 'ldap'

		// self::config_set();
		
		/* $provider::connect();

		$users = $provider::users(); // all -> array of objects

		$provider::login('username','pass'); //

		$provider::logout(); //

		
		$user = $provider::user(array('login' => 'dsf')); //
		$user = $provider::user(array('id' => 33)); //

		$provider::disconnect(); */
	// }

}

/*
abstract class auth_provider extends plugin {

	public static function connect() {
		;
	}

} */

/*
auth_ldap extends auth_provider

auth_db extends auth_provider */
