<?php
namespace peer\sql;

use util\Bucket;

/**
 * This file is part of the MAP-Framework.
 *
 * @author    Michael Piontkowski <mail@mpiontkowski.de>
 * @copyright Copyright 2016 Michael Piontkowski
 * @license   https://raw.githubusercontent.com/map-framework/map/master/LICENSE.txt Apache License 2.0
 */
interface SQLServerDAO {

	/**
	 * Connect to SQL-Server.
	 *
	 * @throws ConnectionException
	 */
	public function __construct(Bucket $config, string $accessPoint);

	/**
	 * Close connection.
	 */
	public function __destruct();

	/**
	 * Execute query and return Result-Bucket.
	 */
	public function query(Query $query):Bucket;

	/**
	 * Begin Transaction.
	 */
	public function beginTransaction();

	/**
	 * Commit all executed queries since begin of transaction.
	 */
	public function commit();

	/**
	 * Rollback all executed queries since begin of transaction.
	 */
	public function rollback();

}
