<?php
defined ("CORE_EXEC") or die('Access Denied');

libs::load('functions');

libs::load('sequence');

class arrayPath {

	private $arr = array();

	public function push($keys, $val, $scalar_val = false) {
		// $keys = explode('.',$path);
		$p = &$this->arr;
		
		if ($scalar_val) {
			$last_key = array_pop($keys);
		}
		foreach ($keys as $key) {
			if (!isset($p[$key])) {
				$p[$key] = array();
			}
			$p = &$p[$key];
		}
		if ($scalar_val) {
			if (isset($p[$last_key])) {
				if (!is_array($p[$last_key])) {
					$t = $p[$last_key];
					$p[$last_key] = array($t);
				}
				$p[$last_key][] = $val;
			} else {
				$p[$last_key] = $val;
			}
		} else {
			if (is_string($p)) {
				$t = $p;
				$p = array($t);
			}
			$p[] = $val;
		}
		return true;
	}

	public function getArray() {
		return $this->arr;
	}
	
}

class files {

	private static function parse_cfg($list, $parse_tree) {
		$sequence = new sequence();

		$r = new arrayPath();

		$path = array();
		
		$sequence->
			waitfor(array('{','}'))->
			filter('{')->
			map('insert_last')->
			callback(function($elements) use ($r, &$path, $parse_tree) {
				$segments = array();
				$key = '';
				$last_key = '';

				foreach ($elements as $el) {

					if ($el === ':') {
						$last_key = $key;
						$key = array_pop($segments);

						if ( ($last_key !== '') && (count($segments) > 0) ) {
							$s = array_shift($segments);
							if ($parse_tree) {
								$r->push(explode('.',implode('.',$path).'.'.$last_key),$s,true);
							} else {
								$r->push(array_merge($path,array($last_key)),$s,true);
							}
						}
						
						foreach ($segments as $s) {
							if ($parse_tree) {
								$r->push(explode('.',implode('.',$path).'.'),$s);
							} else {
								$r->push(array_merge($path,array()),$s);
							}
						}
						
						$segments = array();
					} else {
						$segments[] = $el;
					}
				}

				array_push($path,$key);
			});
		
		$sequence->
			waitfor(array('{','}'))->
			filter('}')->
			map('insert_last')->
			callback(function($elements) use ($r, &$path, $parse_tree) {
				$segments = array();
				$key = '';
				$last_key = '';

				$root_segments = array();
				
				$elements_ = $elements;
				
				$elements[] = ' ';
				$elements[] = ':';
				
				foreach ($elements as $el) {
					if ($el === ':') {
						$last_key = $key;
						$key = array_pop($segments);

						if ( ($last_key !== '') && (count($segments) > 0) ) {
							$s = array_shift($segments);
							if ($parse_tree) {
								$r->push(explode('.',implode('.',$path).'.'.$last_key),$s,true);
							} else {
								$r->push(array_merge($path,array($last_key)),$s,true);
							}
						}
						
						foreach ($segments as $s) {
							if ($parse_tree) {
								$r->push(explode('.',implode('.',$path)),$s,true);
							} else {
								$r->push($path,$s,true);
							}
						}
						
						$segments = array();
					} else {
						$segments[] = $el;
					}
				}

				array_pop($path);
			});
		
		$sequence->run($list);
		return $r->getArray();
	}

	public static function getFiles($path, $names = '*', $sort = false) {
		$path = remove_right($path,'/');
		$flags = 0;
		if (!$sort) {
			$flags |= GLOB_NOSORT;
		}
		$files = glob($path.'/'.$names,$flags);
		$res = array();
		foreach ($files as $file) {
			if (!is_dir($file)) {
				$res[] = $file;
			}
		}
		return $res;
	}

	public static function getFolders($path, $sort = false) {
		$path = remove_right($path,'/');
		$flags = GLOB_ONLYDIR;
		if (!$sort) {
			$flags |= GLOB_NOSORT;
		}
		return glob($path.'/*',$flags);
	}

	private static function _search($path, $mask) {
		$files = self::getFiles($path,$mask,true);
		$dirs = self::getFolders($path,false);
		foreach ($dirs as $dir) {
			if (is_dir($dir)) {
				$files = array_merge( $files, self::_search($dir,$mask) );
			}
		}
		return $files;
	}

	// $arr = files::searchInFiles('str_to_find','/','*.php');
	// [to-do]: another function to search by regexp
	public static function searchInFiles($text, $path, $mask = '*.php', $remove_comments = true) {
		$root = server::get('document_root');
		$path_start = strlen($root);
		$path = $root.'/'.remove_left($path,'/');
		$files = self::_search($path,$mask);
		$rows = array();
		foreach ($files as $i => $file) {
			$fname = substr($file,$path_start);
			$buffer = file_get_contents($file);
			if ($remove_comments) {
				if (strpos($buffer,'/*') !== false) {
					$buffer = preg_replace_callback('#/\*(?:[^*]*(?:\*(?!/))*)*\*/#', function($matches) {
						return str_repeat( "\n", substr_count($matches[0],"\n"));
					}, $buffer);
				}
			}
			$lines = explode("\n",$buffer);
			foreach ($lines as $line_number => $line) {
				if ($remove_comments) {
					$pos = strpos($line,'//');
					if ($pos !== false) {
						$line = substr($line,0,$pos);
					}
				}
				if (strpos($line,$text) !== false) {
					if (strpos($line,'debug::searchInFiles') === false) {
						$rows[] = array(
							'file' => $fname,
							'line_number' => $line_number+1,
							'line' => $line
						);
					}
				}
			}
		}
		return $rows;
	}

	
	public static function upload($file, $filename, $overwrite = false) {
		/* if (!isset($_FILES[$request_name])) {
			trigger_error(debug::_('FILES_UPLOAD_NOT_',$request_name),debug::WARNING);
			return false;
		} */
		if (!isset($file['tmp_name'])) {
			trigger_error(debug::_('FILES_UPLOAD_PARAM_FILE_HAS_WRONG_TYPE',$file),debug::WARNING);
			return false;
		}
		/*if (is_uploaded_file($file['tmp_name'])) {
			trigger_error(debug::_('FILES_UPLOAD_FILE_WAS_NOT_UPLOAD',$file['tmp_name']),debug::WARNING);
			return false;
		} */
		if (is_file($filename)) {
			if ($overwrite) {
				unlink($filename);
			} else {
				trigger_error(debug::_('FILES_UPLOAD_ALREADY_EXISTS',$filename,$file['tmp_name']),debug::WARNING);
				return false;
			}
		}
		return move_uploaded_file($file['tmp_name'],$filename);
	}

	public static function delete($filename) {
		// deprecated
		if (!is_file($filename)) {
			return false;
		}
		return unlink($filename);
	}

	public static function remove($filename) {
		if (!is_file($filename)) {
			return false;
		}
		return unlink($filename);
	}

	public static function rename($oldname, $newname) {
		return rename($oldname, $newname);
	}

	public static function createPath($path) {
		return mkdir($path, 0777, true);
	}

/*	public static function createFolder($path) {
		;
	}

	public static function deleteFolder($path) {
		;
	} */


	public static function read_cfg($file, $parse_tree = false, $no_warning = false) {
		$ext = getExtension($file);
		$err_msg = null;
		if (!is_file($file)) {
			$err_msg = debug::_('FILES_READ_CFG_NOT_EXISTS',$file);
		} elseif ($ext !== 'cfg') {
			$err_msg = debug::_('FILES_READ_CFG_WRONG_EXT',$file);
		}
		if ($err_msg !== null) {
			if ($no_warning) return array();
			trigger_error($err_msg,debug::WARNING);
			return false;
		}

		$file_dat = $file.'.dat';
		if (!is_file($file_dat) || filemtime($file_dat) <= filemtime($file)) {
			$s = file_get_contents($file);
			$s = preg_replace('#//.*?(?=\n)#','',$s);
			if (strpos($s,'/*') !== false) {
				$s = preg_replace('#/\*(?:[^*]*(?:\*(?!/))*)*\*/#','',$s);
			}
			preg_match_all('/[^\s:{}]+|:|{|}/',$s,$matches);

			$r = self::parse_cfg($matches[0],$parse_tree);
			file_put_contents($file_dat,json_encode($r,JSON_UNESCAPED_UNICODE));
		} else {
			$r = json_decode(file_get_contents($file_dat),true);
		}
		return $r;
	}
	
	public static function readCfg($file, $parse_tree = false, $no_warning = false) {
		return self::read_cfg($file, $parse_tree, $no_warning);
	}
	
	public static function read_csv($file, $encoding = null, $no_warning = false) {
		$ext = getExtension($file);
		$err_msg = null;
		if (!is_file($file)) {
			$err_msg = debug::_('FILES_READ_CSV_NOT_EXISTS',$file);
		} elseif ($ext !== 'csv') {
			$err_msg = debug::_('FILES_READ_CSV_WRONG_EXT',$file);
		}
		if ($err_msg !== null) {
			if ($no_warning) return array();
			trigger_error($err_msg,debug::WARNING);
			return false;
		}
		
		$buffer = file_get_contents($file);
		if ($encoding !== null) {
			$buffer = mb_convert_encoding($buffer, 'utf-8', $encoding);
		}
		if (strpos(mb_substr($buffer,0,500),"\r\n") !== false) {
			// for Microsoft exel csv - "\r\n" line endings
			$splitter = "\r\n";
		} else {
			// for other csv - "\n" line ending
			$buffer = str_replace("\r",'',$buffer);
			$splitter = "\n";
		}
		return explode($splitter,remove_right($buffer,$splitter));
	}
	
	public static function readCsv($file, $encoding = null, $no_warning = false) {
		return self::read_csv($file, $encoding, $no_warning);
	}

	public static function read_json($file, $no_warning = false) {
		$ext = getExtension($file);
		$err_msg = null;
		if (!is_file($file)) {
			$err_msg = debug::_('FILES_READ_JSON_NOT_EXISTS',$file);
		} elseif ($ext !== 'json') {
			$err_msg = debug::_('FILES_READ_JSON_WRONG_EXT',$file);
		}
		if ($err_msg !== null) {
			if ($no_warning) return array();
			trigger_error($err_msg,debug::WARNING);
			return false;
		}

		$file_dat = $file.'.dat';
		if (!is_file($file_dat) || filemtime($file_dat) <= filemtime($file)) {
			$s = file_get_contents($file);
			$s = preg_replace('#//.*?(?=\n)#','',$s);
			if (strpos($s,'/*') !== false) {
				$s = preg_replace('#/\*(?:[^*]*(?:\*(?!/))*)*\*/#','',$s);
			}
			$s = preg_replace_callback('#\'([^\'\n]*)\'(?=[^"]+\n)#', function($matches) {
				return '"'.str_replace('"','\"',$matches[1]).'"';
			}, $s);
			// $s = preg_replace('#([^\s]+[^"\s]?)\s*:#','"\1":',$s);
			$s = preg_replace_callback('#("(?:[^"]|\\\\")*")|([^\s"]+[^"\s]?)\s*:#', function($matches) {
				if ($matches[1] !== '') {
					return $matches[0];
				}
				return '"'.$matches[2].'":';
			}, $s);
			
			// $s = preg_replace('#[\'"]?([^\s]+[^"\'\s])\'?\s*:#','"\1":',$s);
			// $s = preg_replace('#:\s*\'?([^,{\s]*[^\',{\s])\'#',': "\1"',$s);
			$r = json_decode($s,true);
			if ($r === null) {
				trigger_error(debug::_('FILES_READ_JSON_PARSE_ERROR',$file),debug::WARNING);
				return false;
			}
			file_put_contents($file_dat,json_encode($r,JSON_UNESCAPED_UNICODE));
		} else {
			$r = json_decode(file_get_contents($file_dat),true);
		}
		return $r;
	}
	
	public static function readJson($file, $no_warning = false) {
		return self::read_json($file, $no_warning);
	}

	public static function read_ini($file, $no_warning = false) {
		$ext = getExtension($file);
		$err_msg = null;
		if (!is_file($file)) {
			$err_msg = debug::_('FILES_READ_INI_NOT_EXISTS');
		} elseif ($ext !== 'ini') {
			$err_msg = debug::_('FILES_READ_INI_WRONG_EXT');
		}
		if ($err_msg !== null) {
			if ($no_warning) return array();
			trigger_error($err_msg,debug::WARNING);
			return false;
		}
		
		$file_dat = remove_right($file,'.ini').'.dat'; // preg_replace('/\.ini$/','.dat',$file);
		if (!is_file($file_dat) || filemtime($file_dat) <= filemtime($file)) {
			$r = parse_ini_file($file);
			file_put_contents($file_dat,json_encode($r,JSON_UNESCAPED_UNICODE)); // serialize($r));
		} else {
			$r = json_decode(file_get_contents($file_dat),true); // unserialize(file_get_contents($file_dat));
		}
		return $r;
	}
	
	public static function readIni($file, $no_warning = false) {
		return self::read_ini($file, $no_warning);
	}

}
