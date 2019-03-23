<?php namespace Framework\MVC;

/**
 * Class Model.
 */
abstract class Model
{
	/**
	 * Database connection instance names.
	 *
	 * @var array
	 */
	protected $connections = [
		'read' => 'default',
		'write' => 'default',
	];
	/**
	 * Table name.
	 *
	 * @var string|null
	 */
	protected $table;
	/**
	 * Table Primary Key.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';
	/**
	 * Prevents Primary Key changes on INSERT and UPDATE.
	 *
	 * @var bool
	 */
	protected $protectPrimaryKey = true;
	/**
	 * Fetched item return type.
	 *
	 * @see Entity
	 *
	 * @var string array, object or the classname of an Entity instance
	 */
	protected $returnType = 'object';
	/**
	 * Allowed columns for INSERT and UPDATE.
	 *
	 * @var array
	 */
	protected $allowedColumns = [];
	/**
	 * @var bool
	 */
	protected $useDatetime = false;
	/**
	 * `created_at` datetime NULL DEFAULT NULL,
	 * `updated_at` datetime NULL DEFAULT NULL,.
	 *
	 * @var array
	 */
	protected $datetimeColumns = [
		'create' => 'createdAt',
		'update' => 'updatedAt',
	];

	protected function getTable() : string
	{
		if ($this->table) {
			return $this->table;
		}
		$class = \get_class($this);
		if ($pos = \strrpos($class, '\\')) {
			$class = \substr($class, $pos + 1);
		}
		return $this->table = $class;
	}

	protected function checkId($id) : void
	{
		if (empty($id)) {
			throw new \InvalidArgumentException(
				'Primary Key can not be empty'
			);
		}
	}

	protected function filterAllowedColumns(array $columns) : array
	{
		if (empty($this->allowedColumns)) {
			throw new \RuntimeException(
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

	public function count() : int
	{
		return App::database($this->connections['read'])
			->select()
			->expressions([
				'count' => function () {
					return 'COUNT(*)';
				},
			])
			->from($this->getTable())
			->run()
			->fetch()->count;
	}

	public function paginate(int $page, int $per_page = 10) : array
	{
		$page = $page === 1 ? 0 : $page * $per_page - $per_page;
		$data = App::database($this->connections['read'])->select()
			->from($this->getTable())
			->limit($per_page, $page)
			->run()
			->fetchArrayAll();
		foreach ($data as &$row) {
			$row = $this->makeEntity($row);
		}
		return $data;
	}

	/**
	 * @param int|string $id
	 *
	 * @return array|Entity|object|null
	 */
	public function find($id)
	{
		$this->checkId($id);
		$data = App::database($this->connections['read'])->select()
			->from($this->getTable())
			->whereEqual($this->primaryKey, $id)
			->limit(1)
			->run()
			->fetchArray();
		return $data ? $this->makeEntity($data) : null;
	}

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

	protected function makeDatetime() : string
	{
		static $timezone;
		if ( ! $timezone) {
			$timezone = new \DateTimeZone('UTC');
		}
		return (new \DateTime('now', $timezone))->format('Y-m-d H:i:s');
	}

	/**
	 * @param array|Entity|object $data
	 *
	 * @return array
	 */
	protected function prepareData($data) : array
	{
		$data = $data instanceof Entity
			? $data->toArray()
			: (array) $data;
		return $this->filterAllowedColumns($data);
	}

	/**
	 * @param array|Entity|object $data
	 *
	 * @return array|Entity|false|object|null
	 */
	public function create($data)
	{
		$data = $this->prepareData($data);
		if ($this->useDatetime === true) {
			$datetime = $this->makeDatetime();
			$data[$this->datetimeColumns['create']] = $data[$this->datetimeColumns['create']]
				?? $datetime;
			$data[$this->datetimeColumns['update']] = $data[$this->datetimeColumns['update']]
				?? $datetime;
		}
		$database = App::database($this->connections['write']);
		return $database->insert()->into($this->getTable())->set($data)->run()
			? $this->find($database->insertId())
			: false;
	}

	/**
	 * @param array|Entity|object $data
	 *
	 * @return array|Entity|false|object|null
	 */
	public function save($data)
	{
		$data = $this->prepareData($data);
		if (isset($data[$this->primaryKey])) {
			return $this->update($data[$this->primaryKey], $data);
		}
		return $this->create($data);
	}

	/**
	 * @param int|string          $id
	 * @param array|Entity|object $data
	 *
	 * @return array|Entity|false|object|null
	 */
	public function update($id, $data)
	{
		$this->checkId($id);
		$data = $this->prepareData($data);
		if ($this->useDatetime === true) {
			$data[$this->datetimeColumns['update']] = $data[$this->datetimeColumns['update']]
				?? $this->makeDatetime();
		}
		App::database($this->connections['write'])
			->update()
			->table($this->getTable())
			->set($data)
			->whereEqual($this->primaryKey, $id)
			->run();
		return $this->find($id);
	}

	public function replace($id, $data)
	{
		// TODO
	}

	/**
	 * @param int|string $id
	 *
	 * @return bool
	 */
	public function delete($id) : bool
	{
		$this->checkId($id);
		return App::database($this->connections['write'])
			->delete()
			->from($this->getTable())
			->whereEqual($this->primaryKey, $id)
			->run();
	}

	/**
	 * Validates the input data according to all rules.
	 *
	 * @param array $data
	 *
	 * @return array An empty array on success or array with errors
	 */
	public function validate(array $data) : array
	{
		// TODO
	}

	/**
	 * Validates rules mathing only input data fields.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function validateOnly(array $data) : array
	{
		// TODO
	}
}
