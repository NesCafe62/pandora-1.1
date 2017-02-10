<?php
defined ("CORE_EXEC") or die('Access Denied');

// $app >> переменная с именем класса текущего application-а

console::log('rendering application template: page.php');


// $app::style('styles.css'); // подключение css

$app::style('styles.less');  // подключение less с компиляцией

$app::script('app.scripts.js'); // подключение js

echo '<div class="page">';

	echo $app::template(); // >> вставка текущего шаблона из app $app::set('template','plugin:test.test_view')

echo '</div>';
