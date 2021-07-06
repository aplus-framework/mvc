<?php declare(strict_types=1);
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC;

use Framework\Database\Database;
use Framework\Pagination\Pager;
use Framework\Validation\Validation;

/**
 * Class Model.
 */
abstract class Model
{
	/**
	 * Database connection instance names.
	 *
	 * @var array|string[]
	 */
	protected array $connections = [
		'read' => 'default',
		'write' => 'default',
	];
	/**
	 * Table name.
	 */
	protected string $table;
	/**
	 * Table Primary Key.
	 */
	protected string $primaryKey = 'id';
	/**
	 * Prevents Primary Key changes on INSERT and UPDATE.
	 */
	protected bool $protectPrimaryKey = true;
	/**
	 * Fetched item return type.
	 *
	 * Array, object or the classname of an Entity instance.
	 *
	 * @see Entity
	 */
	protected string $returnType = 'object';
	/**
	 * Allowed columns for INSERT and UPDATE.
	 *
	 * @var array|string[]
	 */
	protected array $allowedColumns = [];
	/**
	 * Use datetime columns.
	 */
	protected bool $useDatetime = false;
	/**
	 * Datetime column names.
	 *
	 * ```
	 * `created_at` datetime NULL DEFAULT NULL,
	 * `updated_at` datetime NULL DEFAULT NULL
	 * ```
	 *
	 * @var array|string[]
	 */
	protected array $datetimeColumns = [
		'create' => 'createdAt',
		'update' => 'updatedAt',
	];
	/**
	 * The Model Validation instance.
	 */
	protected Validation $validation;
	/**
	 * Validation field labels.
	 *
	 * @var array|string[]
	 */
	protected array $validationLabels = [];
	/**
	 * Validation rules.
	 *
	 * @see Validation::setRules
	 *
	 * @var array|array[]|string[]
	 */
	protected array $validationRules = [];

	public function __destruct()
	{
		App::removeService('validation', $this->getModelIdentifier());
	}

	protected function getTable() : string
	{
		if (isset($this->table)) {
			return $this->table;
		}
		$class = \get_class($this);
		$pos = \strrpos($class, '\\');
		if ($pos) {
			$class = \substr($class, $pos + 1);
		}
		return $this->table = $class;
	}

	protected function checkPrimaryKey(int | string $primary_key) : void
	{
		if (empty($primary_key)) {
			throw new \InvalidArgumentException(
				'Primary Key can not be empty'
			);
		}
	}

	/**
	 * @param array|string[] $columns
	 *
	 * @return array|string[]
	 */
	protected function filterAllowedColumns(array $columns) : array
	{
		if (empty($this->allowedColumns)) {
			throw new \LogicException(
				'Allowed columns not defined for INSERT and UPDATE'
			);
		}
		$columns = \array_intersect_key($columns, \array_flip($this->allowedColumns));
		if ($this->protectPrimaryKey !== false
			&& \array_key_exists($this->primaryKey, $columns)
		) {
			throw new \LogicException(
				'Protected Primary Key column can not be SET'
			);
		}
		return $columns;
	}

	/**
	 * @see Model::$connections
	 *
	 * @return Database
	 */
	protected function getDatabaseForRead() : Database
	{
		return App::database($this->connections['read']);
	}

	/**
	 * @see Model::$connections
	 *
	 * @return Database
	 */
	protected function getDatabaseForWrite() : Database
	{
		return App::database($this->connections['write']);
	}

	/**
	 * A basic function to count all rows in the table.
	 *
	 * @return int
	 */
	public function count() : int
	{
		return $this->getDatabaseForRead()
			->select()
			->expressions([
				'count' => static function () {
					return 'COUNT(*)';
				},
			])
			->from($this->getTable())
			->run()
			->fetch()->count;
	}

	/**
	 * @param int $page
	 * @param int $per_page
	 *
	 * @see Model::paginate
	 *
	 * @return array
	 */
	protected function makePageLimitAndOffset(int $page, int $per_page = 10) : array
	{
		$page = \abs($page);
		$per_page = \abs($per_page);
		$page = $page <= 1 ? null : $page * $per_page - $per_page;
		return [
			$per_page,
			$page,
		];
	}

	/**
	 * A basic function to paginate all rows of the table.
	 *
	 * @param int $page     The current page
	 * @param int $per_page
	 *
	 * @return Pager
	 */
	public function paginate(int $page, int $per_page = 10) : Pager
	{
		$data = $this->getDatabaseForRead()
			->select()
			->from($this->getTable())
			->limit(...$this->makePageLimitAndOffset($page, $per_page))
			->run()
			->fetchArrayAll();
		foreach ($data as &$row) {
			$row = $this->makeEntity($row);
		}
		return new Pager(
			$page,
			$per_page,
			$this->count(),
			$data,
			App::language(),
			App::request()->getURL()
		);
	}

	/**
	 * Find a row based on Primary Key.
	 *
	 * @param int|string $primary_key
	 *
	 * @return array|Entity|\stdClass|string[]|null The selected row as configured
	 *                                              on $returnType or null if row
	 *                                              was not found
	 */
	public function find(int | string $primary_key) : \stdClass | Entity | array | null
	{
		$this->checkPrimaryKey($primary_key);
		$data = $this->getDatabaseForRead()
			->select()
			->from($this->getTable())
			->whereEqual($this->primaryKey, $primary_key)
			->limit(1)
			->run()
			->fetchArray();
		return $data ? $this->makeEntity($data) : null;
	}

	/**
	 * @param array|string[] $data
	 *
	 * @return array|Entity|\stdClass
	 */
	protected function makeEntity(array $data)
	{
		if ($this->returnType === 'array') {
			return $data;
		}
		if ($this->returnType === 'object') {
			return (object) $data;
		}
		return new $this->returnType($data);
	}

	/**
	 * @param array|Entity|\stdClass $data
	 *
	 * @return array
	 */
	protected function makeArray($data) : array
	{
		return $data instanceof Entity
			? $data->toArray()
			: (array) $data;
	}

	/**
	 * Convert data to array and filter allowed columns.
	 *
	 * @param array|Entity|\stdClass $data
	 *
	 * @return array The allowed columns
	 */
	protected function prepareData(array | Entity | \stdClass $data) : array
	{
		$data = $this->makeArray($data);
		return $this->filterAllowedColumns($data);
	}

	/**
	 * Used to set the datetime columns.
	 *
	 * By default it returns the datetime in UTC.
	 *
	 * Use this method to transform the datetime with a custom timezone,
	 * if necessary.
	 *
	 * @return string The datetime in the following format: Y-m-d H:i:s
	 */
	protected function getDatetime() : string
	{
		return \gmdate('Y-m-d H:i:s');
	}

	/**
	 * Insert a new row.
	 *
	 * @param array|Entity|\stdClass|string[] $data
	 *
	 * @return false|int The LAST_INSERT_ID() on success or false if validation fail
	 */
	public function create(array | Entity | \stdClass $data) : false | int
	{
		$data = $this->prepareData($data);
		if ($this->getValidation()->validate($data) === false) {
			return false;
		}
		if ($this->useDatetime === true) {
			$datetime = $this->getDatetime();
			$data[$this->datetimeColumns['create']] ??= $datetime;
			$data[$this->datetimeColumns['update']] ??= $datetime;
		}
		$database = $this->getDatabaseForWrite();
		return $database->insert()->into($this->getTable())->set($data)->run()
			? $database->insertId()
			: false;
	}

	/**
	 * Save a row. Update if the Primary Key is present, otherwise
	 * insert a new row.
	 *
	 * @param array|Entity|\stdClass $data
	 *
	 * @return false|int The number of affected rows on updates as int,
	 *                   the LAST_INSERT_ID() as int on inserts or false if
	 *                   validation fails
	 */
	public function save(array | Entity | \stdClass $data) : false | int
	{
		$data = $this->makeArray($data);
		$primary_key = $data[$this->primaryKey] ?? null;
		$data = $this->filterAllowedColumns($data);
		if ($primary_key !== null) {
			return $this->update($primary_key, $data);
		}
		return $this->create($data);
	}

	/**
	 * Update based on Primary Key and return the number of affected rows.
	 *
	 * @param int|string             $primary_key
	 * @param array|Entity|\stdClass $data
	 *
	 * @return false|int The number of affected rows as int or false if validation fails
	 */
	public function update(int | string $primary_key, array | Entity | \stdClass $data) : false | int
	{
		$this->checkPrimaryKey($primary_key);
		$data = $this->prepareData($data);
		if ($this->getValidation()->validateOnly($data) === false) {
			return false;
		}
		if ($this->useDatetime === true) {
			$data[$this->datetimeColumns['update']] ??= $this->getDatetime();
		}
		return $this->getDatabaseForWrite()
			->update()
			->table($this->getTable())
			->set($data)
			->whereEqual($this->primaryKey, $primary_key)
			->run();
	}

	/**
	 * Replace based on Primary Key and return the number of affected rows.
	 *
	 * Most used with HTTP PUT method.
	 *
	 * @param int|string             $primary_key
	 * @param array|Entity|\stdClass $data
	 *
	 * @return false|int The number of affected rows as int or false if validation fails
	 */
	public function replace(int | string $primary_key, array | Entity | \stdClass $data)
	{
		$this->checkPrimaryKey($primary_key);
		$data = $this->prepareData($data);
		$data[$this->primaryKey] = $primary_key;
		if ($this->getValidation()->validate($data) === false) {
			return false;
		}
		if ($this->useDatetime === true) {
			$datetime = $this->getDatetime();
			$data[$this->datetimeColumns['create']] ??= $datetime;
			$data[$this->datetimeColumns['update']] ??= $datetime;
		}
		return $this->getDatabaseForWrite()
			->replace()
			->into($this->getTable())
			->set($data)
			->run();
	}

	/**
	 * Delete based on Primary Key.
	 *
	 * @param int|string $primary_key
	 *
	 * @return int The number of affected rows
	 */
	public function delete(int | string $primary_key) : int
	{
		$this->checkPrimaryKey($primary_key);
		return $this->getDatabaseForWrite()
			->delete()
			->from($this->getTable())
			->whereEqual($this->primaryKey, $primary_key)
			->run();
	}

	protected function getValidation() : Validation
	{
		if (isset($this->validation)) {
			return $this->validation;
		}
		return $this->validation = App::validation($this->getModelIdentifier())
			->setLabels($this->validationLabels)
			->setRules($this->validationRules);
	}

	/**
	 * Get Validation errors.
	 *
	 * @return array|string[]
	 */
	public function getErrors() : array
	{
		return $this->getValidation()->getErrors();
	}

	protected function getModelIdentifier() : string
	{
		return 'Model:' . \spl_object_hash($this);
	}
}
