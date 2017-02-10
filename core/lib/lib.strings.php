<?php
defined ("CORE_EXEC") or die('Access Denied');

// setlocale(LC_TIME,'ru_RU');

class strings {

	public static function format($s, $arguments = '') {
		$args = func_get_args();
		$args[0] = preg_replace_callback('/{([0-9]+)}/',function($matches){
			return '%'.(intval($matches[1]) + 1).'$s';
		}, $s);
		return call_user_func_array('sprintf',$args);
	}

	public static function format_seconds($f, $lang = null) {
		if ($lang === null) {
			$lang = array('мс.','с.');
		}
		if ($f < 1) {
			return round($f*1000, 1).' '.$lang[0];
		} else {
			return round($f,4).' '.$lang[1];
		}
	}

	public static function dump($x) {
		if ($x === null) {
			return 'null';
		} else if (is_string($x)) {
			return "'".htmlspecialchars($x)."'";
		}
		ob_start();
		var_dump($x);

		$dump = htmlspecialchars(ob_get_clean());
		
		$highlight = true;
		if ($highlight) {
			$dump = preg_replace(
				array(
					'/&quot;(.*)&quot;(?=$|\n)/', '/}\n/', '/&gt;\s*NULL/', '/int\((\d+)\)/'
				), array(
					'<span class="val">"$1"</span>', '}'."\n".'</div><div class="chunk">', '&gt; <span class="val">null</span>', 'int(<span class="val">$1</span>)'
				), $dump
			);
			
			$dump = preg_replace_callback('/\[(.*)\]=&gt;\s*/', function($matches) {
				$key = preg_replace('/^&quot;([a-zA-Z]\w*)&quot;$/','$1',$matches[1]);
				return '<span class="key">'.$key.'</span>: ';
			}, $dump);
			$dump = '<div class="chunk">'.str_replace('  ', '    ', remove_right($dump,'<div class="chunk">') );
			
		} else {
			$dump = preg_replace('/&gt;\s*NULL/','&gt; null',$dump);
			$dump = preg_replace_callback('/\[(.*)\]=&gt;\s*/', function($matches) {
				$key = preg_replace('/^"([a-zA-Z]\w*)"$/','$1',$matches[1]);
				return $key.': ';
			}, $dump);
			$dump = str_replace('  ', '    ', $dump );
		}
		
		return $dump;
	}

	// multibyte: if true - string limiting will consider multibyte charachters
	public static function limit_len($str, $max_len, $suffix = '...', $multibyte = true) {
		if (mb_strlen($str) <= $max_len) {
			return $str;
		}
		$len = mb_strlen($str);
		if ($len > $max_len*2) {
			$len = $max_len*2;
		}
		if ($multibyte) {
			$max_len -= mb_strlen($suffix);
		} else {
			$max_len -= strlen($suffix);
		}
		while ( ($multibyte && (mb_strlen($str) > $max_len)) || (!$multibyte && (strlen($str) > $max_len)) ) {
			$len--;
			$str = mb_substr($str,0,$len);
		}
		return $str.$suffix;
	}

	public static function file_size($bytes) {
		// $units = array('kb','mb','gb');
		$units = array('КБ','МБ','ГБ');
		if ($bytes < 1024) {
			$unit = 'b';
			$size = $bytes;
		} else {
			foreach ($units as $i => $u) {
				$multiplier = pow(1024,$i+1);
				$threshold = $multiplier * 1000;
				$unit = $u;

				if ($bytes < $threshold) {
					$r = $bytes/$multiplier;
					$q = 2;
					if ($r >= 1) $q = 1;
					$size = number_format($r,$q);
					break;
				}
			}
		}
		return $size.' '.$unit; // core::lang('size_'.$unit);
	}

	public static function num_lang($number) { // $const_name,
		if ($number > 10 && $number < 20) {
			$x = 0;
		} else {
			$s = ($number % 10);
			if ($s >= 5 || $s == 0) {
				$x = 0;
			} else if ($s == 1) {
				$x = 1;
			} else {
				$x = 2;
			}
		}
		return $x;
	}

	public static function toJson($data) {
		return str_replace('"',"'",json_encode($data,JSON_UNESCAPED_UNICODE));
	}

	public static function fromJson($json) {
		return json_decode(str_replace("'",'"',$data));
	}

}


class date {

	const DAY_ABBR = "\x021\x03";
	const DAY_NAME = "\x022\x03";
	const MONTH_ABBR = "\x023\x03";
	const MONTH_NAME = "\x024\x03";
	const MONTH_NAME_P = "\x026\x03";

	public static function from_mysql($date, $format = 'j P Y') {
		if ($date === '0000-00-00 00:00:00' || $date === '' || $date === null) return '';
		$datetime = new DateTime($date);
		$date = self::format($format,$datetime->getTimestamp());
		return $date;
	}

	public static function to_mysql($date = 'now') {
		if ($date === 'now') {
			$date = time();
		}
		return date('Y-m-d H:i:s',$date);
	}

	private static $lang = array(
		'ru' => array(
			'month' => array(
				'Январь',
				'Февраль',
				'Март',
				'Апрель',
				'Май',
				'Июнь',
				'Июль',
				'Август',
				'Сентябрь',
				'Октябрь',
				'Ноябрь',
				'Декабрь'
			),
			'month_abbr' => array(
				'Янв',
				'Фев',
				'Мар',
				'Апр',
				'Май',
				'Июн',
				'Июл',
				'Авг',
				'Сен',
				'Окт',
				'Ноя',
				'Дек'
			),
			'month_p' => array(
				'Января',
				'Февраля',
				'Марта',
				'Апреля',
				'Мая',
				'Июня',
				'Июля',
				'Августа',
				'Сентября',
				'Октября',
				'Ноября',
				'Декабря'
			),
			'day' => array(
				'Понедельник',
				'Вторник',
				'Среда',
				'Четверг',
				'Пятница',
				'Суббота',
				'Воскресенье'
			),
			'day_abbr' => array(
				'Пн',
				'Вт',
				'Ср',
				'Чт',
				'Пт',
				'Сб',
				'Вс'
			)
		)
	);

	public static function from_mysql_friendly($date, $formats = []) {
		if ($date === '0000-00-00 00:00:00' || $date === '' || $date === null) return '';
		$datetime = new DateTime($date);
		return self::friendly($datetime->getTimestamp(), $formats);
	}

	public static function friendly($date, $formats = []) { // , $format = 'j P Y') {
		// $now_date = time();
		$now_datetime = new DateTime();
		$now_time = $now_datetime->getTimestamp();

		$now_datetime->setTime(0,0);
		$now_day = $now_datetime->getTimestamp();

		// $datetime = new DateTime($date);
		// $date = $datetime->getTimestamp();

//		console::log(date('j M Y H:i',$now_time),'now');
//		console::log(date('j M Y H:i',$date),'date');

		$formats = extend($formats, [
			'global' => 'j M Y',
			'this_year' => 'j M',
			'yesterday' => 'вчера в H:i',
			'today' => 'сегодня в H:i',
			'minutes_0' => '%s минут назад',
			'minutes_1' => '%s минуту назад',
			'minutes_2' => '%s минуты назад',
			'1_minute' => 'минуту назад',
			'less_minute' => 'менее минуты назад',
		]);

		$format = 'global'; // 'j M Y';
		if (date('Y') == date('Y',$date)) {
			$format = 'this_year'; // 'j M';
		}
		$minutes = 0;

		if (($date > $now_day-86400) && ($date < $now_time)) { // 172800
			if ($date < $now_day) {
				$format = 'yesterday';
				// return 'вчера в '.date('H:i', $date);
			} else {
				if ($date > $now_time-3600) {
					$minutes = (int)(($now_time - $date) / 60); // (int)date('i',$date);
					if ($minutes == 0) {
						$format = 'less_minute';
						// return 'менее минуты назад';
					} else if ($minutes == 1) {
						$format = '1_minute';
						// return 'минуту назад';
					} else {
						$format = 'minutes_'.strings::num_lang($minutes);
					}
					/* $minutes_lang = [
						'%s минут назад', // 0
						'%s минуту назад', // 1
						'%s минуты назад' // 2
					]; */
				    // return sprintf($minutes_lang[strings::num_lang($minutes)],$minutes);
                } else {
					$format = 'today';
                    // return 'сегодня в ' . date('H:i', $date);
                }
			}
		} /* else {
			return self::format($format, $date);
		} */
		return self::format(sprintf($formats[$format],$minutes),$date);
	}

	public static function format($format = 'j P Y', $date = '') {
		$format = preg_replace('/(^|[^\\\])D/',"\\1".self::DAY_ABBR,$format);
		$format = preg_replace('/(^|[^\\\])l/',"\\1".self::DAY_NAME,$format);
		$format = preg_replace('/(^|[^\\\])M/',"\\1".self::MONTH_ABBR,$format);
		$format = preg_replace('/(^|[^\\\])F/',"\\1".self::MONTH_NAME,$format);
		$format = preg_replace('/(^|[^\\\])P/',"\\1".self::MONTH_NAME_P,$format);

		if ($date === '') {
			$date = time();
		}
		$res = date($format,$date);

		$language = 'ru'; // core::lang();
		
		$lang = null;
		if (isset(self::$lang[$language])) {
			$lang = self::$lang[$language];
		}

		if (strpos($res,self::DAY_ABBR) !== false) {
			// $day = date('D',$date);
			if ($lang) {
				$n_day = (date('w',$date)+6) % 7;
				$day = $lang['day_abbr'][$n_day];
			} else {
				$day = date('D',$date);
			}
			$res = str_replace(self::DAY_ABBR,$day,$res);
			// $res = str_replace(self::DAY_ABBR,core::lang('day_'.$day),$res);
		}

		if (strpos($res,self::DAY_NAME) !== false) {
			// $day = date('l',$date);
			if ($lang) {
				$day = $lang['day'][date('w',$date)-1];
			} else {
				$day = date('l',$date);
			}
			// $res = str_replace(self::DAY_NAME,$lang['day'][$day],$res);
			$res = str_replace(self::DAY_NAME,$day,$res);
			// $res = str_replace(self::DAY_NAME,core::lang('day_'.$day),$res);
		}

		if (strpos($res,self::MONTH_ABBR) !== false) {
			// $month = date('F',$date);
			if ($lang) {
				$month = $lang['month_abbr'][date('n',$date)-1];
			} else {
				$month = date('F',$date);
			}
			// $res = str_replace(self::MONTH_ABBR,$lang['month_abbr'][$month],$res);
			$res = str_replace(self::MONTH_ABBR,$month,$res);
			// $res = str_replace(self::MONTH_ABBR,core::lang('month_'.$month.'_short'),$res);
		}

		if (strpos($res,self::MONTH_NAME) !== false) {
			// $month = date('F',$date);
			if ($lang) {
				$month = $lang['month'][date('n',$date)-1];
			} else {
				$month = date('F',$date);
			}
			// $res = str_replace(self::MONTH_NAME,$lang['month'][$month],$res);
			$res = str_replace(self::MONTH_NAME,$month,$res);
			// $res = str_replace(self::MONTH_NAME,core::lang('month_'.$month),$res);
		}

		if (strpos($res,self::MONTH_NAME_P) !== false) {
			// $month = date('F',$date);
			if ($lang) {
				$month = $lang['month_p'][date('n',$date)-1];
			} else {
				$month = date('F',$date);
			}
			// $res = str_replace(self::MONTH_NAME_P,$lang['month_p'][$month],$res);
			$res = str_replace(self::MONTH_NAME_P,$month,$res);
			// $res = str_replace(self::MONTH_NAME_P,core::lang('month_'.$month.'_p'),$res);
		}

		// console::log($res);
		return $res; // str_replace('_','&nbsp;',$res);
	}

}
