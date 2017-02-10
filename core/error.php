<?php
defined ("CORE_EXEC") or die('Access Denied');

$title = '';

$e = error_get_last();

$msg = isset($e['message']) ? $e['message'] : '';
$file = isset($e['file']) ? $e['file'] : '';
$line = isset($e['line']) ? $e['line'] : '';
$type = isset($e['type']) ? debug::getErrorTypeName($e['type']) : 'Fatal error';
$file = '/'.remove_left($file,$root);

echo '<!DOCTYPE html>';
echo '<html>';
echo '<head>';
	echo '<meta charset="utf-8">';
	echo '<meta name="robots" content="noindex">';

	echo '<title>'.$title.'</title>';

?><style>
	body {
		margin: 0;
		font-family: Verdana,"Geneva CY","DejaVu Sans",sans-serif;
		font-size: 13px;
	}
	h1, p, pre {
		margin: 0;
	}
	pre {
		display: inline;
	}
	a {
		outline: none;
	}
	.page {
		margin: 0 auto;
		max-width: 1100px;
		padding-top: 100px;
	}
	.page h1 {
		color: #707070;
		font-size: 32px;
		margin-bottom: 20px;
		margin-left: -1px;
		margin-right: 40px;
	}
	.info {
		margin-bottom: 30px;
	}
	.info p {
		color: #777777;
		font-size: 15px;
		margin-bottom: 10px;
	}
	.info .error-message {
		color: #656565;
		line-height: 26px;
	}
	.messages {
		color: #454545;
	}
	.messages .message-row {
		line-height: 18px;
		margin-bottom: 7px;
	}
	.messages .message-row div {
		display: inline;
		margin-right: 6px;
	}
	b {
		color: #03769C;
		font-weight: normal;
	}
</style><?php
echo '</head>';

echo '<body>';

	echo '<div class="page-wrap">';
		echo '<div class="page error">';
			echo '<h1 class="error">Внутренняя ошибка сервера</h1>';
			echo '<div class="info">';
				// echo '<p class="info-message"></p>';
				echo '<p class="error-message">';
					echo '<b>'.$type.'</b>: '.$msg.' in <b>'.$file.'</b> on line <b>'.$line.'</b>';
				echo '</p>';
				// echo '<p class="link-back">Вы можете <a href="'.$back_url.'">вернуться на предыдущую страницу</a></p>';
			echo '</div>';
			echo '<div class="messages">'; // style="color: #FFF">'; // display: none
				foreach ($messages as $msg) {
					echo '<div class="message-row">';
						echo self::_out_error($msg);
					echo '</div>';
				}
			echo '<div>';
		echo '</div>';
	echo '</div>';

echo '</body>';
exit($e['type']);
