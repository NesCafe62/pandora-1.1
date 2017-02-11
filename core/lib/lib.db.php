<?php
defined ("CORE_EXEC") or die('Access Denied');

class db {

	private static $connected = false;
	private static $db;
	private static $db_name;
	private static $enable_transactions = false;

	private static $last_res = null;

	public static function connection_mysql($host, $db_name, $db_port = null) {
		$connection = 'mysql:host='.$host.';dbname='.$db_name;
		if ($db_port) $connection .= ';port='.$db_port;
		return $connection;
	}

	public static function connection_postgree($host, $db_name, $db_port = null) {
		;
	}

	//	$params = array(
	//		'host' => ,
	//		'user' => ,
	//		'dbname' => ,
	//		'port' => , (optional)
	//		'type' => 'mysql',
	//		'pass' => '',
	//		'encoding' => ''
	//	);
	public static function connect($params) {
		$params = extend($params, array(
			'type' => 'mysql',
			'port' => null,
			'enable_transactions' => false,
			'encoding' => 'utf8'
		));
		foreach (array('host','dbname','user','pass') as $param) {
			if (!isset($params[$param])) {
				trigger_error(debug::_('DB_CONNECT_PARAM_NOT_SET',$param),debug::ERROR);
				return false;
			}
		}

		$type = $params['type'];
		$user = $params['user'];
		$pass = $params['pass'];
		$encoding = $params['encoding'];
		$enable_transactions = $params['enable_transactions'];
		self::$enable_transactions = (is_string($enable_transactions) && $enable_transactions === 'true') || ((int)$enable_transactions === 1);

		$connection = call_user_func_array(array('db','connection_'.$type),array($params['host'], $params['dbname'], $params['port']));

		self::$connected = false;
		try {
			self::$db = new PDO(
				$connection,$user,$pass,
				array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES `'.$encoding.'`')
			);
			self::$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
			self::$db_name = $params['dbname'];
			self::$connected = true;
		} catch(PDOException $e) {
			trigger_error(debug::_('DB_CONNECT_FAILED',$e->getMessage()),debug::ERROR);
		}
		return self::$connected;
	}


	public static function quote($s) {
		return '`'.$s.'`';
	}

	private static $last_query = null;

	public static function transactionBegin() {
		if (self::$enable_transactions) {
			self::$db->beginTransaction();
		}
	}

	public static function transactionCommit() {
		if (self::$enable_transactions) {
			self::$db->commit();
		}
	}

	public static function transactionRollback() {
		if (self::$enable_transactions) {
			self::$db->rollback();
		}
	}

	public static function query_sql($query, $data = array(), $check = true) {
		if ($check && !self::$connected) {
			trigger_error(debug::_('DB_QUERY_NO_CONNECTION'),debug::ERROR);
			return array(false,null);
		}

		$params = '';
		foreach ($data as $param => $val) {
			if ($params !== '') {
				$params .= ',';
			}
			$s = '    <span class="key">'.$param.'</span>: '.str_replace("\n",'',strings::dump($val));
			$params .= "\n".$s;
		}
		if ($params !== '') $params .= "\n";

		self::$last_query = array('query' => $query, 'data' => $data);
		// self::$profiling = true;

		if (self::$profiling) {
			$timer = new timer();
			debug::channelPush('sql');
		}

		$err = false;
		try {
			$res = self::$db->prepare($query);
			self::$last_res = $res;
			$r = $res->execute($data);
		} catch(PDOException $e) {
			$err = $e;
			// return array(false,null);
		}

		$profiling = '';
		if (self::$profiling) {
			$time = strings::format_seconds($timer->stop());
			$profiling = '<div class="row"><span class="label">time:</span><pre> '.$time.'</pre></div>';
		}

		debug::addLog(array(
			'label' => '', // 'query:',
			'message' =>
				'<div class="row"><span class="label">query:</span>'."'<pre class=\"block\">".$query."'</pre>".'</div>'.
				'<div class="row"><span class="label">data:</span>[<pre> '.$params.']</pre></div>'.$profiling
		), 'sql');

		if (self::$profiling) {
			debug::channelPop('sql');
		}

		self::reset_params();

		if ($err !== false) {
			trigger_error(debug::_('DB_QUERY',$err->getMessage().' '.$query),debug::ERROR);
			return array(false,null);
		}

		return array($r,$res);
	}

	public static function get_last_query() {
		return self::$last_query;
	}


	/*
	$where3 = array(
		'_op' => 'and',
		'a' => 1,
		'b' => 1
	);

	=> => =>
	array(
		'_op' => 'and',
		'a' => 1,
		'b' => 1
	);


	$where1 = array(
		'or' => array(
			'a' => 1,
			'b' => 1
		)
	);

	=> => =>

	array(
		'_op' => 'and',
		'a' => 1,
		'b' => 1
	);



	$where2 = array(
		'a' => 1,
		'b' => 1
	);

	=> => =>

	array(
		'_op' => 'and',
		'a' => 1,
		'b' => 1
	); */


	public static function _and($conditions) {
		$conds = func_get_args();
		$keys = array_keys($conditions);
		$conds_merged = array('_op' => 'and');

		if ( (count($conds) > 1) || !is_string($keys[0])) {
			$conditions = $conds;
		}

		$conds = self::normalize_cond($conditions);
		unset($conds['_op']);
		$conds_merged = array_merge($conds_merged, $conds);

		return $conds_merged;
	}

	public static function _or($conditions) {
		$conds = func_get_args();
		$keys = array_keys($conditions);
		$conds_merged = array('_op' => 'or');

		if ( (count($conds) > 1) || !is_string($keys[0])) {
			$conditions = $conds;
		}

		$conds = self::normalize_cond($conditions);
		unset($conds['_op']);
		$conds_merged = array_merge($conds_merged, $conds);

		return $conds_merged;
	}

	public static function _not($condition) {
		$condition = self::normalize_cond($condition);
		if ($condition['_op'] === 'not') {
			$condition['_op'] = 'and';
			return $condition;
		}
		if ($condition['_op'] === 'and') {
			$condition['_op'] = 'not';
			return $condition;
		}
		return array('_op' => 'not', $condition);
	}

	public static function not($condition) {
		return self::_not($condition);
	}

	private static function normalize_sub_conds($condition) { //, $op) {
		$op_array = array('and','or','not');
		foreach ($op_array as $_op) {
			if (isset($condition[$_op])) {
//				if ($_op === $op) {
//					$condition = array_merge($condition, $condition[$_op]);
//					unset($condition[$_op]);
//				} else {
				array_unshift($condition, array_merge(array('_op' => $_op),$condition[$_op]) );
				unset($condition[$_op]);
//				}
			}
		}
		return $condition;
	}

	private static function normalize_cond($condition) {
		if (isset($condition['_op'])) {
			return self::normalize_sub_conds($condition); // ,$condition['_op']);
		}
		$operation = 'and';
		$op_array = array('and','or','not');
		foreach ($op_array as $op) {
			if (isset($condition[$op])) {
				// $condition = $condition[$op];

				$condition = self::normalize_sub_conds($condition[$op]); // ,$op);

				//var_dump($condition);
				//var_dump(111);

				/* foreach ($op_array as $_op) {
					if (isset($condition[$_op])) {
						if ($_op === $op) {
							$condition = array_merge($condition, $condition[$_op]);
							unset($condition[$_op]);
						} else {
							array_unshift($condition, array_merge(array('_op' => $_op),$condition[$_op]) );
							unset($condition[$_op]);
						}
					}
				} */

				$operation = $op;
				break;
			}
		}
		return array_merge(array('_op' => $operation), $condition);
	}

	public static function last_id() {
		return self::$db->lastInsertId();
	}

	public static function last_result() {
		return self::$last_res;
	}

	// select row
	public static function query_sql_item($query, $data = array(), $check = true) {
		list($r,$rows) = self::query_sql($query,$data,$check);
		if (!$r) {
			return false;
		}

		$rows->setFetchMode(PDO::FETCH_OBJ);
		$res = $rows->fetch();

		if ($res === false) {
			return false;
		}
		return $res;
	}

	// select rows
	public static function query_sql_items($query, $data = array(), $check = true) {
		list($r,$rows) = self::query_sql($query,$data,$check);
		if (!$r) {
			return false;
		}

		$rows->setFetchMode(PDO::FETCH_OBJ);
		$res = array();
		while ($row = $rows->fetch()) {
			$res[] = $row;
		}

		return $res;
	}

	// insert rows
	// update
	// delete
	public static function query_sql_rows($query, $data = array(), $check = true) {
		list($r,$res) = self::query_sql($query,$data,$check);
		if (!$r) {
			return false;
		}

		// trigger_error(debug::dumpCaller(),debug::WARNING);
		return $res->rowCount();
	}

	// insert row
	public static function query_sql_insert($query, $data = array(), $check = true) {
		list($r) = self::query_sql($query,$data,$check);
		if (!$r) {
			return false;
		}

		return self::$db->lastInsertId();
	}


	/*
	// select row
	db::query_sql_item();

	// select rows
	db::query_sql_items();

	// update rows
	// delete rows
	// insert rows
	db::query_sql_rows();

	// insert row
	db::query_sql_insert(); */



	// ($params)
	// or
	// ($query_type, $params)
	public static function query() {
		$args = func_get_args();
		if (count($args) > 1) {
			list($query_op, $params) = $args;
		} else {
			list($params) = $args;
			if (!isset($params['_query'])) {
				trigger_error(debug::_('DB_QUERY_REQUIRED_QUERY_TYPE','params[\'_query\']'),debug::WARNING);
				return false;
			}
			$query_op = $params['_query'];
			unset($params['_query']);
		}

		switch ($query_op) {
			case 'item':
				$res = self::item($params);
				break;
			case 'items':
				$res = self::items($params);
				break;
			case 'insert':
				$res = self::insert($params);
				break;
			case 'update':
				$res = self::update($params);
				break;
			case 'delete':
				$res = self::delete($params);
				break;
			default:
				trigger_error(debug::_('DB_QUERY_UNKNOWN_QUERY_TYPE',$query_op),debug::WARNING);
				break;
		}
		return $res;
	}


	private static function sql_table($table) {
		list($sql_table, $alias) = split_str(':',$table);
		$sql_table = trim($sql_table);
		$alias = trim($alias);
		if ($alias) {
			$sql_table .= ' as '.$alias;
		} else {
			$alias = $sql_table;
		}
		return array($sql_table, $alias);
	}

	private static $join_level = 0;

	private static function sql_on($field_on, $table) {
		$field_on = trim($field_on);
		if ( ($field_on !== null) && !is_numeric($field_on) && ($field_on[0] !== ':') && (strpos($field_on,'.') === false) ) {
			$field_on = $table.'.'.$field_on;
		}
		return $field_on;
	}

	// to-do: vulnerable on without placeholders
	private static function sql_on_conds($join_on, $table, $join_table) { // , $level = 0

		$on_fields = array();

		$cond_op = 'AND';
		if (isset($join_on['_op'])) {
			$cond_op = strtoupper($join_on['_op']);
			unset($join_on['_op']);
		}

		foreach ($join_on as $field_on => $field_join) {
			if (is_numeric($field_on)) {
				if (is_array($field_join)) {
					// $field_join;

					$on_conds = self::sql_on_conds($field_join, $table, $join_table);
					$on_fields[] = '('.$on_conds.')';
				} else {
					$on_fields[] = $field_join;
				}
			} else {
				list($field_on, $op) = self::sql_where_cond($field_on);

				/* if ($field_join[0] === ':') {
					$field_join = substr($field_join,1);
					$join_val = 0;
					if (isset(self::$params[$field_join])) {
						$join_val = (int) self::$params[$field_join];
					}
				} else {
					$join_val = self::sql_on($field_join, $join_table);
				} */

				$on_fields[] = self::sql_on($field_on, $table).' '.$op.' '.self::sql_on($field_join, $join_table);
			}
		}
		return implode(' '.$cond_op.' ',$on_fields);
	}

	private static $params = array();

	private static function sql_join($table, $joins) {
		$data = array();
		$tables_fields = array();

		self::$join_level++;

		$sql_join = '';

		if (isset($joins['table'])) {
			$joins = array($joins);
		}

		foreach ($joins as $join) {
			// $join_table = $join['table'];
			list($join_table_sql, $join_table) = self::sql_table($join['table']);

			$fields = '';
			if (isset($join['fields'])) {
				$fields = $join['fields'];
			}
			$tables_fields[$join_table] = $fields; // $join_table.'.field1';

			$join_type = 'left';
			if (isset($join['_join'])) {
				$join_type = $join['_join'];
			}

			if (!isset($join['on'])) {
				trigger_error(debug::_('DB_SQL_JOIN_CONDITION_REQUIRED','join[\'on\']'),debug::WARNING); // $joins
				return false;
			}
			if (!is_array($join['on'])) {
				list($field_on, $field_join) = split_str('=', $join['on']);
				$sql_on = self::sql_on($field_on, $table).' = '.self::sql_on($field_join, $join_table);
			} else {

				$sql_on = self::sql_on_conds($join['on'],$table, $join_table);
				// $sql_on = implode(' '.$cond_op.' ',$on_fields);
			}

			$offset = str_repeat('    ', self::$join_level);
			$sql_join .= " \n".$offset.strings::format('{0} JOIN {1} ON {2}', strtoupper($join_type), $join_table_sql, $sql_on);

			// $sql_join .= " \n".$offset.strings::format('{0} JOIN {1} ON {2} = {3}', strtoupper($join_type), $join_table_sql, $table_join_field, $join_field);

			if (isset($join['join'])) {
				$sub_joins = $join['join'];
				$res = self::sql_join($join_table, $sub_joins);

				if ($res === false) {
					return false;
				}

				list($sql_sub_join, $tables_sub_fields, $sub_data) = $res;

				$data = array_merge($data, $sub_data);
				$tables_fields = array_merge($tables_fields, $tables_sub_fields);
				$sql_join .= $sql_sub_join;
			}
		}

		self::$join_level--;

		return array($sql_join, $tables_fields, $data);
	}

	private static function sql_where_cond($field) {
		$op = '=';
		$field = strtolower(trim($field));
		foreach (array('!=','<=','>=','=','>','<',' like') as $_op) {
			if (ends_with($field,$_op)) {
				$field = rtrim(remove_right($field,$_op));
				$op = $_op;
				break;
			}
		}
		return array($field, $op);
	}

	private static $param = 0;

	private static function reset_params() {
		self::$param = 0;
		self::$params = array();
	}

	private static function sql_param() {
		self::$param++;
		return 'param'.self::$param;
	}

	private static function sql_where($where, $level = 0) {
		$where = self::normalize_cond($where);

		$level++;

		$data = array();

		$where_arr = array();

		$cond_op = strtoupper($where['_op']);
		unset($where['_op']);

		$op_is_not = false;
		$_cond_op = $cond_op;
		if ($cond_op === 'NOT') {
			$op_is_not = true;
			$_cond_op = 'AND';
		}
//		var_dump($op_is_not);

		// $offset = str_repeat('    ', $level);

		foreach ($where as $field => $val) {
			/* if (in_array($field, array('and','or','not'))) {
				$field;
				$val = ;
				$field = 0;
			} */
			if (is_numeric($field)) {
				if (is_array($val)) {
					$sub_where = $val;

					list($sql_sub_where, $sub_data, $cond_sub_op) = self::sql_where($sub_where, $level);

					$data = array_merge($data, $sub_data);

	//				var_dump($op_is_not);
					// if (!$op_is_not && ($cond_sub_op === $cond_op) ) {
					if ($cond_sub_op === $cond_op) {
						if ($sql_sub_where !== '') {
							$where_arr[] = $sql_sub_where;
						}
					} else {
						$sub_offset = "\t".'    ';
						if ($sql_sub_where[0] === '(') {
							$sql_sub_where = "\n\t".$sql_sub_where;
						}
						$sql_sub_where = str_replace("\t", $sub_offset, $sql_sub_where);
						$sql_sub_where = '('.$sql_sub_where."\n\t".')';
						if ($cond_sub_op === 'NOT') {
							$sql_sub_where = 'NOT '.$sql_sub_where;
						}
						$where_arr[] = $sql_sub_where;
					}
				} else {
					$where_arr[] = $val;
				}
			} else {
				list($field, $op) = self::sql_where_cond($field);

				if (is_array($val)) {
					// "in" sql-statement
					$s = array('\'_NULL_\'');
					/* if (count($val) > 0) {
						$s = str_repeat('?,',count($val)-1).'?';
					} */
					if (count($val) > 0) {
						$s = array();
						foreach ($val as $v) {
							$param = self::sql_param();
							$s[] = ':'.$param;
							$data[$param] = $v;
						}
					}
					$where_cond = $field.' IN ('.implode(',',$s).')';
					// $data = array_merge($data,$val);
				} else if ($val === null) {
					if ($op === '=') {
						$where_cond = $field.' IS NULL';
					} else {
						$where_cond = $field.' IS NOT NULL';
					}
				} else {
					if ( ($val != '') && ($val[0] === ':') ) {
						$param = substr($val,1);
					} else {
						$param = self::sql_param();
						// $where_cond = $field.' '.$op.' ?';
						$data[$param] = $val;
					}
					$where_cond = $field.' '.$op.' :'.$param;
				}
				$where_arr[] = "\n\t".$where_cond;
			}
		}

		$sql_where = implode(' '.$_cond_op.' ', $where_arr);

		$level--;

		if ($level === 0) {
			$offset = '    ';
			if ($op_is_not) {
				$sql_where = 'NOT ('.$sql_where."\n".$offset.')';
				$offset = $offset.'    ';
			}
			$sql_where = str_replace("\t",$offset,$sql_where);
		}

		return array($sql_where, $data, $cond_op);
	}

	// dump last called query
	private static function _dump_query() {
		trigger_error(debug::_('DB_DUMP_QUERY_NOT_IMPLEMENTED'),debug::NOTICE);
	}

	private static function error($msg) {
		self::_dump_query();

		$args = func_get_args();
		return call_user_func_array('debug::_',$args);
	}

	private static function sql_fields($fields, $table = '') {
		if (!$fields) {
			$fields = array();
		} else {
			if (!is_array($fields)) {
				if (!$table) {
					trigger_error(self::error('DB_SQL_FIELDS_TABLE_IS_EMPTY',$fields),debug::NOTICE);
					return $fields;
				}
				// $fields = explode(',',$fields);
				// preg_match_all('/([^,\'"]|\'[^\']*\'|"[^"]*")+/',$fields,$matches);
				preg_match_all('/([^,\'"(]|\'[^\']*\'|"[^"]*"|\([^)]*\))+/',$fields,$matches);
				$fields = $matches[0];
			}
		}

		$fields_arr = array();
		foreach ($fields as $field) {
			list($field, $alias) = extend_arr( split_str(':',trim($field),false,false), 2 );
			$field = trim($field);
			$alias = trim($alias);

			if ($table) {
				$table_prefix = $table.'.';
				if (strpos($field,'(') === false && strpos($field,$table_prefix) === false) {
					$field = $table_prefix.$field;
				}
			}
			if ($alias !== '') {
				$field .= ' as '.$alias;
			}
			$fields_arr[] = $field;
		}
		return implode(', ',$fields_arr);
	}

	private static function sql_limit($limit) {
		$sql_limit = $limit;
		if (!is_scalar($limit)) {
			if (count($limit) === 0) {
				trigger_error(debug::_('DB_QUERY_LIMIT_EMPTY',strings::dump($limit)),debug::WARNING);
				return '';
			} else if (count($limit) > 1) {
				list($from, $len) = $limit;
				$from = (int)$from;
				$len = (int)$len;
				$sql_limit = $from.', '.$len;
			} else {
				list($count) = $limit;
				$count = (int)$count;
				if ($count < 1) $count = 0;
				$sql_limit = $count.'';
			}
		}
		return $sql_limit;
	}

	private static function quot($s) {
		return '`'.$s.'`';
	}




	private static $profiling = false;

	public static function query_item($params, $reset_query = true) {
		// params: table, where
		// fields = '*',
		// join = '',
		// group by, having

		if ($reset_query) {
			self::$profiling = false;
			if (isset($params['profiling']) && $params['profiling']) {
				self::$profiling = true;
			}

			self::reset_params();
		}

		$query_data = array();
		if (isset($params['params']) && is_array($params['params'])) {
			$query_data = $params['params'];
			self::$params = $query_data;
		}

		list(
			$fields, $table, $union, $join, $where,
			$group_by, $having
		) = array_to_list($params, array(
			'fields', 'table', 'union', 'join', 'where',
			'group by', 'having'
		));
		if ($fields === '') $fields = '*';

		// $query_data = array();
		/* if ($query_params && is_array($query_params)) {
			$query_data = $query_params;
		} */

		if ($union) {
			if (!is_array($union)) {
				trigger_error(debug::_('DB_ITEM_ATTR_UNION_MUST_BE_ARRAY'),debug::WARNING);
				return false;
			}

			if ($join) {
				trigger_error(debug::_('DB_ITEM_ATTR_JOIN_IGNORED_WHEN_UNION'),debug::WARNING);
				$join = '';
			}

			$union_type = 'union all';
			if (isset($union['distinct'])) {
				if ($union['distinct']) { // true
					$union_type = 'union';
				}
				unset($union['distinct']);
			}

			$union_name = 'union1';
			if (isset($union['name'])) {
				$union_name = $union['name'];
				unset($union['name']);
			}

			$union_tables = array();
			foreach ($union as $union_item) {
				list($union_query, $union_data) = self::query_items($union_item, false);
				$union_tables[] = $union_query;
				$query_data = array_merge($query_data, $union_data);
				// list($union_query, $union_data) = $union_item;
			}

			$sql_tables = '('.implode(' '.strtoupper($union_type).' ',$union_tables).') as '.$union_name;
			$table = $union_name;

		} else {
			// tables
			// $sql_tables = $table;
			list($sql_table, $table) = self::sql_table($table);
			$sql_tables = $sql_table;
			$tables_fields = array($table => $fields);

			if ($join) {
				self::$join_level = 0;
				$res = self::sql_join($table, $join);

				if ($res === false) {
					return false;
				}

				list($sql_join, $join_fields, $data) = $res;

				$sql_tables .= $sql_join;
				$query_data = array_merge($query_data, $data);
				$tables_fields = array_merge($tables_fields, $join_fields);
			}
		}

		// fields
		if ($join) {
			// $sql_fields = '';
			$fields_arr = array();
			foreach ($tables_fields as $table_name => $table_fields) {
				$flds = self::sql_fields($table_fields, $table_name);
				if ($flds) {
					$fields_arr[] = $flds;
				}
			}
			$sql_fields = implode(', ',$fields_arr);
		} else {
			$sql_fields = self::sql_fields($fields, $table);
		}

		// where
		$sql_where = '';
		if ($where) {
			list($sql_where, $data) = self::sql_where($where);
			$query_data = array_merge($query_data, $data);
		} else {
			if (!$union) {
				// !!!
				trigger_error(debug::_('DB_ITEM_ATTR_IS_REQUIRED','where'),debug::WARNING);
				return false;
			}
		}

		// group by
		$sql_group_by = '';
		if ($group_by) {
			if (!is_scalar($group_by)) {
				$sql_group_by = implode(', ',$group_by);
			} else {
				$sql_group_by = $group_by;
			}
		}

		// having
		$sql_having = '';
		if ($having) {
			// self::$where_level = 0;
			list($sql_having, $data) = self::sql_where($having);
			$query_data = array_merge($query_data, $data);
		}

		// $query = strings::format('SELECT {0} '."\n".'FROM {1}', $sql_fields, $sql_tables);
		$query = strings::format('SELECT {0} '."\n".'FROM {1} '."\n".'WHERE {2}', $sql_fields, $sql_tables, $sql_where);

		foreach (array(
			// 'where' => $sql_where,
			'group by' => $sql_group_by,
			'having' => $sql_having,
			'limit' => '1'
		) as $param => $value) {
			if ($value) {
				$query .= " \n".strings::format('{0} {1}',strtoupper($param),$value);
			}
		}

		// 'SELECT $fields FROM $tables WHERE $where GROUP BY $group_by HAVING $having LIMIT 1';
		return array($query, $query_data);
	}


	private static function _query_item($params) {

		$debug = false;
		if (isset($params['debug']) && $params['debug']) {
			$debug = true;
			debug::channelPush('sql');
		}

		self::reset_params();

		list($query, $query_data) = self::query_item($params,true);

		$res = self::query_sql_item($query, $query_data, false);

		if ($debug) {
			debug::channelPop('sql');
		}

		return $res;
	}

	public static function query_items($params, $reset_query = true) {
		// params: table,
		// where = '',
		// fields = '*',
		// order by = '',
		// join = '',
		// limit, group by, having

		if ($reset_query) {
			self::$profiling = false;
			if (isset($params['profiling']) && $params['profiling']) {
				self::$profiling = true;
			}

			self::reset_params();
		}

		$query_data = array();
		if (isset($params['params']) && is_array($params['params'])) {
			$query_data = $params['params'];
			self::$params = $query_data;
		}

		list(
			$fields, $table, $union, $join, $where,
			$group_by, $having, $order_by, $limit
		) = array_to_list($params, array(
			'fields', 'table', 'union', 'join', 'where',
			'group by', 'having', 'order by', 'limit'
		));
		if ($fields === '') $fields = '*';

		/* $query_data = array();
		if ($query_params && is_array($query_params)) {
			$query_data = $query_params;
		} */

		if ($union) {
			if (!is_array($union)) {
				trigger_error(debug::_('DB_ITEMS_ATTR_UNION_MUST_BE_ARRAY'),debug::WARNING);
				return false;
			}

			if ($join) {
				trigger_error(debug::_('DB_ITEMS_ATTR_JOIN_IGNORED_WHEN_UNION'),debug::WARNING);
				$join = '';
			}

			$union_type = 'union all';
			if (isset($union['distinct'])) {
				if ($union['distinct']) { // true
					$union_type = 'union';
				}
				unset($union['distinct']);
			}

			$union_name = 'union1';
			if (isset($union['name'])) {
				$union_name = $union['name'];
				unset($union['name']);
			}

			$union_tables = array();
			foreach ($union as $union_item) {
				list($union_query, $union_data) = self::query_items($union_item, false);
				$union_tables[] = '    '.str_replace("\n","\n".'    ',$union_query);
				$query_data = array_merge($query_data, $union_data);
			}

			$sql_tables = '('."\n".implode("\n".strtoupper($union_type)."\n",$union_tables)."\n".') as '.$union_name;
			$table = $union_name;

		} else {
			// tables
			// $sql_tables = $table;
			list($sql_table, $table) = self::sql_table($table);
			$sql_tables = $sql_table;
			$tables_fields = array($table => $fields);

			if ($join) {
				self::$join_level = 0;
				$res = self::sql_join($table, $join);

				if ($res === false) {
					return false;
				}

				list($sql_join, $join_fields, $data) = $res;

				$sql_tables .= $sql_join;
				$query_data = array_merge($query_data, $data);
				$tables_fields = array_merge($tables_fields, $join_fields);
			}
		}

		// fields
		if ($join) {
			// $sql_fields = '';
			$fields_arr = array();
			foreach ($tables_fields as $table_name => $table_fields) {
				$flds = self::sql_fields($table_fields, $table_name);
				if ($flds) {
					$fields_arr[] = $flds;
				}
			}
			$sql_fields = implode(', ',$fields_arr);
		} else {
			$sql_fields = self::sql_fields($fields, $table);
		}


		// where
		$sql_where = '';
		if ($where) {
			list($sql_where, $data) = self::sql_where($where);
			$query_data = array_merge($query_data, $data);
		}

		// group by
		$sql_group_by = '';
		if ($group_by) {
			if (!is_scalar($group_by)) {
				$sql_group_by = implode(', ',$group_by);
			} else {
				$sql_group_by = $group_by;
			}
		}

		// having
		$sql_having = '';
		if ($having) {
			// self::$where_level = 0;
			list($sql_having, $data) = self::sql_where($having);
			$query_data = array_merge($query_data, $data);
		}

		// order by
		$sql_order_by = '';
		if ($order_by) {
			if (!is_scalar($order_by)) {
				$sql_order_by = implode(', ',$order_by);
			} else {
				$sql_order_by = $order_by;
			}
		}

		// limit
		$sql_limit = '';
		if ($limit) {
			$sql_limit = self::sql_limit($limit);
		}

		$query = strings::format('SELECT {0} '."\n".'FROM {1}', $sql_fields, $sql_tables);

		foreach (array(
			'where' => $sql_where,
			'group by' => $sql_group_by,
			'having' => $sql_having,
			'order by' => $sql_order_by,
			'limit' => $sql_limit
		) as $param => $value) {
			if ($value) {
				$query .= " \n".strings::format('{0} {1}',strtoupper($param),$value);
			}
		}

		// 'SELECT $fields FROM $tables WHERE $where GROUP BY $group_by HAVING $having ORDER BY $order_by LIMIT $limit';

		return array($query, $query_data);
	}

	private static function _query_items($params) {

		$debug = false;
		if (isset($params['debug']) && $params['debug']) {
			$debug = true;
			debug::channelPush('sql');
		}

		self::reset_params();

		list($query, $query_data) = self::query_items($params,true);

		$res = self::query_sql_items($query, $query_data, false);

		if ($debug) {
			debug::channelPop('sql');
		}

		return $res;
	}

	public static function query_insert($params, $reset_query = true) {
		// params: table,
		// fields = [fld1 => val1, fld2 => val2],
		// join = '',

		if ($reset_query) {
			self::$profiling = false;
			if (isset($params['profiling']) && $params['profiling']) {
				self::$profiling = true;
			}

			self::reset_params();
		}

		$query_data = array();
		if (isset($params['params']) && is_array($params['params'])) {
			$query_data = $params['params'];
			self::$params = $query_data;
		}

		list(
			$fields, $table, $join
		) = array_to_list($params, array(
			'fields', 'table', 'join'
		));
		// if ($fields === '') $fields = '*';

		/* $query_data = array();
		if ($query_params && is_array($query_params)) {
			$query_data = $query_params;
		} */

		// tables
		list($sql_table, $table) = self::sql_table($table);
		$sql_tables = $sql_table;
		// $sql_tables = $table;

		if ($join) {
			self::$join_level = 0;
			$res = self::sql_join($table, $join);

			if ($res === false) {
				return false;
			}

			list($sql_join, $join_fields, $data) = $res;

			$sql_tables .= $sql_join;
			$query_data = array_merge($query_data, $data);
			// $tables_fields = array_merge($tables_fields, $join_fields);
		}

		// fields
		// !!!

		$fields_arr = array();
		$values_arr = array();
		$values = array();
		foreach ($fields as $fld => $val) {
			$param = self::sql_param();
			// $values_arr[] = '?';
			$values_arr[] = ':'.$param;
			$fields_arr[] = $fld;

			$values[$param] = $val;
			// [$param] = $val;
		}
		$query_data = array_merge($query_data, $values);

		$sql_fields = '(`'.implode('`, `',$fields_arr).'`)';

		$sql_values = '(\'_NULL_\')';
		if (count($fields) > 0) {
			$sql_values = '('.implode(', ',$values_arr).')';
			// $sql_values = '('.str_repeat('?,',count($fields)-1).'?)';
		}


		/* if ($join) {
			// $sql_fields = '';
			$fields_arr = array();
			foreach ($tables_fields as $table_name => $table_fields) {
				$fields_arr[] = self::sql_fields($table_fields, $table_name);
			}
			$sql_fields = implode(', ',$fields_arr);
		} else {
			$sql_fields = self::sql_fields($fields, $table);
		} */

		// values

		$query = strings::format('INSERT INTO {0} '."\n".'{1} '."\n".'VALUES {2}', $sql_tables, $sql_fields, $sql_values);

		/* foreach (array(
			'where' => $sql_where,
			'group by' => $sql_group_by,
			'having' => $sql_having
		) as $param => $value) {
			if ($value) {
				$query .= " \n".strings::format('{0} {1}',strtoupper($param),$value);
			}
		} */

		// 'INSERT INTO $tables ($fields) VALUES ($values)';

		return array($query, $query_data);
	}

	private static function _query_insert($params) {

		$debug = false;
		if (isset($params['debug']) && $params['debug']) {
			$debug = true;
			debug::channelPush('sql');
		}

		self::reset_params();

		list($query, $query_data) = self::query_insert($params,true);

		$res = self::query_sql_insert($query,$query_data,false);

		if ($debug) {
			debug::channelPop('sql');
		}

		return $res;
	}

	public static function query_insert_rows($params, $reset_query = true) {
		// params: table,
		// fields = [ row1, row2 ],
		// join = '',

		if ($reset_query) {
			self::$profiling = false;
			if (isset($params['profiling']) && $params['profiling']) {
				self::$profiling = true;
			}

			self::reset_params();
		}

		$query_data = array();
		if (isset($params['params']) && is_array($params['params'])) {
			$query_data = $params['params'];
			self::$params = $query_data;
		}

		list(
			$fields, $table, $join
		) = array_to_list($params, array(
			'fields', 'table', 'join'
		));

		/* $query_data = array();
		if ($query_params && is_array($query_params)) {
			$query_data = $query_params;
		} */

		// tables
		list($sql_table, $table) = self::sql_table($table);
		$sql_tables = $sql_table;
		// $sql_tables = $table;

		if ($join) {
			self::$join_level = 0;
			$res = self::sql_join($table, $join);

			if ($res === false) {
				return false;
			}

			list($sql_join, $join_fields, $data) = $res;

			$sql_tables .= $sql_join;
			$query_data = array_merge($query_data, $data);
			// $tables_fields = array_merge($tables_fields, $join_fields);
		}

		// fields
		// !!!


		$fields_arr = array_keys(first($fields,array()));
		$n_fields = count($fields_arr);
		if ($n_fields <= 0) {
			trigger_error(debug::_('DB_INSERT_ROWS_FIELDS_EMPTY','table:'.$table.' fields:'.strings::dump($fields)),debug::WARNING);
			return false;
		}

		$n_rows = count($fields);
		$values = array();

		$sql_values = '';
		foreach ($fields as $row) {
			$row_val = array();
			foreach ($fields_arr as $fld) {
				$val = '';
				if (isset($row[$fld])) {
					$val = $row[$fld];
				}
				$param = self::sql_param();
				$row_val[] = ':'.$param;
				$values[$param] = $val;
			}
			if ($sql_values !== '') {
				$sql_values .= ', ';
			}
			$sql_values .= '('.implode(', ',$row_val).')';
		}
		$query_data = array_merge($query_data, $values);

		$sql_fields = '(`'.implode('`, `',$fields_arr).'`)';
		// $sql_values = '('.str_repeat('?,',count($fields)-1).'?)';

/*		$row_val = '(\'_NULL_\')';
		if ($n_fields > 0) {
			$row_val = '('.str_repeat('?,',$n_fields-1).'?)';
		}
		$sql_values = str_repeat($row_val.', ',$n_rows-1).$row_val; */


		/* if ($join) {
			// $sql_fields = '';
			$fields_arr = array();
			foreach ($tables_fields as $table_name => $table_fields) {
				$fields_arr[] = self::sql_fields($table_fields, $table_name);
			}
			$sql_fields = implode(', ',$fields_arr);
		} else {
			$sql_fields = self::sql_fields($fields, $table);
		} */

		// values
		$values = array();
		$sql_values = implode('',$values); // '('.'), ('.')';

		$query = strings::format('INSERT INTO {0} '."\n".'{1} '."\n".'VALUES {2}', $sql_tables, $sql_fields, $sql_values);

		// 'INSERT INTO $tables ($fields) VALUES ($values1), ($values2)';

		return array($query, $query_data);
	}

	private static function _query_insert_rows($params) {

		$debug = false;
		if (isset($params['debug']) && $params['debug']) {
			$debug = true;
			debug::channelPush('sql');
		}

		self::reset_params();

		list($query, $query_data) = self::query_insert_rows($params,true);

		$res = self::query_sql_rows($query,$query_data,false);

		if ($debug) {
			debug::channelPop('sql');
		}

		return $res;
	}

	public static function query_update($params, $reset_query = true) {
		// params: table, fields
		// where = '',
		// join = '',

		if ($reset_query) {
			self::$profiling = false;
			if (isset($params['profiling']) && $params['profiling']) {
				self::$profiling = true;
			}

			self::reset_params();
		}

		$query_data = array();
		if (isset($params['params']) && is_array($params['params'])) {
			$query_data = $params['params'];
			self::$params = $query_data;
		}

		list(
			$fields, $table, $join, $where
		) = array_to_list($params, array(
			'fields', 'table', 'join', 'where'
		));

		/* $query_data = array();
		if ($query_params && is_array($query_params)) {
			$query_data = $query_params;
		} */

		// tables
		list($sql_table, $table) = self::sql_table($table);
		$sql_tables = $sql_table;
		// $sql_tables = $table;

		if ($join) {
			self::$join_level = 0;
			$res = self::sql_join($table, $join);

			if ($res === false) {
				return false;
			}

			list($sql_join, $join_fields, $data) = $res;

			$sql_tables .= $sql_join;
			$query_data = array_merge($query_data, $data);
			// $tables_fields = array_merge($tables_fields, $join_fields);
		}

		// fields
		// !!!

		$fields_arr = array();
		foreach ($fields as $fld => $val) {
			/* $values_arr[] = '?';
			$fields_arr[] = $fld;

			$values[] = $val; */
			if (!is_array($val)) {
				$param = self::sql_param();
				// $fields_arr[] = $fld.' = ?';
				$fields_arr[] = self::quot($fld).' = :'.$param;
				$values[$param] = $val;
			} else { // $val = array('views + ?',1)
				$s = array_shift($val);
				$values = array();
				$s = preg_replace_callback('/\?/', function($matches) use ($values) {
					$param = self::sql_param();
					$values[$param] = '';
					return ':'.$param;
				}, $s);
				foreach ($values as $param => $v) {
					if (count($val) == 0) {
						break;
					}
					$values[$param] = array_shift($val);
				}
				// $values = $params; // array_merge($values, $params);
				// $values = array_merge($values, $val);
				$fields_arr[] = self::quot($fld).' = '.$s;
			}
		}
		$query_data = array_merge($query_data, $values);

		$sql_fields = implode(', ',$fields_arr);

		/* $sql_fields = '('.implode(', ',$fields_arr).')';
		// $sql_values = '('.implode(', ',$values_arr).')';
		$sql_values = '('.str_repeat('?,',count($fields)-1).'?)'; */

/*		if ($join) {
			// $sql_fields = '';
			$fields_arr = array();
			foreach ($tables_fields as $table_name => $table_fields) {
				$fields_arr[] = self::sql_fields($table_fields, $table_name);
			}
			$sql_fields = implode(', ',$fields_arr);
		} else {
			$sql_fields = self::sql_fields($fields, $table);
		} */

		// where
//		$sql_where = '';
		// !!!

		/* if ($where) {
			self::$where_level = 0;
			list($sql_where, $data) = self::sql_where($where);
			$query_data = array_merge($query_data, $data);
		} */

//		if (!$where) {
			// !!!
//			return false;
//		}
		// self::$where_level = 0;
		list($sql_where, $data) = self::sql_where($where);
		$query_data = array_merge($query_data, $data);

		// $query = strings::format('UPDATE {0} '."\n".'SET {1}', $sql_tables, $sql_fields);
		$query = strings::format('UPDATE {0} '."\n".'SET {1} '."\n".'WHERE {2}', $sql_tables, $sql_fields, $sql_where);

		/* if ($sql_where) {
			" \n".'WHERE '.$sql_where;
		} */

		// 'UPDATE $tables SET $fields WHERE $where';

		return array($query, $query_data);
	}

	private static function _query_update($params) {

		$debug = false;
		if (isset($params['debug']) && $params['debug']) {
			$debug = true;
			debug::channelPush('sql');
		}

		self::reset_params();

		list($query, $query_data) = self::query_update($params,true);

		$res = self::query_sql_rows($query,$query_data,false);

		if ($debug) {
			debug::channelPop('sql');
		}

		return $res;
	}

	private static function query_delete($params, $reset_query = true) {
		// params: table
		// where = '',
		// join = '',

		if ($reset_query) {
			self::$profiling = false;
			if (isset($params['profiling']) && $params['profiling']) {
				self::$profiling = true;
			}

			self::reset_params();
		}

		$query_data = array();
		if (isset($params['params']) && is_array($params['params'])) {
			$query_data = $params['params'];
			self::$params = $query_data;
		}

		list(
			$table, $delete_tables, $join, $where
		) = array_to_list($params, array(
			'table', 'tables', 'join', 'where'
		));

		if ($delete_tables != '') {
			$delete_tables = $delete_tables.' ';
		}

		/* $query_data = array();
		if ($query_params && is_array($query_params)) {
			$query_data = $query_params;
		} */

		// tables
		list($sql_table, $table) = self::sql_table($table);
		$sql_tables = $sql_table;
		// $sql_tables = $table;

		if ($join) {
			self::$join_level = 0;
			$res = self::sql_join($table, $join);

			if ($res === false) {
				return false;
			}

			list($sql_join, $join_fields, $data) = $res;

			$sql_tables .= $sql_join;
			$query_data = array_merge($query_data, $data);
			// $tables_fields = array_merge($tables_fields, $join_fields);
		}

		// where
//		$sql_where = '';
		/* if ($where) {
			self::$where_level = 0;
			list($sql_where, $data) = self::sql_where($where);
			$query_data = array_merge($query_data, $data);
		} */

//		if (!$where) {
			// !!!
//			return false;
//		}
		// self::$where_level = 0;
		list($sql_where, $data) = self::sql_where($where);
		$query_data = array_merge($query_data, $data);

		// $query = 'DELETE FROM '.$sql_tables;
		$query = strings::format('DELETE '.$delete_tables.'FROM {0} '."\n".'WHERE {1}', $sql_tables, $sql_where);

		// 'DELETE FORM $tables WHERE $where';

		return array($query, $query_data);
	}

	private static function _query_delete($params) {

		$debug = false;
		if (isset($params['debug']) && $params['debug']) {
			$debug = true;
			debug::channelPush('sql');
		}

		self::reset_params();

		list($query, $query_data) = self::query_delete($params,false);

		$res = self::query_sql_rows($query,$query_data,false); // reset autoinc

		if ($debug) {
			debug::channelPop('sql');
		}

		return $res;
	}

	public static function reset_autoinc($table, $id = 1) {
		$id = intval($id);
		if ($id < 1) $id = 1;
		$query = 'ALTER TABLE '.$table.' AUTO_INCREMENT = '.$id;
		list($res) = self::query_sql($query,array(),false);
		return $res;
	}



	// ($params)
	// or
	// ($table, $where, $fields = '')
	public static function item() {
		list($table, $where, $fields) = extend_arr(func_get_args(),3);
		if (!is_scalar($table)) {
			$params = $table;
		} else {
			$params = array(
				'table' => $table,
				'where' => $where,
				'fields' => $fields
			);
		}

		/* $required = array('table','where');
		foreach ($required as $attr) {
			if (!isset($params[$attr]) || ($params[$attr] === '')) {
				trigger_error(debug::_('DB_ITEM_ATTR_IS_REQUIRED',$attr),debug::WARNING);
				return false;
			}
		} */
		if ( (!isset($params['table']) || $params['table'] == '') && (!isset($params['union']) || $params['union'] == '') ) {
			trigger_error(debug::_('DB_ITEMS_ATTR_IS_REQUIRED','table'),debug::WARNING);
			return false;
		}
		if (!isset($params['where']) || $params['where'] == '') {
			trigger_error(debug::_('DB_ITEMS_ATTR_IS_REQUIRED','where'),debug::WARNING);
			return false;
		}

		return self::_query_item($params);
	}

	// ($params)
	// or
	// ($table, $where = '', $fields = '', $order_by = '')
	public static function items() {
		list($table, $where, $fields, $order_by) = extend_arr(func_get_args(),4);
		if (!is_scalar($table)) {
			$params = $table;
		} else {
			$params = array(
				'table' => $table,
				'where' => $where,
				'fields' => $fields,
				'order by' => $order_by
			);
		}

		if ( (!isset($params['table']) || $params['table'] == '') && (!isset($params['union']) || $params['union'] == '') ) {
			trigger_error(debug::_('DB_ITEMS_ATTR_IS_REQUIRED','table'),debug::WARNING);
			return false;
		}

		return self::_query_items($params);
	}

	// ($params)
	// or
	// ($table, $fields)
	// or
	// ($table, $fields = [$row1, $row2, ..])
	public static function insert() {
		list($table, $fields) = extend_arr(func_get_args(),2);
		if (!is_scalar($table)) {
			$params = $table;
		} else {
			$params = array(
				'table' => $table,
				'fields' => $fields
			);
		}

		$required = array('table','fields');
		foreach ($required as $attr) {
			if (!isset($params[$attr]) || ($params[$attr] === '')) {
				trigger_error(debug::_('DB_INSERT_ATTR_IS_REQUIRED',$attr),debug::WARNING);
				return false;
			}
		}

		if (is_scalar(first($params['fields']))) {
			return self::_query_insert($params);
		} else {
			return self::_query_insert_rows($params);
		}
	}

	// ($params)
	// or
	// ($table, $fields, $where = '')
	public static function update() {
		list($table, $fields, $where) = extend_arr(func_get_args(),3);
		if (!is_scalar($table)) {
			$params = $table;
		} else {
			$params = array(
				'table' => $table,
				'fields' => $fields,
				'where' => $where
			);
		}

		$required = array('table','fields');
		foreach ($required as $attr) {
			if (!isset($params[$attr]) || ($params[$attr] === '')) {
				trigger_error(debug::_('DB_UPDATE_ATTR_IS_REQUIRED',$attr),debug::WARNING);
				return false;
			}
		}

		return self::_query_update($params);
	}

	// ($params)
	// or
	// ($table, $where = '')
	public static function delete() {
		list($table, $where) = extend_arr(func_get_args(),2);
		if (!is_scalar($table)) {
			$params = $table;
		} else {
			$params = array(
				'table' => $table,
				'where' => $where
			);
		}

		if (!isset($params['table']) || $params['table'] == '') {
			trigger_error(debug::_('DB_DELETE_ATTR_IS_REQUIRED','table'),debug::WARNING);
			return false;
		}

		/* $required = array('table');
		foreach ($required as $attr) {
			if (!isset($params[$attr]) || ($params[$attr] === '')) {
				trigger_error(debug::_('DB_DELETE_ATTR_IS_REQUIRED',$attr),debug::WARNING);
				return false;
			}
		} */

		return self::_query_delete($params);
	}

/*
	db::insert('users', array('login' => '', 'pass' => '111'));
	db::insert(array(
		'table' => 'users',
		'fields' => array('login' => '', 'pass' => '111')
	));
	db::query(array(
		'_op' => 'insert', // '_op' => 'item', // '_op' => 'items',
		'table' => 'users',
		'fields' => array('login' => '', 'pass' => '111')
	));

	db::insert('users', array(
		array('login' => '', 'pass' => '111'),
		array('login' => '', 'pass' => '111')
	));
	db::insert(array(
		'table' => 'users',
		'fields' => array(
			array('login' => '', 'pass' => '111'),
			array('login' => '', 'pass' => '111')
		)
	));
	db::query(array(
		'_op' => 'insert',
		'table' => 'users',
		'fields' => array(
			array('login' => '', 'pass' => '111'),
			array('login' => '', 'pass' => '111')
		)
	));

	db::update('users', array('login' => ''), array('id' => 1));

	db::delete('users', array('id' => 1)); */

	public static function merge_fields($fields, $default_fields) {
		if (is_string($default_fields)) {
			if (is_array($fields)) {
				$fields = implode(',',$fields);
			}
			$fields = $default_fields.','.$fields;
		} else {
			if (is_string($fields)) {
				$fields = explode(',',$fields);
			}
			$fields = array_merge($default_fields,$fields);
		}
		return $fields;
	}

}

class table {

	private static function extend_query($params, $default_params) {
		if (isset($params['&where'])) {
			if (isset($default_params['where'])) {
				$params['where'] = db::_and($params['&where'],$default_params['where']);
			} else {
				$params['where'] = $params['&where'];
			}
			unset($params['&where']);
		}

		if (isset($params['&fields'])) {
			if (isset($default_params['fields'])) {
				$params['fields'] = db::merge_fields($params['&fields'],$default_params['fields']);
			} else {
				$params['fields'] = $params['&fields'];
			}
			unset($params['&fields']);
		}

		return extend($params, $default_params);
	}

	public static function getItems($query_name, $params = array(), $default = array()) {
		$table_name = get_called_class();

		if ($query_name === '') {
			trigger_error(debug::_('TABLE_GET_ITEMS_QUERY_NAME_NOT_SET',$table_name.'::getItems'),debug::WARNING);
			return $default;
		}

		// if ( (!isset($params['debug'])) && (!isset($params['profiling'])) && (!isset($params['params'])) && (!isset($params['where'])) && (!isset($params['&where'])) ) {
		//	$params['&where'] = $params;
		// }

		// $query = 'query_'.$query_name;
		$query = 'query'.ucfirst($query_name);
		$query2 = 'query_'.$query_name;

		if (!method_exists($table_name,$query) && !method_exists($table_name,$query2)) {
			trigger_error(debug::_('TABLE_GET_ITEMS_QUERY_DOESNOT_EXIST',$table_name.'::'.$query),debug::WARNING);
			return $default;
		}

		/* deprecated { */
		if (!method_exists($table_name,$query) && method_exists($table_name,$query2)) {
			$query = $query2;
			// !! trigger_error(debug::_('TABLE_GET_ITEMS_QUERY_NAME_DEPRECATED',$table_name.'::'.$query2),debug::DEPRECATED);
		}
		/* } */

		$query_params = $table_name::$query();

		if (!isset($query_params['table']) && !isset($query_params['union'])) {
			if (!is_array($query_params)) {
				$msg = 'TABLE_GET_ITEMS_QUERY_MUST_BE_ARRAY';
			} else {
				$msg = 'TABLE_GET_ITEMS_QUERY_TABLE_NOT_SET';
			}
			trigger_error(debug::_($msg,$table_name.'::'.$query),debug::WARNING);
			return $default;
		}
		$query_params = self::extend_query($params,$query_params);

		$items = db::items($query_params);

		if ($items === false) {
			// return array();
			return $default;
		}

		// prepare items
		$prepare_func = 'prepare'.ucfirst($query_name);
		$prepare_func2 = 'prepare_'.$query_name;

		if (!method_exists($table_name,$prepare_func) && method_exists($table_name,$prepare_func2)) {
			$prepare_func = $prepare_func2;
			// !! trigger_error(debug::_('TABLE_GET_ITEMS_QUERY_NAME_DEPRECATED',$table_name.'::'.$query2),debug::DEPRECATED);
		}

		if (method_exists($table_name,$prepare_func)) {
			$items = $table_name::$prepare_func($items);
			if (!is_array($items)) {
				trigger_error(debug::_('TABLE_GET_ITEMS_PREPARE_RESULT_IS_NOT_ARRAY',$table_name.'::'.$prepare_func),debug::WARNING);
			}
		}

		return $items;
	}

	public static function getItem($query_name, $params = array(), $default = false) {
		$table_name = get_called_class();

		// if ($default === '__default_val') {
		// 	$default = false;
		// }

		if ($query_name === '') {
			trigger_error(debug::_('TABLE_GET_ITEM_QUERY_NAME_NOT_SET',$table_name.'::getItem'),debug::WARNING);
			return $default;
		}

		if ( (!isset($params['params'])) && (!isset($params['where'])) && (!isset($params['&where'])) ) {
			$params['&where'] = $params;
		}

		// $query = 'query_'.$query_name;
		$query = 'query'.ucfirst($query_name);
		$query2 = 'query_'.$query_name;

		if (!method_exists($table_name,$query) && !method_exists($table_name,$query2)) {
			trigger_error(debug::_('TABLE_GET_ITEM_QUERY_DOESNOT_EXIST',$table_name.'::'.$query),debug::WARNING);
			return $default;
		}

		/* deprecated { */
		if (!method_exists($table_name,$query) && method_exists($table_name,$query2)) {
			$query = $query2;
			// !! trigger_error(debug::_('TABLE_GET_ITEM_QUERY_NAME_DEPRECATED',$table_name.'::'.$query2),debug::DEPRECATED);
		}
		/* } */

		$query_params = $table_name::$query();

		if (!isset($query_params['table']) && !isset($query_params['union'])) {
			if (!is_array($query_params)) {
				$msg = 'TABLE_GET_ITEM_QUERY_MUST_BE_ARRAY';
			} else {
				$msg = 'TABLE_GET_ITEM_QUERY_TABLE_NOT_SET';
			}
			trigger_error(debug::_($msg,$table_name.'::'.$query),debug::WARNING);
			return $default;
		}

		$query_params = self::extend_query($params,$query_params);

		$item = db::item($query_params);

		if ($item === false) {
			return $default;
		}

		// prepare items
		$prepare_func = 'prepare'.ucfirst($query_name);
		$prepare_func2 = 'prepare_'.$query_name;

		if (!method_exists($table_name,$prepare_func) && method_exists($table_name,$prepare_func2)) {
			$prepare_func = $prepare_func2;
			// !! trigger_error(debug::_('TABLE_GET_ITEMS_QUERY_NAME_DEPRECATED',$table_name.'::'.$query2),debug::DEPRECATED);
		}

		if (method_exists($table_name,$prepare_func)) { // .'::'.
			$item = $table_name::$prepare_func($item);
			if ( ($item !== null) && !is_object($item) ) {
				trigger_error(debug::_('TABLE_GET_ITEM_PREPARE_RESULT_IS_NOT_OBJECT',$table_name.'::'.$prepare_func),debug::WARNING);
			}
		}

		return $item;
	}

}
