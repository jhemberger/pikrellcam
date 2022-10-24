<?php

namespace Cache;

/**
 * Dictionary cache class
 * The record is in memory, it is not stored between script calls
 */
class Variable {
	private $cache = array ();
	private $cacheCount = 0;
	private $maxCount = 100;

	private static $instance;

	public function clearAll() {
	}
	
	/**
	 * Static constructor
	 */
	public static function getInstance(){
		if (empty(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 * @param $maxCount max cache length
	 */
	private function __construct() {
	}

	/**
	 * Inserting a cache position
	 * @param $module
	 * @param $property
	 * @param $value
	 */
	function set($module, $property, $value) {
		if (! isset ( $this->cache [$module] [$property] )) {
			$this->cacheCount += 1;
		}
		// Insert cache value
		$this->cache [$module] [$property] = $value;
		return true;
	}

	/**
	 * Checking if there is a cache entry
	 * @param $module
	 * @param $property
	 * @return boolean
	 */
	function check($module, $property) {
		if (isset ( $this->cache [$module] [$property] )) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Taking a position with a cache
	 * @param $module
	 * @param $property
	 * @return wartość
	 */
	function get($module, $property) {
		if (isset ( $this->cache [$module] [$property] )) {
			$tTemp = $this->cache [$module] [$property];
			return $tTemp;
		} else {
			return null;
		}
	}

	public function clear($module, $property) {
		return true;
	}

	/**
	 * Displays the cache table
	 */
	function debug() {
		\psDebug::print_r ( $this->cache );
	}
}
