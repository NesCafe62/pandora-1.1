<?php
namespace core;
defined ("CORE_EXEC") or die('Access Denied');

// use \console;


//	$file = request::getFile('file_prog');
//	if ($file) {
		/* $attribs = array(
			'name' => '',
			'access' => 0
		); */
		// file_storage::store('programs.'.$id_prog, $file); // , $attribs);
//	}


//	$files = request::getFiles('files_prog');
//	if ($file) {
		/* $attribs = array(
			'name' => '',
			'access' => 0
		); */
		// file_storage::store('programs.'.$id_prog[], $file); // , $attribs);
//	}


	// file_storage::store('programs.'.$id_prog.'[]', $file);

	// programs.3 0 0

	// programs.3 1 1
	// programs.3 1 2

	// programs/3-file-dfgg.pdf
	// programs/3[1]-file-dfgg.pdf
	// programs/3[2]-file-dfgg.pdf

	// file_storage::getFiles('programs.'.$id_prog);
	// file_storage::getFile('programs.'.$id_prog.'[1]');

	// file_storage::remove('programs.'.$id_prog.'[1]');

	// file_storage::download('programs.'.$id_prog.'[1]', 'filename');

	

	
	// file_storage::getFile('programs.'.$id_prog); [->name, ->filename, ->access, ->attribs] ->getSize
	// file_storage::getAttribs('programs.'.$id_prog);

	// file_storage::remove('programs.'.$id_prog);
	// file_storage::download('programs.'.$id_prog, 'filename');


// id:				int(11)
// storage_key:		varchar(255)
// type:			tinyint(1)
// storage_index:	int(11)
// name:			varchar(255)
// access:			tinyint(1)
// attribs:			varchar(8192)

class storageFile {

	private $key;
	private $index;

	private $params = [];

	public $name;
	public $ext; // readonly
	public $filename; // readonly
	public $access;

	public $attribs = null;

	public function __construct($key, $index, $params) {
		$this->key = $key;
		$this->index = $index;
		$this->name = $params['name'];
		$this->filename = $params['filename'];
		$this->access = $params['access'];
		$this->attribs = json_decode($params['attribs']); // attribs -> object
		$this->params = $params;

		$this->ext = $this->getExtension();
	}

	private function getExtension() {
		return strtolower(getExtension($this->params['filename']));
	}

	public static function getFilename($key, $index, $name, $ext) { // ,$ext) {
		// $ext = $this->getExtension();
		if ($index > 0) {
			$key .= '['.$index.']';
		}
		return str_replace('.','/',$key).'-file-'.str_replace(' ','-',$name).'.'.strtolower($ext);
	}

	public function _update_filename() {
		$params['name'] = $this->name;
		$params['filename'] = $this->filename;
	}

	public function update() {
		$params = $this->params;
		$fields = [];
		$rename = false;
		if ($this->name !== $params['name']) {
			$fields['name'] = $this->name;

			$old_name = $this->params['filename'];
			$fields['filename'] = self::getFilename($this->key, $this->index, $this->name, $this->getExtension()); // , $ext);
			$rename = true;
		}
		if ($this->access !== $params['access']) {
			$fields['access'] = $this->access;
		}
		$attribs = json_encode($this->attribs);
		if ($attribs !== $params['attribs']) {
			$fields['attribs'] = $attribs;
		}
		$this->params = extend($fields, $this->params);
		$r = db::update('file_storage', $fields, ['storage_key' => $this->key, 'storage_index' => $this->index]);

		if ($r === false) {
			trigger_error(debug::_('STORE_FILE_DB_UPDATE_FAILED', $this->key, $this->index),debug::WARNING);
		} else if ($rename) {
			files::rename($old_name, $this->params['filename']);
		}
		return $r;
	}

	public function getSize() {
		return filesize($this->params['filename']);
	}
	
}

class file_storage extends plugin {

	// $key, $file
	// or
	// $key, $files
	public static function store($key, $file) {
	//	if () {
			return storeFile($key,$file);
	//	} else {
	//		return storeFiles($key,$file);
	//	}
	}

	// storeFile('docs.3[1]',$file); // only if it already exists
	// storeFile('docs.3[]',$file); // add to end
	
	// storeFile('docs.4',$file);
	public static function storeFile($file_key, $file) { // , $overwrite = true) {
		$res_path = self::res_path();


		$key = $file_key;
		$index = 0;
		$type = 0;
		if (strpos($file_key,'[') !== false) {
			$type = 1;
			list($key, $index) = split_str('[',$file_key,false);
			if ($index !== '') {
				if (!is_numeric($index) || ($index <= 0) ) {
					trigger_error(debug::_('FILE_STORAGE_STORE_FILE_WRONG_KEY_INDEX',$file_key),debug::WARNING);
					return false;
				}
				$index = (int) $index;
			}
		}


		$editing = false;
		$key_type = self::keyType($key);
		if ($type == 1) {
			if ($key_type === 0) {
				trigger_error(debug::_('FILE_STORAGE_STORE_FILE_STORED_KEY_IS_NOT_ARRAY',$file_key),debug::WARNING);
				return false;
			}
			
			if ($index === '') {
				// get next file index
				if ($key_type === -1) {
					$index = 1;
				} else {
					$index = self::nextKeyIndex($key);
				}
				
			} else {
				if ($key_type === -1) {
					trigger_error(debug::_('FILE_STORAGE_STORE_FILE_KEY_INDEX_NOT_FOUND',$key,$index),debug::WARNING);
					return false;
				} else {
					$editing = true;
				}
			}
		} else {
			// $index = 0;
			if ($key_type === 1) {
				trigger_error(debug::_('FILE_STORAGE_STORE_FILE_STORED_KEY_IS_ARRAY',$file_key),debug::WARNING);
				return false;
			}

			if ($key_type === -1) {
				;
			} else {
				$editing = true;
			}
		}


		$ext = getExtension($file['name']);
		$name = remove_right($file['name'],'.'.$ext);
		$filename = storageFile::getFilename($key, $index, $name, $ext);

		if ($editing) {
			$storage_file = self::getStorageFile($key, $index);
			// remove old file
			files::remove($res_path.$storage_file->filename);

			$storage_file->name = $name;
			$storage_file->filename = $filename;
			$storage_file->_update_filename();
		} else {
			$storage_file = new storageFile($key, $index, [
				'access' => 0,
				'name' => $name,
				'filename' => $filename,
				'attribs' => []
			]);
		}
		
		$storage_filename = $res_path.$storage_file->filename;
		files::createPath(getFilePath($storage_filename));
		if (!files::upload($file, $storage_filename)) {
			trigger_error(debug::_('FILE_STORAGE_STORE_FILE_UPLOAD_FAILED',$storage_filename),debug::WARNING);
			return false;
		}
		
		$err = false;

		if ($editing) {
			$fields = [
				'name' => $storage_file->name,
				'filename' => $storage_file->filename
			];
			$where = [
				'storage_key' => $key,
				'storage_index' => $index
			];
			if (!db::update('file_storage', $fields, $where)) {
				$err = debug::_('FILE_STORAGE_STORE_FILE_DB_UPDATE_FAILED', $fields['storage_key'].'['.$fields['storage_index'].']', $fields['type'], $fields['name'], $fields['filename']);
			}
		} else {
			$fields = [
				'storage_key' => $key,
				'type' => $type,
				'storage_index' => $index,
				'name' => $storage_file->name,
				'filename' => $storage_file->filename,
				'access' => $storage_file->access,
				'attribs' => json_encode($storage_file->attribs)
			];
			if (!db::insert('file_storage',$fields)) {
				$err = debug::_('FILE_STORAGE_STORE_FILE_DB_INSERT_FAILED', $fields['storage_key'].'['.$fields['storage_index'].']', $fields['type'], $fields['name'], $fields['filename']);
			}
		}

		if ($err) {
			trigger_error($err,debug::WARNING);
			files::remove($storage_filename);
			return false;
		}

		return $storage_file;
	}

//	public static function storeFiles($key, $files, $overwrite = true) {
//		;
//	}


	/* public static function hasKey($file_key) {
		db::item('file_storage',['','',''],[]);
	} */

	private static function keyType($key) { // 0 -> single, 1 -> array, -1 -> key not found
		$item = db::item(
			'file_storage',
			'sum( if(storage_index = 0, 1, 0) ):count_single, sum( if(storage_index > 0, 1, 0) ):count_array',
			['storage_key' => $key]
		);
		if ($item->count_single > 0) {
			return 0;
		}
		if ($item->count_array > 0) {
			return 1;
		}
		return -1;
	}

	private static function nextKeyIndex($key) {
		$item = db::item(
			'file_storage',
			'max(storage_index):max_index',
			['storage_key' => $key]
		);
		if (!$item->max_index) {
			return 1;
		}
		$next_index = (int) $item->max_index;
		return $next_index + 1;
	}


	public static function getFile($file_key) {
		$key = $file_key;
		$index = 0;
		if (strpos($file_key,'[') !== false) {
			list($key, $index) = split_str('[',$file_key,false);
			if (!is_numeric($index) || ($index <= 0) ) {
				trigger_error(debug::_('FILE_STORAGE_GET_FILE_WRONG_KEY_INDEX',$file_key),debug::WARNING);
				return false;
			}
			$index = (int) $index;
		}

		
		
		return self::getStorageFile($key, $index);
	}

	private static function getStorageFile($key, $index) {
		$where = [
			'storage_key' => $key,
			'storage_index' => $index
		];
	//	if ($index === 0) {
	//		$where['type'] = 0;
	//	}
		$params = db::item('file_storage', 'name, filename, access, attribs', $where);
		if (!$params) {
			trigger_error(debug::_('FILE_STORAGE_GET_STORAGE_FILE_DB_NOT_FOUND', $key, $index),debug::WARNING);
			return false;
		}
		$file = new storageFile($key, $index, $params);
		return $file;
	}

	public static function getFiles($file_key) {
		;
	}

	public static function download($file_key) {
		;
	}

	public static function remove($file_key) {
		;
	}

}
