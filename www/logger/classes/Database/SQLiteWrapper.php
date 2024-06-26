<?php

namespace Database;

/**
 * SQLite data base interface class
 *
 * @author Pawel Spychalski <pawel@spychalski.info>
 * @link http://www.spychalski.info
 * @version 0.1
 * @copyright 2009 Lynx-IT Pawel Stanislaw Spychalski
 *
 */
class SQLiteWrapper {


	/**
	 * @var \SQLite3
	 */
	protected $dbHandle = null;

	/**
	 * @var Config
	 */
	protected $dbConfig;

	protected $queryCount = 0;

	/**
	 * Whether to save every query to the log
	 *
	 * @var boolean
	 */
	public $writeToFile = false;

	protected $logFile = null;

	protected function openLogFile() {
		if (empty ( $this->logFile )) {
			$this->logFile = fopen ( 'db.log', 'a' );
		}
	}

	protected function writeLog($query) {
		$this->openLogFile ();
		fputs ( $this->logFile, $query . "\n" );
	}

	protected function closeLogFile() {
		if (! empty ( $this->logFile )) {
			fclose ( $this->logFile );
		}
	}

	/**
	 * @return int
	 */
	public function getQueryCount() {
		return $this->queryCount;
	}

	/**
	 * Has there been a database connection
	 *
	 * @var boolean
	 */
	protected $connected = false;

	public function quote($string) {
		if (! $this->connected) {
			$this->connect ();
		}
		return $this->dbHandle->escapeString($string);
	}

	/**
	 * Quotation of all fields
	 * @param array/stdClass $data
	 */
	public function quoteAll(&$data) {
		if (is_array($data)) {
			foreach ($data as $tKey => $tValue) {
				if (is_array($tValue)) {
					foreach($tValue as $tKey2=>$tValue2) {
						$data[$tKey][$tKey2] = $this->quote($tValue2);
					}
				} else {
					$data[$tKey] = $this->quote($tValue);
				}
			}
		} elseif (is_object($data)) {
			foreach ($data as $tKey => $tValue) {
				$data->{$tKey} = $this->quote($tValue);
			}
		}
	}

	public function lastUsedID() {
		return $this->dbHandle->lastInsertRowID();
	}

	/**
	 * Execution of database query
	 *
	 * @param string $query
	 * @return resource
	 * @throws Exception
	 */
	public function execute($query) {
		if (! $this->connected) {
			$this->connect ();
		}
		if ($this->dbHandle == null) {
			return false;
		}
		$this->queryCount += 1;
		$tResult = $this->dbHandle->query($query);
		if (! $tResult) {
			throw new Exception ( $this->dbHandle->lastErrorMsg(), $this->dbHandle->lastErrorCode() );
		}

		/**
		 * logging queries into a file
		 */
		if ($this->writeToFile) {
			$this->writeLog ( $query );
		}
		return $tResult;
	}

	public function fetch(\SQLite3Result $result = null) {
		if ($this->dbHandle == null) {
			return false;
		}
		$aTemp =  $result->fetchArray(SQLITE3_ASSOC);
		if (!empty($aTemp)) {
			$tResult = new \stdClass();
			foreach($aTemp as $sKey => $mValue) {
				$tResult->{$sKey} = $mValue;
			}
		} else {
			$tResult = false;
		}
		unset($aTemp);
		return $tResult;
	}

	public function fetchAssoc(\SQLite3Result $result = null) {
		if ($this->dbHandle == null) {
			return false;
		}
		return $result->fetchArray(SQLITE3_ASSOC);
	}

	public function connect() {
		try {
			$this->dbHandle = new \SQLite3($this->dbConfig ['db']);
			if (empty ( $this->dbHandle )) {
				throw new Exception ('No connection');
			}
			$this->connected = true;
			$this->dbConfig ['handle'] = $this->dbHandle;
		} catch ( Exception $e ) {
			\psDebug::halt ('No database connection', $e, array ('display' => false ));
		}
	}

	/**
	 * Database builder
	 *
	 * @param Config
	 * @return boolean
	 */
	public function __construct(Config $dbConfig) {
		$this->dbConfig = $dbConfig;
		return true;
	}

	public function __destruct() {
		$this->closeLogFile ();
		$this->close();
	}

	/**
	 * Downloading the database holder
	 *
	 * @return resource
	 */
	public function getHandle() {
		if ($this->dbHandle == null) {
			return false;
		}
		return $this->dbHandle;
	}

	/**
	 * Close database connection
	 *
	 */
	public function close() {
		if (! empty ( $this->dbHandle )) {
			$this->dbHandle->close();
		}
		return true;
	}
}

class Exception extends \Exception {

}