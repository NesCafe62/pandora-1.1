<?php
defined ("CORE_EXEC") or die('Access Denied');

class test extends core\plugin {

	// [section: Routing]
	protected static function routing($routings) {
		return extend(array(
		
			'/test' => 'view:test'
			
		), $routings);
	}

	// [section: Actions]
	protected static function actions($actions) {
		return self::extendActions(array(
		
			'test.dosomething:public' => 'doSomething'
			
		), $actions);
	}
	
	public static function actionDoSomething() {
		console::log('message!!');
	}

}
