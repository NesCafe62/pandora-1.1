<?php
defined ("CORE_EXEC") or die('Access Denied');

class sequence {

	private static $_sequence_id = 0;

	private static $_sequences = array();

	private $source_id;
	private $map_mode = 'concat';

	private $callbacks = array();
	private $filters = array();
	private $waitfor = array();

	private static function register_sequence($seq, $source_id) {
		if (!isset(self::$_sequences[$source_id])) {
			self::$_sequences[$source_id] = array();
		}
		self::$_sequences[$source_id][] = $seq;
	}

	public function __construct($parent_seq = null) {
		if ($parent_seq === null) {
			self::$_sequence_id++;
			$this->source_id = self::$_sequence_id;
		} else {
			$this->map_mode = $parent_seq->map_mode;
			$this->filters = $parent_seq->filters;
			$this->waitfor = $parent_seq->waitfor;
			$this->source_id = $parent_seq->source_id;
		}
		self::register_sequence($this, $this->source_id);
	}

	public function map($map_mode) {
		$seq = new sequence($this);
		
		if (!in_array($map_mode,array('insert_last','insert_first','concat'))) {
			$map_mode = 'concat';
		}
		$seq->map_mode = $map_mode;
		return $seq;
	}

	public function waitfor($flt) {
		$seq = new sequence($this);
		$seq->filters[] = $flt;
		$seq->waitfor[] = true;
		return $seq;
	}

	public function filter($flt) {
		$seq = new sequence($this);
		$seq->filters[] = $flt;
		$seq->waitfor[] = false;
		return $seq;
	}
	
	public function callback($func) {
		$this->callbacks[] = $func;
		return $this;
	}
	
	public function run($list, $map_func = null) {
		$sequences = self::$_sequences[$this->source_id];

		foreach ($sequences as $sequence) {
			if ($sequence->map_mode === 'concat') {
				$sequence->elements = '';
			} else {
				$sequence->elements = array();
			}
		}

		$has_map_func = is_function($map_func);

		foreach ($list as $el) {

			if ($has_map_func) {
				$el = $map_func($el);
			}

			$skip = false;

			foreach ($sequences as $sequence) {

				$waitfor_matched = false;
				$valid = true;

				$has_waitfor = false;
				
				foreach ($sequence->filters as $i => $flt) {
					$waitfor = $sequence->waitfor[$i];

					if (is_array($flt)) {
						$valid = in_array($el,$flt);
					} else if (is_function($flt)) {
						$valid = ($flt($el) === true);
					} else {
						$valid = ($flt === $el);
					}

					if ($waitfor) {
						$has_waitfor = true;

						$waitfor_matched = $valid;
						
					}
					if (!$valid) break;
				}
				if ($has_waitfor) {
					if ($waitfor_matched) {
						if ($valid) {
							foreach ($sequence->callbacks as $c) {
								$c($sequence->elements);
							}
							
						}
						
						if ($sequence->map_mode === 'concat') {
							$sequence->elements = '';
						} else {
							$sequence->elements = array();
						}
					} else {
						
						if ($sequence->map_mode === 'concat') {
							$sequence->elements .= $el;
						} else if ($sequence->map_mode === 'insert_first') {
							array_unshift($sequence->elements,$el);
						} else { // insert_last
							$sequence->elements[] = $el;
						}
					}
					
				} else {
					if ($valid) {
						foreach ($sequence->callbacks as $c) {
							$res = call_user_func($c,$el);
							if ($res === false) {
								$skip = true;
								break;
							}
						}
					}
				}

				if ($skip) {
					break;
				}

			}
			
		}
		
	}

}
