<?php
namespace extension;

use data\InvalidDataException;
use data\oop\AnnotationNotFoundException;
use data\oop\ClassNotFoundException;
use data\oop\ClassObject;
use data\oop\InstanceException;
use data\oop\PropertyNotFoundException;
use data\oop\PropertyObject;
use data\peer\sql\Query;
use peer\sql\ConnectionException;
use peer\sql\SQLServerDAO;
use util\Bucket;
use util\MAPException;

/**
 * This file is part of the MAP-Framework.
 *
 * @author    Michael Piontkowski <mail@mpiontkowski.de>
 * @copyright Copyright 2016 Michael Piontkowski
 * @license   https://raw.githubusercontent.com/map-framework/map/master/LICENSE.txt Apache License 2.0
 *
 * Use this abstract class to reflect a SQL table.
 * @example   Annotate your child class like this example:
 *
 *            - @table accessPoint='local' tableName='person'
 *              -> The (string) accessPoint points to the config (group: sqlAccessPoint; key: local).
 *              -> The (string) tableName is the table name of the reflected sql table.
 *
 * Create properties to reflect the columns. Expected that the column name is equal the property name.
 * @example   Annotate the properties like this example:
 *
 *            - @column type='i' notNull=TRUE
 *              -> The (string) type has matching the pattern @see Query::$PATTERN_TYPE.
 *              -> The (bool) notNull parameter indicates that this column requires a value.
 */
abstract class AbstractSQLTable {

	const FORMAT_STATEMENT_FILL_BY_COLUMN = 'SELECT %s FROM `%s` WHERE `%s` = ? LIMIT 1';

	/**
	 * @var Bucket
	 */
	protected $config;

	/**
	 * @var SQLServerDAO
	 */
	private $serverDAO;

	/**
	 * connect to server
	 *
	 * @throws AnnotationNotFoundException
	 * @throws InvalidDataException
	 * @throws ClassNotFoundException
	 * @throws InstanceException
	 * @throws MAPException
	 * @throws ConnectionException
	 */
	public function __construct(Bucket $config) {
		$this->config = $config;

		$tableClass = new ClassObject(get_class($this));
		$tableClass->assertHasAnnotation('table');

		$tableAnnotation = $tableClass->getAnnotation('table');
		$tableAnnotation->assertIsString('accessPoint');

		$accessPoint = $tableAnnotation->getParam('accessPoint');
		$this->config->assertIsArray('sqlAccessPoint', $accessPoint);

		$serverDAOClass = new ClassObject($this->config->get('sqlAccessPoint', $accessPoint)['serverDAO'] ?? null);
		$serverDAOClass->assertExists();
		$serverDAOClass->assertIsNotAbstract();
		$serverDAOClass->assertImplementsInterface(SQLServerDAO::class);

		$namespace       = $serverDAOClass->get();
		$this->serverDAO = new $namespace($this->config, $accessPoint);
	}

	/**
	 * @throws InvalidDataException
	 */
	public function getTableName():string {
		$tableAnnotation = (new ClassObject(get_class($this)))->getAnnotation('table');
		$tableAnnotation->assertIsString('tableName');
		return $tableAnnotation->getParam('tableName');
	}

	public function getColumn(string $columnName):PropertyObject {
		return new PropertyObject(new ClassObject(get_class($this)), $columnName);
	}

	/**
	 * @return PropertyObject[]
	 */
	public function getColumnList():array {
		foreach ((new ClassObject(get_class($this)))->getPropertyList() as $property) {
			if ($property->hasAnnotation('column')) {
				$columnList[] = $property;
			}
		}
		return $columnList ?? array();
	}

	public function fillBy(PropertyObject $column) {
		$column->assertExists();
		$column->assertHasAnnotation('column');
		$columnAnnotation = $column->getAnnotation('column');
		$columnAnnotation->assertIsString('type');

		foreach ($this->getColumnList() as $column) {
			$columnQueryList[] = '`'.$column->get().'`';
		}

		$query = new Query(
				sprintf(
						self::FORMAT_STATEMENT_FILL_BY_COLUMN,
						implode(', ', $columnQueryList ?? array()),
						$this->getTableName(),
						$column->get()
				)
		);
		$query->bindParam($columnAnnotation->getParam('type'), $column->getValue($this));

		$this->serverDAO->query($query);
	}

	/**
	 * @throws ClassNotFoundException
	 * @throws InstanceException
	 * @throws MAPException
	 * @return AbstractSQLTable[]
	 */
	public static function bucketToTableList(Bucket $config, Bucket $bucket, ClassObject $tableClass):array {
		$tableClass->assertExists();
		$tableClass->assertIsNotAbstract();
		$tableClass->assertIsChildOf(AbstractSQLTable::class);

		/* @var $table AbstractSQLTable */
		$namespace  = $tableClass->get();
		$table      = new $namespace($config);
		$columnList = $table->getColumnList();

		for ($i = 0; $i < $bucket->getGroupCount(); $i++) {
			$bucket->assertGroupExists($i);

			$tableClone = clone $table;
			foreach ($columnList as $column) {
				if ($column->isPublic()) {
					$column->setValue($tableClone, $bucket->get($i, $column->getName()));
				}
			}
			$resultTables[] = $tableClone;
		}
		return $resultTables ?? array();
	}

	final public function isColumn(string $columnName):bool {
		$column = $this->getColumn($columnName);
		return $column->exists() && !$column->isStatic();
	}

	/**
	 * @throws MAPException
	 * @throws PropertyNotFoundException
	 */
	final public function assertIsColumn(string $columnName) {
		$column = $this->getColumn($columnName);
		$column->assertExists();
		$column->assertIsNotStatic();
	}

}
