<?php
defined ("CORE_EXEC") or die('Access Denied');

list($show_caption, $placeholders, $link_register) = $plugin::params(array(
	'show_caption' => true,
	'placeholders' => false,
	'link_register' => false
));

$plugin::script('plugin.auth.js');

echo '<form name="auth_login" class="form" method="post" action="'.route::url().'">'; // route::app($plugin::get('url.login'))
	if ($show_caption) {
		echo '<h3 class="caption">'.$plugin::lang('form_login_caption').'</h3>';
	}
	echo '<div class="form-cont">';
		echo '<label class="field">';
			$placeholder = '';
			$label = $plugin::lang('form_login_field_login');
			if ($placeholders) {
				$placeholder = ' placeholder="'.$label.'"';
			} else {
				echo '<div class="label">'.$label.'</div>';
			}
			echo '<input type="text" name="login" value="'.request::get('login').'"'.$placeholder.'/>';
		echo '</label>';
		echo '<label class="field">';
			$placeholder = '';
			$label = $plugin::lang('form_login_field_password');
			if ($placeholders) {
				$placeholder = ' placeholder="'.$label.'"';
			} else {
				echo '<div class="label">'.$label.'</div>';
			}
			echo '<input type="password" name="password" value=""'.$placeholder.'/>';
		echo '</label>';

		echo '<div class="toolbar">';
			echo '<a href="'.route::url('#').'" class="button submit">'.$plugin::lang('form_login_button_submit').'</a>';
			if ($link_register) {
				echo '<a class="register" href="'.route::app('/register').'">Регистрация</a>';
			}
			echo '<div class="clearfix"></div>';
		echo '</div>';

		$action_login = $plugin::getAction('user.login');
		echo '<input type="hidden" name="'.core::getToken($action_login).'" value="1" />';
		echo '<input type="hidden" name="action" value="'.$action_login.'" />';
		// $plugin::getAction('user.login')
	echo '</div>';
echo '</form>';
