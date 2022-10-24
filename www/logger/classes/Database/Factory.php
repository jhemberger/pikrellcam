<?php

namespace Database;

/**
 * Factory
 * @author Paweł
 *
 */
class Factory {
	private function __construct() { }

	/**
	 * @var SQLiteWrapper
	 */
	private static $instance = null;

	/**
	 * Singleton instance
	 * @throws Exception
	 * @return \Database\SQLiteWrapper
	 */
	public static function getInstance() {
		if (empty(self::$instance)) {
			self::connect();
		}
		if (empty(self::$instance)) {
			throw new \Exception('Data Base object failed to initialize');
		}
		return self::$instance;
	}

	/**
	 * Establish a connection
	 */
	private static function connect() {
		self::$instance = new SQLiteWrapper ( Config::getInstance() );

	}

}