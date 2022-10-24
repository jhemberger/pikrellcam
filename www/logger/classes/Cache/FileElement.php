<?php

namespace Cache;

/**
 * Shared cache class
 *
 * @author Paweł Spychalski <pawel@spychalski.info>
 * @see http://www.spychalski.info
 * @see CacheOverFile
 * @category Common
 * @version 0.9
 */
class FileElement{
	/**
	 * Entry value
	 *
	 * @var mixed
	 */
	protected $value = null;
	
	/**
	 * Entry validity time
	 *
	 * @var int
	 */
	protected $time = null;
	
	/**
	 * User ID setting the entry
	 *
	 * @var int
	 */
	protected $userID = null;
	
	/**
	 * Has the entry changed
	 *
	 * @var boolean
	 */
	protected $changed = false;
	
	/**
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}
	
	/**
	 * @param int $time
	 */
	public function setTime($time) {
		$this->changed = true;
		$this->time = $time;
	}
	
	/**
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * @param mixed $value
	 */
	public function setValue($value) {
		$this->changed = true;
		$this->value = $value;
	}
	
	/**
	 * Constructor
	 *
	 * @param mixed $value
	 * @param int $time
	 * @param int $userID
	 * @param boolean $changed
	 */
	function __construct($value, $time, $userID = null, $changed = true) {
		
		$this->value = $value;
		$this->time = $time;
		$this->userID = $userID;
		$this->changed = $changed;
	}
	
	/**
	 * Entry setting
	 *
	 * @param mixed $value
	 * @param int $time
	 * @param int $userID
	 */
	public function set($value, $time, $userID = null) {
		if ($this->value != $value || $this->userID != $userID || $this->time != $time) {
			$this->changed = true;
		}
		$this->value = $value;
		$this->time = $time;
		$this->userID = $userID;
	}

}