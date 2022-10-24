<?php

namespace Model;

use Model\Readout;


class InternalReadout extends Readout implements \Interfaces\Model {
	protected $selectList = "hdc1080.*";
	protected $tableName = "hdc1080";
	protected $tableJoin = "";
	protected $extraList = "";
	protected $selectCountField = "hdc1080.ReadoutID";
	
}