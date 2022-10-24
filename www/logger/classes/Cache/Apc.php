<?php

namespace Cache;

/**
 * Wrapper performing cache shared using APC
 * @author Paweł Spychalski 2011
 */
class Apc implements \Interfaces\Singleton  {

	/**
	 * Prefix key names
	 * @var string
	 */
	static private $sCachePrefix = 'apc';

	/**
	 * Timeout
	 * @var int
	 */
	static private $gcTimeThreshold = 30;

	/**
	 * Garbage Collector
	 * @var string
	 * access_time
	 * mtime
	 * creation_time
	 */
	private static $gcMethod = 'access_time';

	/**
	 * Singleton field
	 * @var \Cache\Apc
	 */
	private static $instance;

	/**
	 * Internal class cache
	 * @var array
	 */
	private $internalCache = Array();

	/**
	 * Singleton
	 * @return \Cache\Apc
	 */
	public static function getInstance(){
		if (empty(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	/**
	 * Setting the prefix to the key name
	 * @param string $prefix
	 */
	static public function sSetPrefix($prefix) {
		self::$sCachePrefix = $prefix;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		if (time() - $this->getGcRunTime() > self::$gcTimeThreshold) {
			$this->setGcRunTime();
			$this->garbageCollector();
		}
	}

	/**
	 * Correct prefix key names
	 * @return string
	 */
	static public function sGetPrefix() {
		return self::$sCachePrefix;
	}

	/**
	 * Default cache validity time in seconds
	 *
	 * @var int
	 */
	private $timeThreshold = 7200;

	/**
	 * Checking if there is an entry in the cache
	 * @param string $module
	 * @param string $property
	 * @return boolean
	 */
	public function check($module, $property) {
		$tValue = $this->get($module, $property);
		if ($tValue === false) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Return value from cache
	 * @param string $module
	 * @param string $property
	 * @return mixed
	 */
	public function get($module, $property) {
		$key = $this->getKey($module, $property);
		if (isset($this->internalCache[$key])) {
			$retVal = $this->internalCache[$key];
		} else {
			$retVal = apc_fetch($key);
			$this->internalCache[$key] = $retVal;
		}
		return $retVal;
	}

	/**
	 * Clearing a specific cache entry
	 *
	 * @param string $module
	 * @param string $property
	 */
	public function clear($module, $property = null) {
		apc_delete($this->getKey($module, $property));
	}

	/**
	 * Cleaning a specific cache module
	 *
	 * @param string $module
	 */
	public function clearModule($module = null) {
		$iterator = new \APCIterator('user');
		while ($iterator->current()) {
			$tKey = $iterator->key();
			if (mb_strpos ( $tKey, $module . '||' ) !== false) {
				apc_delete($tKey);
			}
			$iterator->next();
		}
	}

	/**
	 * Insert into the cache
	 *
	 * @param string $module
	 * @param string $property
	 * @param mixed $value
	 * @param int $sessionLength
	 */
	public function set($module, $property, $value, $sessionLength = null) {
		if ($sessionLength == null) {
			$sessionLength = $this->timeThreshold;
		}
		apc_store ( $this->getKey($module, $property) , $value , $sessionLength);
	}

	private function getGcRunTime() {
		$retVal = apc_fetch('CacheOverApcGcRunTime');
		if ($retVal === false) {
			$retVal = 0;
		}
		return $retVal;
	}

	private function setGcRunTime() {
		@apc_store ( 'CacheOverApcGcRunTime' , time() , 86400);
	}

	public function runGarbageCollector() {
		$this->garbageCollector();
	}

	/**
	 * Garbage collector destroying entries with a date older than the assumed period
	 */
	private function garbageCollector() {
		$iterator = new \APCIterator('user');
		while ($tKey = $iterator->current()) {
			if (time() - $tKey['ttl'] > $tKey[self::$gcMethod]) {
				apc_delete($tKey['key']);
			}
			$iterator->next();
		}
	}

	/**
	 * Clearing entries depending on the class provided
	 *
	 * @param string $className
	 */
	public function clearClassCache($className = null) {
		$iterator = new \APCIterator('user');
		while ($iterator->current()) {
			$tKey = $iterator->key();
			if (mb_strpos ( $tKey, $className . '::' ) !== false) {
				apc_delete($tKey);
			}
			$iterator->next();
		}
	}

	/**
	 * 
	 * Flush opcode cache
	 * @since 2012-08-01
	 */
	public function flushOpcode() {
		apc_clear_cache('opcode');
	}
	
	/**
	 * Cleaning the entire cache
	 */
	public function clearAll() {
		apc_clear_cache('user');
	}

	/**
	* Key statement
	* @param string $module
	* @param string $property
	* @return string
	*/
	private function getKey($module, $property) {
		return self::$sCachePrefix.'__'.$module.'||'.$property;
	}
}