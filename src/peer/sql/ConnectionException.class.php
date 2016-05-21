<?php
namespace peer\sql;

use util\MAPException;

/**
 * This file is part of the MAP-Framework.
 *
 * @author    Michael Piontkowski <mail@mpiontkowski.de>
 * @copyright Copyright 2016 Michael Piontkowski
 * @license   https://raw.githubusercontent.com/map-framework/map/master/LICENSE.txt Apache License 2.0
 */
class ConnectionException extends MAPException {

	public function __construct(string $accessPoint, string $details = null) {
		parent::__construct('Failed to connect to sql server');

		$this->setData('accessPoint', $accessPoint);
		$this->setData('details', $details);
	}

}
