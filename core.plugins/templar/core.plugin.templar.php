<?php
namespace core;
defined ("CORE_EXEC") or die('Access Denied');

use \core;
use \debug;

use \console;
use \timer;
use \strings;

class templar extends plugin {

//	public static function onInit() {
//		self::addEvent('onRenderTemplate');
//	}

	protected static $registerEvents = array(
		'renderTemplate',
		'templateGetName'
	);

	public static function onTemplateGetName(&$tpl) {
		$tpl = str_replace('.php','.tpl.php',$tpl);
	}

	public static function onRenderTemplate($file) {
		$tpl = str_replace('.php','.tpl.php',$file);
		// console::log($tpl);
		if (file_exists($tpl)) {
			return self::render($file);
		}
	}

	private static $force_compile = false; // true;

//	private static $template_path = '';
	
//	private static function render_template() {
//		$app = core::app();
		
//		ob_start();
//		// echo 'it works!';
//		require(self::$template_path);
//		return ob_get_clean();
//	}


	private static function convertTemplate($tpl) {
		$timer = new timer();
		$buffer = file_get_contents($tpl);
		if (strpos($buffer,'/*') !== false) {
//			$buffer = preg_replace('#//.*?(?=\n)#','',$buffer);
			
			// $buffer = preg_replace('#/\*(?:[^*]*(?:\*(?!/))*)*\*/#','',$buffer); // strip comments
			
			$buffer = rtrim(preg_replace_callback('#/\*(?:[^*]*(?:\*(?!/))*)*\*/#', function($matches) {
				return str_repeat( "\n", substr_count($matches[0],"\n"));
			}, $buffer))."\n";
		}
		$buffer = str_replace("\r",'',$buffer);
		
//		$buffer = preg_replace('/(?<=\n\s{0,10})(?:\[[^]]*)(\n)(?=[^]]*\])/','',$buffer);
//		var_dump($buffer);

		$lines = explode("\n",$buffer);
		$converted = '';
		$converted_first_line = '';


		$php_indicators = array('<?php','foreach','function','echo',',','=>','if','else','?>'); // '::', ,';'

		$tags = array();
		$tag_levels = array();
		$braces = 0;
		
		$in_block = false;
		$block_spaces = '';
		
		foreach ($lines as $i => $line) {
			/* $pos = strpos($line,'//');
			if ($pos !== false) {
				$line = substr($line,0,$pos);
			} */
			if (preg_match('/^(([^"\']|\'[^\']*\'|"[^"]*")*?)\/\//',$line,$match)) { // match '//' otside of quotes
				$line = $match[1]; // substr($line,0,$pos);
			}

			$braces_opened = substr_count($line,'{');
			$braces_closed = substr_count($line,'}');
			$braces += $braces_opened - $braces_closed;

			$force_no_php = false;

			$t_line = ltrim($line);
			if ($t_line !== '' && $t_line[0] === '[') {
				$force_no_php = true;
			}
			
			
			$php_line = false;
			
			if (!$in_block && !$force_no_php) {
			
				foreach ($php_indicators as $search) {
					if (strpos($line,$search) !== false) {
						$php_line = true;
						break;
					}
				}
				if (!$php_line && strpos($t_line,'=') !== false) {
					if (preg_match('/^([^"\']|\'[^\']*\'|"[^"]*")*?=/',$t_line)) { // match '=' outside of quotes
						$php_line = true;
					}
				}
				if (!$php_line && ends_with($t_line,';')) {
					$php_line = true;
				}
				if (!$php_line && starts_with($t_line,'$')) {
					$php_line = true;
				}
				if (!$php_line && ($t_line === 'array(' || $t_line === ')')) {
					$php_line = true;
				}
				//if (!$php_line && strpos($line,'::') !== false) {
					// if (!ends_with($line,'{')) {
					//	$php_line = true;
					// }
				// }
				if (!$php_line && strpos($t_line,'<') === 0) {
					$php_line = true;
				}
				if (!$php_line && strpos($t_line,')') !== false) {
					if (preg_match('/^\)+$/',$t_line)) { // match
						$php_line = true;
					}
				}
				if (!$php_line && strpos($t_line,';') !== false) {
					if (preg_match('/^([^"\']|\'[^\']*\'|"[^"]*")*?;/',$t_line)) { // match ';' outside of quotes
						$php_line = true;
					}
				}
				if (!$php_line && strpos($t_line,'::') !== false) {
					if (preg_match('/^[_a-zA-Z]\w*::/',$t_line)) { // match indentifier:: form start of string
						$php_line = true;
					}
				}
			
			}

			if ($php_line) {
				$render_line = $line;
			} else {

				// do {
				
					$offset = strlen($line)-strlen(ltrim($line));
					$spaces = substr($line,0,$offset);

					$line = trim($line);
					$render_line = '';

					if ($line !== '') {

						if ($line[0] === '[' && !$in_block) {
							$in_block = true;
							$block = '';
							$block_spaces = $spaces;
							$block_braces = $braces;
						}
					
						if ($in_block) {

							$block .= $line;
							// substr_count($line,'['); substr_count($line,']'); // !!!

							if (strpos($line,']') !== false && $braces <= $block_braces) {
							
								$block_s = split_right('[',$block);
								$block_s = trim( split_left(']',$block_s,false) );
								
								preg_match('/([^\(]+)(?:\((.*)\)(?:,\s*{\s*(.*)\s*})?)?/',$block_s,$matches);
								$block_func = $matches[1]; // view
								$block_context = '';
								$block_p = '';
								
								if (isset($matches[2])) {
									$block_context = $matches[2]; // 'search'

									if (isset($matches[3])) {
										$block_p = $matches[3]; // type: true,name: 'fdggb',d: $x
										
										$arr = explode(',',$block_p);
										$params = array();
										foreach ($arr as $w) {
											list($k,$v) = explode(':',$w);
											$params[] = trim($k).' => '.trim($v);
										}
										$block_p = 'array('.implode(', ',$params).')';
									}
								}
								
								$func = '';
								
								if ($block_func === 'head') {
									$func = "'<include:head />'";
								} else if ($block_func === 'scripts') {
									$func = "'<include:scripts />'";
								} else {
									$func_params = '';
									if ($block_func === 'view') {
										$block_func = '$plugin::view';
									} else if ($block_func === 'template') {
										$block_func = 'core::template';
									}
	//								else {
	//									$block_func = '$'.$block_func;
	//								}
									if ($block_context !== '') {
										if ($block_p !== '') $block_p = ', '.$block_p;
										$func_params = $block_context.$block_p;
									}
									$func = $block_func.'('.$func_params.')';
								}
								
								$render_line = 'echo '.$func.';';
								
								$spaces = $block_spaces;
							
								$in_block = false;
							
							}
						
						} else {

							$opened = false;
							$len = strlen($line);
							if ($line[$len-1] === '{') {
								$line = trim(substr($line,0,$len-1));
								$opened = true;
							}
							if ($opened) {
								if ($line === '') {
									trigger_error(debug::_('TEMPLAR_RENDER_NO_TAG_SPECIFIED','line: '.$i),debug::WARNING);
									return '';
								}
							}
							
							if ($line[0] === '}') {
								
								$render_line = $line;
								if (isset($tags[0])) {
									$tag = $tags[0];
									if ($tag_levels[0] == $braces) {
										array_shift($tags);
										array_shift($tag_levels);
										$html = '</'.$tag.'>';
										$render_line = "echo '".$html."';";
									}
								}
							
							} else {
							
								// $line = str_replace('.',' .',$line);
								$line = preg_replace_callback('/(^([^"\']|\'[^\']*\'|"[^"]*")*?)\./', function($matches) { // match '.' outside of quotes
									return $matches[1].' .';
								}, $line);
								
								// $line = str_replace("'",'"',$line);
								$line = preg_replace_callback('/\'((?:[^\'\\\\]|\\\\.)*)\'/', function($matches) {
									$s = $matches[1];
									$s = str_replace(array("\'",'\\','"'),array("'",'\\\\','__%quot%__'),$s);
									// $s = str_replace('\\','\\',$s);
									// $s = str_replace('\\"','\\\\\\"',$s); // \_" >> \_\"
									// $s = preg_replace('/(?<!\\\\)"/','\"',$s); // кавычка ["] перед которой нет слеша
									return '"'.$s.'"';
								}, $line);
								
								// $line = str_replace(array('.',"'"),array(' .','"'),$line);
								$line = preg_replace('/#(?=[^"])/',' #',$line);
								$line = preg_replace('/[\s\t]+/',' ',$line);
								// $line = str_replace(': ',':',$line);
								$line = str_replace(array(': '),array(':'),$line);

								$classes = array();
								if (strpos($line,'?') === false) {
									preg_match_all('/[^\s"]+|("[^"]*")/',$line,$matches);
									$arr3 = $matches[0];
								} else {
									/* preg_match_all('/(\((?:(?>[^()]+)|(?R))*\)(\s*\?.*?:\s*)?|[^\s("]*("[^"]*")?)/',$line,$matches); */
									
									preg_match_all('/(\((?:(?>[^()]+)|(?R))*\)(\s*\?(\s*)|(.*?:\s*)?)?|[^\s("]*("[^"]*")?)/',$line,$matches);
									$arr3 = $matches[0];
									
									/* var_dump($line);

									$arr3 = array(); */
									/* $line = preg_replace_callback('/(\((?:(?>[^()]+)|(?R))*\)(\s*\?.*?:\s*)?|[^\s("]*("[^"]*")?)/', function($match) use ($arr3) { */
									/* $line = preg_replace_callback('/(\((?:(?>[^()]+)|(?R))*\)(\s*\?(\s*)|(.*?:\s*)?)?|[^\s("]*("[^"]*")?)/', function($match) use ($arr3) {
										$token = $match[0];
										$q_pos = strpos($token,'?');
										if ($q_pos !== false) {
											// $variants = substr($token,$q_pos+1);
											// var_dump($token);
												// if (strpos(substr($token,$q_pos+1),':') === false) {	
												//	$token .= ':""';
												// }
											$token = str_replace('"',"'",$token);
											$token = str_replace("'+'","'.'",$token);
											$token = str_replace("+'",".'",$token);
											$token = str_replace("'+","'.",$token);
											// if ($token[strlen($token)-1] !== ')') {
											// 	$token = '('.$token.')';
											// }
										}
										if ($token !== '') {
											$arr3[] = $token;
										}
										return $token;
									}, $line); */
								}
								
								$arr = array();
								$s = '';
								$pos = 0;
								foreach ($arr3 as $e) {
									if ($e === '') continue;
									if ($line[$pos] === ' ') {
										$pos += 1+strlen($e);
										$arr[] = $s;
										$s = '';
									} else {
										$pos += strlen($e);
									}
									$s .= $e;
								}
								$arr[] = $s;
								
								// $arr = explode($line,' ');
								$tag = '';
								$props = array();
								foreach ($arr as $e) {
									if ($e === '') continue;
									if ($tag === '') {
										if ($e[0] === '.' || $e[0] === '#' || $e[0] === ':') {
											// trigger_error(debug::_('TEMPLAR_RENDER_NO_TAG_SPECIFIED','line: '.$i),debug::WARNING);
											$tag = 'div';
											/* debug::addLog(array(
												'label' => '',
												'message' => debug::_('TEMPLAR_RENDER_NO_TAG_SPECIFIED'),
												'level' => 'warning',
												'file' => $tpl,
												'line' => $i
											),'system');
											return ''; */
										} /* else {
											//debug::addLog(array(
											//	'label' => '',
											//	'message' => debug::_('TEMPLAR_RENDER_NO_TAG_SPECIFIED'),
											//	'level' => 'warning',
											//	'file' => $tpl,
											//	'line' => $i
											//),'system');
											return '';
											console::log($line);
											$php_line = true;
											break;
										} */
									}
									$q_pos = strpos($e,'?');
									if ($q_pos !== false) {
										if (strpos(substr($e,$q_pos+1),':') === false) {	
											$e = preg_replace('/\?\s*"/','\0 ',$e);
											$e .= ':""';
										} else {
											$e = preg_replace('/[\?:]\s*"/','\0 ',$e);
										}
										$e = str_replace('"+"','"."',$e);
										$e = str_replace('+"','."',$e);
										$e = str_replace('"+','".',$e);
										$e = str_replace('"',"'",$e);
										if ($e[strlen($e)-1] !== ')') {
											// $e = '('.$e.')';
											$e = preg_replace('/[^.#].*/','(\0)',$e);
										}
									}
									if ( (strpos($e,'[') !== false) && preg_match('/(^([^"\']|\'[^\']*\'|"[^"]*")*?)\[/',$e) ) { // match '[' outside of quotes
										$e = preg_replace_callback('#(?:\$\w+)?\[((?:(?>[^\[\]]+)|(?R))*)\]#', function($match) { // to-do: do matching of '[' outside of quotes
											$s = $match[0];
											if ( ($match[1] !== '') && ($match[0][0] !== '$') ) {
												$s = 'implode(\' \','.$match[1].')';
											}
											return $s;
										}, $e);
									}

									// if ($e[1] === '[') {
									//	$e = preg_replace('#(?<=^[.#])\[([^\]]+)\]#','implode(' ',\1)',$e);
									// }
									if ($e[0] === '.') {
										$class = substr($e,1);
										/* if ($class[0] === '[') {
											$class = preg_replace('#^\[([^\]]+)\]#','implode(\' \',\1)',$class);
										} */
										
										// if (!isset($props['class'])) $props['class'] = '';
										if (!in_array($class,$classes)) $classes[] = $class;
										continue;
									}
									if ($e[0] === '#') {
										$props['id'] = substr($e,1);
										continue;
									}
									if (strpos($e,'?') !== false) {
										$props[] = $e;
										continue;
									}
									if (strpos($e,':') !== false) {
										list($prop,$val) = split_str(':',$e);
										if ($prop === 'class') {
											$class = $val;
											// if (!isset($props['class'])) $props['class'] = '';
											if (!in_array($class,$classes)) $classes[] = $class;
										} else {
											$props[$prop] = $val;
										}
										continue;
									}
									if ($tag === '') {
										$tag = $e;
										continue;
									}
									$props[] = $e;
								}
								if ($php_line) {
									$render_line = $line;
									break;
								}

								if (count($classes) > 0) {

									foreach($classes as $i => &$val) {
										/* $q_pos = strpos($val,'?');
										if ($q_pos !== false) {
											if (strpos(substr($val,$q_pos+1),':') === false) {	
												$val .= ':""';
											}
											$val = str_replace('"',"'",$val);
											$val = str_replace("'+'","'.'",$val);
											$val = str_replace("+'",".'",$val);
											$val = str_replace("'+","'.",$val);
											if ($val[strlen($val)-1] !== ')') {
												$val = '('.$val.')';
											}
										} */
										if (strpos($val,'$') !== false || strpos($val,'+') !== false || strpos($val,'"') !== false) {
											if ($val[0] === '"') {
												$val = substr($val,1);
											} else {
												$val = "'.".$val;
											}
											$len = strlen($val);
											if ($val[$len-1] === '"') {
												$val = substr($val,0,$len-1);
											} else {
												$val = $val.".'";
											}
											$val = str_replace('"+"',"'.'",$val);
											$val = str_replace('+"',".'",$val);
											$val = str_replace('"+',"'.",$val);
										}
										if ($i > 0) {
											if (!preg_match('/[\?:]\s*\'\s/',$val)) {
												$val = ' '.$val;
											}
										}
									}

									// $props['class'] = implode('',$classes);
									unset($props['class']);
									$props = array_merge( array('class' => implode('',$classes)), $props );
								}

								$arr2 = array();
								foreach($props as $prop => $val) {
									// if ($val[0] === '"') $val = substr($val,1);
									// $len = strlen($val);
									// if ($val[$len-1] === '"') $val = substr($val,0,$len-1);
									$is_prop = !is_int($prop);
									$is_prop_var = false;
									// $q = (strpos($val,'$') !== false);

									/* $q = ($val[0] === '$');
									if ($is_prop && ($q || strpos($val,'::') !== false)) { // $val[0] === '$'
									//	$len = strlen($val);
									//	if ( ($val[$len-1] === ')') || !$q || (strrpos($val,"+'",strrpos($val,'$')) === false) ) {
											$is_prop_var = true;
										// }
									} */

									if ($is_prop) {
										if ($val[0] === '$' || strpos($val,'::') !== false) {
											$is_prop_var = true;
										} else {
											$len = strlen($val);
											if ( (substr($val,$len-2) !== ".'") && (strpos($val,'$') > 0) && ($val[$len-1] !== ')') ) {
												if (strrpos($val,"+'",strrpos($val,'$')) === false) {
													$is_prop_var = true;
												}
											}
										}
									}
									
									if ($val[0] === '"') {
										$val = substr($val,1);
									} else {
										// $q = strpos($val,'$');

										// $val = $val.".'";
										
										/* console::log(0);
										console::log($val);
										console::log($is_prop_var); */
										if (!$is_prop || $is_prop_var) { // || ($q !== false && strrpos($val,"+'",$q) === false)) {
											$val = "'.".$val;
										}
									}
									/* } else if (!$is_prop || $is_prop_var) {
										$val = "'.".$val;
									} */
									$len = strlen($val);
									// console::log(0);
									// console::log($is_prop);
									// console::log($is_prop_var);
									if ($val[$len-1] === '"') {
										$val = substr($val,0,$len-1);
									} else {
										// $q = strpos($val,'$');
										// $val = $val.".'";
										
										/* console::log(1);
										console::log($val);
										console::log($is_prop_var); */
										if (!$is_prop || $is_prop_var) { // || ($q !== false && strrpos($val,"+'",$q) === false)) {
											$val = $val.".'";
										}
									}
									/* } else if (!$is_prop || $is_prop_var) {
										console::log(2);
										$val = $val.".'";
									} */
									// console::log($val);
									$val = str_replace('"+"',"'.'",$val);
									$val = str_replace('+"',".'",$val);
									$val = str_replace('"+',"'.",$val);

									// $val = preg_replace_callback('/(^([^"\'\(]|\'[^\']*\'|\([^\)]*\)|"[^"]*")*)\+/', function($matches) { // match '+' outside of brackets nad quotes
									$val = preg_replace_callback('/\'[^\']*\'|"[^"]*"/', function($matches) { // match '(' inside of quotes
										return str_replace('(','__%bracket%__',$matches[0]);
									}, $val);

									$val .= '()';
									
									$val = preg_replace_callback('/([^\)]*)?(\((?:(?>[^\(\)]+)|(?R))*\))/', function($matches) { // match '+' outside of brackets nad quotes
										return str_replace('+','.',$matches[1]).$matches[2];
									}, $val);

									$val = remove_right($val,'()');
									
									// $val = str_replace('__%quot%__','"',$val);
									$val = str_replace('__%bracket%__','(',$val);

									$val = str_replace('__%quot%__','"',$val);
									if (!$is_prop) {
										if (preg_match('/[\?:]\s*"\s/',$val)) {
											$val = ' '.$val;
										}
										$arr2[] = $val;
									} else {
										$arr2[] = ' '.$prop.'="'.$val.'"';
									}
								}
								
								$options = implode('',$arr2);
								// if ($options !== '') $options = ' '.$options;
								
								if ($tag === 'br' || $tag === 'input') {
									$html = '<'.$tag.$options.'/>';
								} else {
									$html = '<'.$tag.$options.'>';
									if ($opened) {
										array_unshift($tags,$tag);
										array_unshift($tag_levels,$braces-1);
									} else {
										$html .= '</'.$tag.'>';
									}
								}
								$render_line = "echo '".$html."';";
							}
						}
						
					}
					$render_line = $spaces.$render_line;

				// } while (false);
			}
			if ($converted_first_line === '') {
				$converted_first_line = $render_line.' // generated by [Templar]';
			} else {
				$converted .= $render_line."\r\n";
			}
		}
		$converted_first_line .= ' time: '.strings::format_seconds($timer->stop(),array('ms.','s.')).', source file: \''.$tpl.'\'';
		$converted_first_line .= "\r\n";
		return $converted_first_line.$converted;
	}

	protected static function render($file) {
		// self::triggerEventArgs('templateGetName',array(&$file));
		$tpl = str_replace('.php','.tpl.php',$file);
		// var_dump($tpl);
		// exit;
		if (!file_exists($tpl)) {
			trigger_error(debug::_('TEMPLAR_RENDER_FILE_NOT_FOUND',$tpl),debug::WARNING);
			return false;
		}
		
		if (self::$force_compile || !is_file($file) || (filemtime($file) <= filemtime($tpl))) {
			$converted = self::convertTemplate($tpl);
			file_put_contents($file, $converted);
		}
		
		return true;

//		self::$template_path = $file;
//		self::render_template();
//		self::$template_path = '';
		
		// return $buffer;
	}

}
