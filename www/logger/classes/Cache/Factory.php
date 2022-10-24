<?php

namespace Cache;

/**
 * Controller cache
 * Static
 * @author Paweł
 *
 */
class Factory implements \Interfaces\Singleton {

	/**
	 * Cache class object
	 * @var mixed
	 */
	private static $cacheInstance;

	/**
	 * Private constructor
	 */
	private function __construct() {

	}

	/*
	 * The method that creates the cache object
	 */
	static private function create() {
		if (empty(self::$cacheInstance)) {
			$sCachingMethod = \General\Config::getInstance()->get('cacheMethod');
			if ($sCachingMethod === 'apc' && !(extension_loaded('apc') && ini_get('apc.enabled'))) {
				$sCachingMethod = 'Mem';
			}
			switch ($sCachingMethod) {
				case 'Apc':
					self::$cacheInstance = Apc::getInstance();
					break;
				case 'Memcached':
					self::$cacheInstance = Memcached::getInstance();
					break;
				default:
					self::$cacheInstance = Variable::getInstance();
					break;
			}
		}
	}

	/**
	 * Downloading a cache class object
	 * @throws Exception
	 * @return Memcached
	 */
	static public function getInstance() {
		if (empty(self::$cacheInstance)) {
			self::create();
		}
		if (empty(self::$cacheInstance)) {
			throw new \Exception('Cache object is not initialized');
		}
		return self::$cacheInstance;
	}


}