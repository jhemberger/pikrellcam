<?php

namespace Cache;

/**
 * Class performing cache shared on a file
 *
 * @author Paweł Spychalski <pawel@spychalski.info>
 * @see http://www.spychalski.info
 * @category Common
 * @version 0.9
 */
class File{

	/**
	 * Table of entries in the cache
	 *
	 * @var array
	 */
	private $elements = array ();

	/**
	 * Name of the cache file
	 *
	 * @var string
	 */
	private $fileName = null;

	/**
	 * Default cache validity time in seconds
	 *
	 * @var int
	 */
	private $timeThreshold = 1200;

	/**
	 * Maximum cache size
	 *
	 * @var int
	 */
	private $maxSize = 2000;

	/**
	 * Current size
	 *
	 * @var int
	 */
	private $currentSize = 0;

	/**
	 * Has the cache content changed after loading / creating
	 *
	 * @var boolean
	 */
	private $changed = false;

	/**
	 * Name of the entry in $ _SESSION storing the next cleaning time
	 *
	 * @var string
	 */
	private $cacheMaintenanceTimeName = 'CacheOverFileMaintnance';

	/**
	 * Do compress the cache file
	 *
	 * @var boolean
	 */
	private $useZip = true;

	/**
	 * Singleton field
	 * @var CacheOverFile
	 */
	private static $instance;

	public static function getInstance(){
		if (empty(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	/**
	 * Destructor
	 *
	 */
	public function __destruct() {
		$this->synchronize ();
	}

	/**
	 * Construktor
	 *
	 * @param int $userID
	 */
	private function __construct() {
		$this->fileName = dirname ( __FILE__ ) . "/../../../userData/" . get_class () . '.sca';
		$this->load ();

	}

	/**
	 * Downloading cache
	 */
	private function load() {
		try {
			if (file_exists ( $this->fileName )) {
				$tCounter = 0;
				$tFile = fopen ( $this->fileName, 'r' );

				/*
				 * Set a lock on the cache file
				 */
				while ( ! flock ( $tFile, LOCK_SH ) ) {
					usleep ( 5 );
					$tCounter ++;
					if ($tCounter == 100) {
						return false;
					}
				}

				$tContent = fread ( $tFile, filesize ( $this->fileName ) );
				if ($this->useZip) {
					$tContent = gzuncompress ( $tContent );
				}
				$this->elements = unserialize ( $tContent );
				flock ( $tFile, LOCK_UN );
				fclose ( $tFile );
				$tKeys = array_keys ( $this->elements );
				foreach ( $tKeys as $tKey ) {
					$this->maintenace ( $tKey );
				}
			}
		} catch ( Exception $e ) {
			psDebug::cThrow ( null, $e, array ('display' => false, 'send' => true ) );
		}
		return true;
	}

	/**
	 * File cache synchronization
	 *
	 * @return boolean
	 */
	function synchronize() {
		try {
			$tCounter = 0;
			if ($this->changed) {
				$tFile = fopen ( $this->fileName, 'a' );
				/*
				 * Set a lock on the cache file
				 */
				while ( ! flock ( $tFile, LOCK_EX ) ) {
					usleep ( 5 );
					$tCounter ++;
					if ($tCounter == 100) {
						return false;
					}
				}

				/*
				 * If locked, save the items
				 */
				$tContent = serialize ( $this->elements );
				ftruncate ( $tFile, 0 );
				if ($this->useZip) {
					$tContent = gzcompress ( $tContent );
				}
				fputs ( $tFile, $tContent );
				flock ( $tFile, LOCK_UN );
				fclose ( $tFile );
				return true;
			}
		} catch ( Exception $e ) {
			psDebug::cThrow ( null, $e, array ('display' => false, 'send' => true ) );
			return false;
		}
		return true;
	}

	/**
	 * Cleansing the selected module
	 *
	 * @param string $module
	 * @return boolean
	 */
	private function maintenace($module) {
		if (! isset ( $this->elements [$module] ))
		return false;
		if (! isset ( $_SESSION [$this->cacheMaintenanceTimeName] [$module] )) {
			$_SESSION [$this->cacheMaintenanceTimeName] [$module] = time ();
		}

		// Check if cleaning is done
		if (time () < $_SESSION [$this->cacheMaintenanceTimeName] [$module])
		return false;
			
		// Set the next cleaning time
		$_SESSION [$this->cacheMaintenanceTimeName] [$module] = time () + $this->timeThreshold;

		// Download all keys in the module
		$keys = array_keys ( $this->elements [$module] );

		// Make a loop of keys
		foreach ( $keys as $value ) {
			// Clean expired keys
			if (time () > $this->elements [$module] [$value]->getTime ()) {
				unset ( $this->elements [$module] [$value] );
				$this->changed = true;
			}
		}
		return true;
	}

	/**
	 * Checking if there is an entry in the cache
	 *
	 * @param string $module
	 * @param string $property
	 * @return boolean
	 */
	function check($module, $property) {
		if (isset ( $this->elements [$module] [$property] )) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Taking value from cache
	 *
	 * @param string $module
	 * @param string $property
	 * @return mixed
	 */
	function get($module, $property) {
		if (isset ( $this->elements [$module] [$property] )) {
			$tValue = $this->elements [$module] [$property]->getValue ();
			return $tValue;
		} else {
			return NULL;
		}
	}

	/**
	 * Clearing a specific cache entry
	 *
	 * @param string $module
	 * @param string $property
	 */
	function clear($module, $property) {
		if (isset ( $this->elements [$module] [$property] )) {
			unset ( $this->elements [$module] [$property] );
			$this->changed = true;
		}
	}

	/**
	 * Cleaning a specific cache module
	 *
	 * @param string $module
	 */
	function clearModule($module) {
		if (isset ( $this->elements [$module] )) {
			unset ( $this->elements [$module] );
			$this->changed = true;
		}
	}

	/**
	 * Inserted into the cache
	 *
	 * @param string $module
	 * @param string $property
	 * @param mixed $value
	 * @param int $sessionLength
	 */
	function set($module, $property, $value, $sessionLength = null) {
		if ($sessionLength == null) {
			$sessionLength = $this->timeThreshold;
		}
		if (! isset ( $this->elements [$module] [$property] )) {
			$this->elements [$module] [$property] = new FileElement ( $value, time () + $sessionLength );
		} else {
			$this->elements [$module] [$property]->set ( $value, time () + $sessionLength);
		}

		/*
		 * Specify the time of the next cache cleaning for this module
		 */
		if (! isset ( $_SESSION [$this->cacheMaintenanceTimeName] [$module] )) {
			$_SESSION [$this->cacheMaintenanceTimeName] [$module] = time () + $sessionLength;
		}
		$this->changed = true;
	}

	/**
	 * Clearing entries depending on the class provided
	 *
	 * @param string $className
	 */
	public function clearClassCache($className) {
		$tKeys = array_keys ( $this->elements );
		foreach ( $tKeys as $tKey ) {
			if (mb_strpos ( $tKey, $className . '::' ) !== false) {
				$this->clearModule ( $tKey );
			}
		}
	}

	/**
	 * Clearing all cache entries
	 *
	 */
	private function globalMaintnance() {
		$tKeys = array_keys ( $this->elements );
		foreach ( $tKeys as $tKey ) {
			$this->maintenace ( $tKey );
		}
	}

	/**
	 * Downloading the total number of modules
	 */
	public function getCount() {
		return count($this->elements);
	}

	/**
	 * Downloading the total number of entries
	 */
	public function getTotalCount() {
		$retVal = $this->getCount();
		foreach ($this->elements as $tElement) {
			$retVal += count($tElement);
		}
		return $retVal;
	}

	/**
	 * Cleaning the entire cache
	 */
	public function clearAll() {
		$this->elements = array();
	}

	public function debug() {
		\psDebug::print_r($this->elements);
	}

}