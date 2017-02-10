<?php
defined ("CORE_EXEC") or die('Access Denied');

class timer {

	private $start_time = null;
	private $elapsed_time = null;
	private $finished = false;

	function __construct($autostart = true) {
		if ($autostart) {
			$this->start();
		}
	}

	public function start() {
		$this->start_time = microtime(true);
		$this->elapsed_time = null;
		$this->finished = false;
	}

	public function reset($time = null) {
		$this->start_time = $time;
		$this->elapsed_time = null;
		$this->finished = false;
	}

	public function stop() {
		if (!$this->finished) {
			if ($this->start_time === null) return 0;
			$this->elapsed_time = microtime(true) - $this->start_time;
			$this->finished = true;
		}
		return $this->elapsed_time;
	}

	public function getStartTime() {
		return $this->start_time;
	}

	public function getTime() {
		if ($this->finished) {
			return $this->elapsed_time;
		}
		return microtime(true) - $this->start_time;
	}

}
