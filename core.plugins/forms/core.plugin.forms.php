<?php
namespace core;
defined ("CORE_EXEC") or die('Access Denied');

use \console;

// use \core;
// use \route;
// use \request;
use \debug;

/*

form json:

form: {
	name: program_edit,
	fields: {
		name: {
			type: text,
			format: url // string // int // html
		//	normalize: [
		//		// no_script,
		//		no_html
		//	]
			rules: [
				required,
				email,
				unique: 'users.email',
				
				minLength: 6
			]
		}
	}
	toolbar: [
		button:
			name: 'Сохранить',
			action: programs.edit.save
	]
}

*/

/*class form {

	private static $fields = [];

	public static function __constructor($fields) {
		// $this-> = ;
	}

	public function emailFilter(){

	    return true;
    }
	public static function validate() {
		;
		return [$res, $msg, $fields];
	}

    public static function validate($data,$rules){
        $result  = true;
        foreach ($rules as $item){
            $fields_ = gettype($item[0])=='string' ? [$item[0]] : $item[0] ;
            $rules_  = gettype($item[1])=='string' ? [$item[1]] : $item[1] ;
            foreach($fields_ as $field_){
                foreach($rules_ as $rule_){
                    $ruleMethod ='rule'.ucfirst($rule_);
                    $result &= self::$ruleMethod($data[$field_]);
                }
            }
        }
        return $result;
    }
    public static function ruleEmail($field){
        $res = true;
        // code
        return $res;
    }



}*/

class forms extends plugin {

	// #section [Routing]
/*	protected static function routing($routes) {
		return extend(array(
			'' => ''
		), $routes);
	} */

	// #section [Actions]
/*	protected static function actions($actions) {
		return self::extendActions(array(
			'' => ''
		), $actions);
	} */

	// #section [Events]
    protected static $registerEvents = array(
		//'afterBody'
        'init',
    );

    public static function onInit() {
        self::import('form');
	}


	/* public static function onAfterBody(&$html) {
		;
	} */


	// #section [Main]
	public static function load($path) {
		;
	}

}
