<?php

namespace Model;

abstract class Readout extends Base implements \Interfaces\Model {

	/*
	Those properties have to be set in child classes. Example for internal sensor
	
	protected $selectList = "hdc1080.*";
	protected $tableName = "hdc1080";
	protected $tableJoin = "";
	protected $extraList = "";
	protected $selectCountField = "hdc1080.ReadoutID";
	*/
	
	/**
	 * @var string
	 */
	protected $tableDateField = '';
	protected $registryIdField = "ReadoutID";
	
	/**
	 * Table fields definition
	 * @var array
	 */
	protected $tableFields = array(
			'ReadoutID',
			'Timestamp',
			'Temperature',
			'Humidity'
	);

	public function getAverage($days = 1) {
		$db = \Database\Factory::getInstance();
		$stamp = date('Y-m-d H:i',strtotime ( "-{$days} day" , time() ) );
		$rResult = $db->execute("SELECT AVG(Temperature) Temperature, AVG(Humidity) Humidity FROM {$this->tableName} WHERE Timestamp>='{$stamp}'");
		return $db->fetch($rResult);
	}
	
	public function getMin($days = 1) {
		$db = \Database\Factory::getInstance();
		$stamp = date('Y-m-d H:i',strtotime ( "-{$days} day" , time() ) );
		$rResult = $db->execute("SELECT MIN(Temperature) Temperature, MIN(Humidity) Humidity FROM {$this->tableName} WHERE Timestamp>='{$stamp}'");
		return $db->fetch($rResult);
	}
	
	public function getMax($days = 1) {
		$db = \Database\Factory::getInstance();
		$stamp = date('Y-m-d H:i',strtotime ( "-{$days} day" , time() ) );
		$rResult = $db->execute("SELECT MAX(Temperature) Temperature, MAX(Humidity) Humidity FROM {$this->tableName} WHERE Timestamp>='{$stamp}'");
		return $db->fetch($rResult);
	}
	
	public function getCurrent() {
		$db = \Database\Factory::getInstance();
		$rResult = $db->execute("SELECT * FROM {$this->tableName} ORDER BY `Timestamp` DESC LIMIT 1");
		return $db->fetch($rResult);
	}
	
	public function getHistory($skip = 0, $limit = 25) {
		$retVal = array();
		$cache = \Cache\Factory::getInstance();
		$sModule = get_class($this).'::getHistory';
		$sProperty = $skip.'|'.$limit;
		if (!$cache->check($sModule, $sProperty)) {
			$db = \Database\Factory::getInstance();
			$rResult = $db->execute("SELECT * FROM {$this->tableName} ORDER BY `Timestamp` DESC LIMIT {$limit} OFFSET {$skip}");
			while ($tResult = $db->fetchAssoc($rResult)) {
				array_push($retVal, $tResult);
			}
			$cache->set($sModule, $sProperty, $retVal, 300);
		}else {
			$retVal = $cache->get($sModule, $sProperty);
		}
		return $retVal;
	}
	
	public function getDayAggregate($days = 7, $orderBy = "DESC") {
		$retVal = array();
		$cache = \Cache\Factory::getInstance();
		$sModule = get_class($this).'::getDayAggregate';
		$sProperty = $days.'|'.$orderBy;
		if (!$cache->check($sModule, $sProperty)) {
			$db = \Database\Factory::getInstance();
			$rResult = $db->execute("SELECT 
						date(`Timestamp`) Timestamp, AVG(Temperature) Temperature 
						, AVG(Humidity) Humidity
						, MIN(Temperature) MinTemperature
						, MAX(Temperature) MaxTemperature
						, MIN(Humidity) MinHumidity
						, MAX(Humidity) MaxHumidity
					FROM
						{$this->tableName}
	    			WHERE 
						`Timestamp`>(SELECT DATETIME('now', '-{$days} day'))
					GROUP BY 
						date(`Timestamp`)
					ORDER BY
						date(`Timestamp`) {$orderBy}
	    			");
			while ($tResult = $db->fetchAssoc($rResult)) {
				array_push($retVal, $tResult);
			}
			$cache->set($sModule, $sProperty, $retVal, 3600);
		}else {
			$retVal = $cache->get($sModule, $sProperty);
		}
		return $retVal;
	}
	
	public function getHourAggregate($hours = 24, $orderBy = "DESC") {
		$retVal = array();
		$cache = \Cache\Factory::getInstance();
		$sModule = get_class($this).'::getHourAggregate';
		$sProperty = $hours.'|'.$orderBy;
		if (!$cache->check($sModule, $sProperty)) {
			$db = \Database\Factory::getInstance();
			$rResult = $db->execute("SELECT
					strftime('%Y-%m-%d %H:00:00', `Timestamp`) Timestamp
					, AVG(Temperature) Temperature
					, AVG(Humidity) Humidity
					, MIN(Temperature) MinTemperature
					, MAX(Temperature) MaxTemperature
					, MIN(Humidity) MinHumidity
					, MAX(Humidity) MaxHumidity
					FROM
						{$this->tableName}
					WHERE
						datetime(`Timestamp`)>(SELECT DATETIME('now', '-{$hours} hour'))
					GROUP BY
						strftime('%Y-%m-%d %H:00:00', `Timestamp`)
					ORDER BY
						datetime(`Timestamp`) {$orderBy}
					");
			while ($tResult = $db->fetchAssoc($rResult)) {
				array_push($retVal, $tResult);
			}
			$cache->set($sModule, $sProperty, $retVal, 3600);
		}else {
			$retVal = $cache->get($sModule, $sProperty);
		}
		return $retVal;
	}
}