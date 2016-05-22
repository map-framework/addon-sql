<?php
namespace data\peer\sql;

use data\AbstractData;

/**
 * This file is part of the MAP-Framework.
 *
 * @author    Michael Piontkowski <mail@mpiontkowski.de>
 * @copyright Copyright 2016 Michael Piontkowski
 * @license   https://raw.githubusercontent.com/map-framework/map/master/LICENSE.txt Apache License 2.0
 */
class Query extends AbstractData {

	const PATTERN_TYPE = '^([iI]|[dD]|[sS]|[bB])$';

	const TYPE_INT    = 'i';
	const TYPE_DOUBLE = 'd';
	const TYPE_STRING = 's';
	const TYPE_BLOB   = 'b';

	/**
	 * @var string
	 */
	protected $query;

	/**
	 * @var mixed[]
	 */
	protected $paramList = array();

	/**
	 * @var string
	 */
	protected $paramTypeList;

	public function set(string $query):Query {
		$this->query = $query;
		return $this;
	}

	public function get():string {
		return $this->query;
	}

	/**
	 * Expected types length equal the number of params.
	 */
	public function bindParam(string $types, ...$param):Query {
		foreach ($param as $nr => $p) {
			if (isset($types[$nr])) {
				$this->paramList[]   = $p;
				$this->paramTypeList = $types[$nr];
			}
		}
		return $this;
	}

	public function getParamList():array {
		return $this->paramList;
	}

	public function getParamTypeList():array {
		return $this->paramTypeList;
	}

	public function getParamTypes():string {
		return implode('', $this->getParamTypeList());
	}

}
