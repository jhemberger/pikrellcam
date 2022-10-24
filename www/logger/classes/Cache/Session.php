<?php

namespace Cache;

/**
 * Class performing caching based on the session mechanism
 * @package Common
 * @deprecated
 */
class Session{
	private $table;
	private $size;
	private $currentSize = 0;
	private $timeThreshold = 60;
	private $cacheName = 'cache';
	private $cacheMaintenanceTimeName = 'cacheMaintenanceTime';

	/**
	* Singleton field
	*/
	private static $instance;
	
	/**
	 * Static constructor
	 * @deprecated
	 */
	public static function getInstance(){
		if (empty(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}
	
	/**
	 * Clearing cache from expired entries
	 *
	 * @param string $module
	 * @return boolean
	 */
	private function maintenace($module) {
		if (! isset ( $_SESSION [$this->cacheName] [$module] ))
			return false;
			
		// Check if cleaning is done
		if (time () < $_SESSION [$this->cacheMaintenanceTimeName] [$module])
			return false;
			
		// Set the next cleaning time
		$_SESSION [$this->cacheMaintenanceTimeName] [$module] = time () + $this->timeThreshold;

		// Download all keys in the module
		$keys = array_keys ( $_SESSION [$this->cacheName] [$module] );

		// Make a loop of keys
		foreach ( $keys as $value ) {
			// Clean expired keys
			if (time () > $_SESSION [$this->cacheName] [$module] [$value] ['time']) {
				unset ( $_SESSION [$this->cacheName] [$module] [$value] );
			}
		}
		return true;
	}

	/**
	 * @return int
	 */
	public function getTimeThreshold() {
		return $this->timeThreshold;
	}

	/**
	 * @param int $timeThreshold
	 */
	public function setTimeThreshold($timeThreshold) {
		$this->timeThreshold = $timeThreshold;
	}

	/**
	 * Constructor
	 *
	 * @param int $size - cache size
	 * @return boolean
	 */
	private function __construct($size = 100) {
		$this->size = $size;
		return true;
	}

	/**
	 * Checking if there is a cache entry
	 *
	 * @param string $module
	 * @param string $id
	 * @return boolean
	 */
	function check($module, $id) {
		if (isset ( $_SESSION [$this->cacheName] [$module] [$id] )) {
			return true;
		} elseif (isset ( $this->table [$module] [$id] )) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Taking a position with a cache
	 *
	 * @param string $module
	 * @param string $id
	 * @return cache
	 */
	function get($module, $id) {
		if (isset ( $_SESSION [$this->cacheName] [$module] [$id] )) {
			$tValue = $_SESSION [$this->cacheName] [$module] [$id] ['value'];
			$this->maintenace ( $module );
			return $tValue;
		} elseif (isset ( $this->table [$module] [$id] )) {
			return $this->table [$module] [$id];
		} else {
			return NULL;
		}
	}

	/**
	 * Insertion into the cache
	 *
	 * @param string $module
	 * @param string $id
	 * @param string $value
	 * @param boolean $useSession
	 * @return string
	 */
	function set($module, $id, $value, $useSession = false, $expire = null) {
		if ($useSession) {
			// Save in session
			if ($expire == null)
			$expire = $this->timeThreshold;
			$_SESSION [$this->cacheName] [$module] [$id] ['value'] = $value;
			$_SESSION [$this->cacheName] [$module] [$id] ['time'] = time () + $expire;

			/*
			 * Specify the time of the next cache cleaning for this module
			 */
			if (! isset ( $_SESSION [$this->cacheMaintenanceTimeName] [$module] )) {
				$_SESSION [$this->cacheMaintenanceTimeName] [$module] = time () + $this->timeThreshold;
			}

		} else {
			// Save to ordinary table
			$this->table [$module] [$id] = $value;
		}
		$this->currentSize += 1;
		return true;
	}

	/**
	 * Cleaning the cache position
	 *
	 * @param string $module
	 * @param string $id
	 */
	function clear($module, $id = null) {
		if ($id != null) {
			unset ( $_SESSION [$this->cacheName] [$module] [$id] );
			unset ( $this->table [$module] [$id] );
		} else {
			unset ( $_SESSION [$this->cacheName] [$module] );
			unset ( $this->table [$module] );
		}
	}
}
