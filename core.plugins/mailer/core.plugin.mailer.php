<?php
namespace core;
defined ("CORE_EXEC") or die('Access Denied');

use libs;
libs::load('class.phpmailer','lib.phpmailer');
// \core::requireModule('class.phpmailer','lib/phpmailer');
use PHPMailer;

class mailer extends plugin {

/*	public static function initEvent() {
	} */

	private static $method = 'phpmailer:mail';	// mail, phpmailer
												// phpmailer_mode: sendmail, qmail, smtp, mail

	private static $params = array();

	public static function setMethod($method) {
		if ($method !== '') {
			self::$method = $method;
		}
	}

	public static function setParams($params) {
		if ($params) {
			self::$params = $params;
		}
	}

	private static function phpmailer_init($submethod,$params) {
		// \core::requireModule('class.phpmailer','lib/phpmailer');
		// \core::requireModule('lib/phpmailer/language','phpmailer.lang-ru.php');
		$mail = new PHPMailer;
		
		$mail->setLanguage('ru'); // ,'lib/phpmailer/language/');

		$params = extend($params,self::$params);

		switch ($submethod) {
			case 'sendmail':
			
				$mail->isSendmail();
				break;
				
			case 'qmail':
			
				$mail->isQmail();
				break;
				
			case 'mail':
			
				break;
				
			case 'smtp':
			default:
			
				// \core::requireModule('class.smtp','lib/phpmailer');
				libs::load('class.smtp','lib.phpmailer');
				$mail->isSMTP();

				$smtp_params = $params['smtp'];
				$mail->SMTPDebug = extend_val($smtp_params,'debug',0);

				$mail->Debugoutput = extend_val($smtp_params,'debug output','html');

				$mail->Host = $smtp_params['host'];

				$mail->Port = extend_val($smtp_params,'port',25);

				$auth = extend_val($smtp_params,'auth',false);
				$mail->SMTPAuth = $auth;

				if ($auth) {
					$mail->Username = $smtp_params['username']; // "yourname@example.com";
					$mail->Password = $smtp_params['password']; // "yourpassword";
				}
			
		}
		return $mail;
	}


	public static function send($params) { // $to, $subject, $message, $headers) {
		$res = false;
		
		$method = self::$method;
		if (isset($params['method'])) {
			$method = $params['method'];
		}
		
		list($method,$submethod) = extend_arr(split_str(':',$method),2); // xplod2(':',$method);
		
		switch ($method) {
			case 'mail':

//				$_headers = array();
//				foreach ($params['headers'] as $key => $val) {
//					if ($key === 'charset') {
//						$charset = $val;
//						$key = 'Content-type';
//						$val = 'text/html; charset='.$charset;
//					}
//					$_headers[] = $key.': '.$val;
//				}

				if (is_scalar($params['from'])) {
					$from = $params['from'];
				} else {
					$from = $params['from'][1].' <'.$params['from'][0].'>';
				}

				if (isset($params['html'])) {
					$message = $params['html'];
				} else {
					$message = $params['message'];
				}

				if (is_scalar($params['to'])) {
					$to = $params['to'];
				} else {
					$to = $params['to'][0];
				}

				$subject = $params['subject'];

				$headers =
					'Content-type: text/html; charset=utf-8' . "\r\n" .
					'From: '.$from . "\r\n" . // Учителя для учителей
					'Reply-To: '.$to . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
			
				$res = mail($to, $subject, $message, $headers); //  implode("\r\n",$_headers) );
				break;
				
			case 'phpmailer':
			default:

				ob_start();

				// $submode: sendmail, qmail, smtp, mail
				$mail = self::phpmailer_init($submethod,$params);

				if (is_scalar($params['from'])) {
					$mail->setFrom($params['from']); // 'from@example.com', 'First Last');
				} else {
					$mail->setFrom($params['from'][0],$params['from'][1]);
				}

				if (isset($params['reply to']) && $params['reply to']) {
					if (is_scalar($params['reply to'])) {
						$mail->addReplyTo($params['reply to']); // 'replyto@example.com', 'First Last');
					} else {
						$mail->addReplyTo($params['reply to'][0],$params['reply to'][1]);
					}
				}

				if (is_scalar($params['to'])) {
					$mail->addAddress($params['to']); // 'whoto@example.com', 'John Doe');
				} else {
					$mail->addAddress($params['to'][0],$params['to'][1]);
				}

				$mail->Subject = ( isset($params['subject']) ? $params['subject'] : ''); // extend_val($params,'subject',''); // 'PHPMailer sendmail test';

				$mail->CharSet = 'UTF-8';
				if (isset($params['html'])) {
					$mail->IsHTML(true);
					$mail->Body = $params['html'];
					// $mail->msgHTML($params['html'],'');
					$mail->AltBody = strip_tags($params['html']);
				} else {
					$mail->Body = $params['message'];
				}
				
				$res = $mail->send();
//				var_dump($res);
//				exit;
				
				$output = ob_get_clean();
				if ($output != '') {
					// trigger_error(\debug::_('MAILER_OUTPUT',$output),\debug::WARNING);
					var_dump($output);
					exit;
				}

				if (!$res) {
					// trigger_error(\debug::_('MAILER_ERROR',$mail->ErrorInfo),\debug::WARNING);
					var_dump($mail->ErrorInfo);
					exit;
					// echo "Mailer Error: " . $mail->ErrorInfo;
				}
		}
		return $res;
	}

//	public static function actionEvent($action, $ajax = false) {
//		return '';
//	}
	
}
