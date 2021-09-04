<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC;

use Exception;
use Framework\Cache\Cache;
use Framework\Database\Database;
use Framework\Pagination\Pager;
use Framework\Validation\Validation;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use LogicException;
use stdClass;

/**
 * Class Model.
 *
 * @package mvc
 */
abstract class Model implements ModelInterface
{
    /**
     * Database connection instance name for read operations.
     *
     * @var string
     */
    protected string $connectionRead = 'default';
    /**
     * Database connection instance name for write operations.
     *
     * @var string
     */
    protected string $connectionWrite = 'default';
    /**
     * Table name.
     *
     * @var string
     */
    protected string $table;
    /**
     * Table Primary Key.
     *
     * @var string
     */
    protected string $primaryKey = 'id';
    /**
     * Prevents Primary Key changes on INSERT and UPDATE.
     *
     * @var bool
     */
    protected bool $protectPrimaryKey = true;
    /**
     * Fetched item return type.
     *
     * Array, object or the classname of an Entity instance.
     *
     * @see Entity
     *
     * @var string
     */
    protected string $returnType = 'stdClass';
    /**
     * Allowed columns for INSERT and UPDATE.
     *
     * @var array<int,string>
     */
    protected array $allowedFields = [];
    /**
     * Auto set timestamp fields.
     *
     * @var bool
     */
    protected bool $autoTimestamps = false;
    /**
     * The timestamp field for 'created at' time when $autoTimestamps is true.
     *
     * @var string
     */
    protected string $fieldCreated = 'createdAt';
    /**
     * The timestamp field for 'updated at' time when $autoTimestamps is true.
     *
     * @var string
     */
    protected string $fieldUpdated = 'updatedAt';
    /**
     * The timestamp format used on database write operations.
     *
     * @var string
     */
    protected string $timestampFormat = 'Y-m-d H:i:s';
    /**
     * The Model Validation instance.
     */
    protected Validation $validation;
    /**
     * Validation field labels.
     *
     * @var array<string,string>
     */
    protected array $validationLabels = [];
    /**
     * Validation rules.
     *
     * @see Validation::setRules
     *
     * @var array<string,array|string>
     */
    protected array $validationRules;
    /**
     * The Pager instance.
     *
     * Instantiated when calling the paginate method.
     *
     * @see Model::paginate
     *
     * @var Pager
     */
    protected Pager $pager;
    protected bool $cacheActive = false;
    protected string $cacheInstance = 'default';
    protected int $cacheTtl = 60;
    protected int | string $cacheDataNotFound = 0;

    public function __destruct()
    {
        App::removeService('validation', $this->getModelIdentifier());
    }

    #[Pure]
    protected function getTable() : string
    {
        if (isset($this->table)) {
            return $this->table;
        }
        $class = static::class;
        $pos = \strrpos($class, '\\');
        if ($pos) {
            $class = \substr($class, $pos + 1);
        }
        if (\str_ends_with($class, 'Model')) {
            $class = \substr($class, 0, -5);
        }
        return $this->table = $class;
    }

    /**
     * @return string[]
     */
    #[Pure]
    public function getAllowedFields() : array
    {
        return $this->allowedFields;
    }

    protected function checkPrimaryKey(int | string $id) : void
    {
        if (empty($id)) {
            throw new InvalidArgumentException(
                'Primary Key can not be empty'
            );
        }
    }

    /**
     * @param array<int,string> $data
     *
     * @return array<int,string>
     */
    protected function filterAllowedFields(array $data) : array
    {
        if (empty($this->allowedFields)) {
            throw new LogicException(
                'Allowed fields not defined for database writes'
            );
        }
        $fields = \array_intersect_key($data, \array_flip($this->allowedFields));
        if ($this->protectPrimaryKey !== false
            && \array_key_exists($this->primaryKey, $fields)
        ) {
            throw new LogicException(
                'Protected Primary Key field can not be SET'
            );
        }
        return $fields;
    }

    /**
     * @see Model::$connectionRead
     *
     * @return Database
     */
    protected function getDatabaseForRead() : Database
    {
        return App::database($this->connectionRead);
    }

    /**
     * @see Model::$connectionWrite
     *
     * @return Database
     */
    protected function getDatabaseForWrite() : Database
    {
        return App::database($this->connectionWrite);
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
                'count' => static function () : string {
                    return 'COUNT(*)';
                },
            ])
            ->from($this->getTable())
            ->run()
            ->fetch()->count;
    }

    /**
     * @param int $page
     * @param int $perPage
     *
     * @see Model::paginate
     *
     * @return array<int,int|null>
     */
    #[ArrayShape([0 => 'int', 1 => 'int|null'])]
    #[Pure]
    protected function makePageLimitAndOffset(int $page, int $perPage = 10) : array
    {
        $page = (int) \abs($page);
        $perPage = (int) \abs($perPage);
        $page = $page <= 1 ? null : $page * $perPage - $perPage;
        return [
            $perPage,
            $page,
        ];
    }

    /**
     * A basic function to paginate all rows of the table.
     *
     * @param int $page The current page
     * @param int $perPage Items per page
     *
     * @return array<int,array|Entity|stdClass>
     */
    public function paginate(int $page, int $perPage = 10) : array
    {
        $data = $this->getDatabaseForRead()
            ->select()
            ->from($this->getTable())
            ->limit(...$this->makePageLimitAndOffset($page, $perPage))
            ->run()
            ->fetchArrayAll();
        foreach ($data as &$row) {
            $row = $this->makeEntity($row);
        }
        unset($row);
        $this->pager = new Pager($page, $perPage, $this->count(), App::language());
        return $data;
    }

    /**
     * Allowed only after call the paginate method.
     *
     * @return Pager
     */
    public function getPager() : Pager
    {
        return $this->pager;
    }

    /**
     * Find a row based on Primary Key.
     *
     * @param int|string $id
     *
     * @return array<string,float|int|string|null>|Entity|stdClass|null The
     * selected row as configured on $returnType property or null if row was
     * not found
     */
    public function find(int | string $id) : array | Entity | stdClass | null
    {
        $this->checkPrimaryKey($id);
        if ($this->cacheActive) {
            return $this->findWithCache($id);
        }
        $data = $this->findRow($id);
        return $data ? $this->makeEntity($data) : null;
    }

    /**
     * @param int|string $id
     *
     * @return array<string,float|int|string|null>|null
     */
    protected function findRow(int | string $id) : array | null
    {
        return $this->getDatabaseForRead()
            ->select()
            ->from($this->getTable())
            ->whereEqual($this->primaryKey, $id)
            ->limit(1)
            ->run()
            ->fetchArray();
    }

    /**
     * @param int|string $id
     *
     * @return array<string,float|int|string|null>|Entity|stdClass|null
     */
    protected function findWithCache(int | string $id) : array | Entity | stdClass | null
    {
        $cacheKey = $this->getCacheKey([
            $this->primaryKey => $id,
        ]);
        $data = $this->getCache()->get($cacheKey);
        if ($data === $this->cacheDataNotFound) {
            return null;
        }
        if (\is_array($data)) {
            return $this->makeEntity($data);
        }
        $data = $this->findRow($id);
        if ($data === null) {
            $data = $this->cacheDataNotFound;
        }
        $this->getCache()->set($cacheKey, $data, $this->cacheTtl);
        return \is_array($data) ? $this->makeEntity($data) : null;
    }

    /**
     * @param array<string,float|int|string|null> $data
     *
     * @return array<string,float|int|string|null>|Entity|stdClass
     */
    protected function makeEntity(array $data) : array | Entity | stdClass
    {
        if ($this->returnType === 'array') {
            return $data;
        }
        if ($this->returnType === 'object' || $this->returnType === 'stdClass') {
            return (object) $data;
        }
        return new $this->returnType($data);
    }

    /**
     * @param array<string,mixed>|Entity|stdClass $data
     *
     * @return array<string,mixed>
     */
    protected function makeArray(array | Entity | stdClass $data) : array
    {
        return $data instanceof Entity
            ? $data->toArray()
            : (array) $data;
    }

    /**
     * Convert data to array and filter allowed columns.
     *
     * @param array<string,mixed>|Entity|stdClass $data
     *
     * @return array<string,mixed> The allowed columns
     */
    protected function prepareData(array | Entity | stdClass $data) : array
    {
        $data = $this->makeArray($data);
        return $this->filterAllowedFields($data);
    }

    /**
     * Used to auto set the timestamp fields.
     *
     * By default, get the timezone from database write connection config. As
     * fallback, uses the UTC timezone.
     *
     * @throws Exception if database config has a bad timezone or a DateTime
     * error occur
     *
     * @return string The timestamp in the $timestampFormat property format
     */
    protected function getTimestamp() : string
    {
        $timezone = $this->getDatabaseForWrite()->getConfig()['timezone'] ?? '+00:00';
        $timezone = new \DateTimeZone($timezone);
        $datetime = new \DateTime('now', $timezone);
        return $datetime->format($this->timestampFormat);
    }

    /**
     * Insert a new row.
     *
     * @param array<string,float|int|string|null>|Entity|stdClass $data
     *
     * @return false|int|string The LAST_INSERT_ID() on success or false if
     * validation fail
     */
    public function create(array | Entity | stdClass $data) : false | int | string
    {
        $data = $this->prepareData($data);
        if ($this->getValidation()->validate($data) === false) {
            return false;
        }
        if ($this->autoTimestamps) {
            $timestamp = $this->getTimestamp();
            $data[$this->fieldCreated] ??= $timestamp;
            $data[$this->fieldUpdated] ??= $timestamp;
        }
        if (empty($data)) {
            // TODO: Set error - payload is empty
            return false;
        }
        $database = $this->getDatabaseForWrite();
        $insertId = $database->insert()->into($this->getTable())->set($data)->run()
            ? $database->insertId()
            : false;
        if ($insertId && $this->cacheActive) {
            $this->updateCachedRow($insertId);
        }
        return $insertId;
    }

    protected function updateCachedRow(int | string $id) : void
    {
        $data = $this->findRow($id);
        if ($data === null) {
            $data = $this->cacheDataNotFound;
        }
        $this->getCache()->set(
            $this->getCacheKey([$this->primaryKey => $id]),
            $data,
            $this->cacheTtl
        );
    }

    /**
     * Save a row. Update if the Primary Key is present, otherwise
     * insert a new row.
     *
     * @param array<string,float|int|string|null>|Entity|stdClass $data
     *
     * @return false|int|string The number of affected rows on updates as int, the
     * LAST_INSERT_ID() as int on inserts or false if validation fails
     */
    public function save(array | Entity | stdClass $data) : false | int | string
    {
        $data = $this->makeArray($data);
        $id = $data[$this->primaryKey] ?? null;
        $data = $this->filterAllowedFields($data);
        if ($id !== null) {
            return $this->update($id, $data);
        }
        return $this->create($data);
    }

    /**
     * Update based on Primary Key and return the number of affected rows.
     *
     * @param int|string $id
     * @param array<string,float|int|string|null>|Entity|stdClass $data
     *
     * @return false|int The number of affected rows as int or false if
     * validation fails
     */
    public function update(int | string $id, array | Entity | stdClass $data) : false | int
    {
        $this->checkPrimaryKey($id);
        $data = $this->prepareData($data);
        if ($this->getValidation()->validateOnly($data) === false) {
            return false;
        }
        if ($this->autoTimestamps) {
            $data[$this->fieldUpdated] ??= $this->getTimestamp();
        }
        $affectedRows = $this->getDatabaseForWrite()
            ->update()
            ->table($this->getTable())
            ->set($data)
            ->whereEqual($this->primaryKey, $id)
            ->run();
        if ($this->cacheActive) {
            $this->updateCachedRow($id);
        }
        return $affectedRows;
    }

    /**
     * Replace based on Primary Key and return the number of affected rows.
     *
     * Most used with HTTP PUT method.
     *
     * @param int|string $id
     * @param array<string,float|int|string|null>|Entity|stdClass $data
     *
     * @return false|int The number of affected rows as int or false if
     * validation fails
     */
    public function replace(int | string $id, array | Entity | stdClass $data) : false | int
    {
        $this->checkPrimaryKey($id);
        $data = $this->prepareData($data);
        $data[$this->primaryKey] = $id;
        if ($this->getValidation()->validate($data) === false) {
            return false;
        }
        if ($this->autoTimestamps) {
            $timestamp = $this->getTimestamp();
            $data[$this->fieldCreated] ??= $timestamp;
            $data[$this->fieldUpdated] ??= $timestamp;
        }
        $affectedRows = $this->getDatabaseForWrite()
            ->replace()
            ->into($this->getTable())
            ->set($data)
            ->run();
        if ($this->cacheActive) {
            $this->updateCachedRow($id);
        }
        return $affectedRows;
    }

    /**
     * Delete based on Primary Key.
     *
     * @param int|string $id
     *
     * @return false|int The number of affected rows
     */
    public function delete(int | string $id) : false | int
    {
        $this->checkPrimaryKey($id);
        $affectedRows = $this->getDatabaseForWrite()
            ->delete()
            ->from($this->getTable())
            ->whereEqual($this->primaryKey, $id)
            ->run();
        if ($this->cacheActive) {
            $this->getCache()->delete(
                $this->getCacheKey([$this->primaryKey => $id])
            );
        }
        return $affectedRows;
    }

    protected function getValidation() : Validation
    {
        if ( ! isset($this->validationRules)) {
            throw new \RuntimeException('Validation rules are not set');
        }
        return $this->validation
            ?? ($this->validation = App::validation($this->getModelIdentifier())
                ->setLabels($this->validationLabels)
                ->setRules($this->validationRules));
    }

    /**
     * Get Validation errors.
     *
     * @return array<string,string>
     */
    public function getErrors() : array
    {
        return $this->getValidation()->getErrors();
    }

    #[Pure]
    protected function getModelIdentifier() : string
    {
        return 'Model:' . \spl_object_hash($this);
    }

    protected function getCache() : Cache
    {
        return App::cache($this->cacheInstance);
    }

    /**
     * @param array<string,float|int|string> $fields
     *
     * @return string
     */
    protected function getCacheKey(array $fields) : string
    {
        \ksort($fields);
        $suffix = [];
        foreach ($fields as $field => $value) {
            $suffix[] = $field . '=' . $value;
        }
        $suffix = \implode(';;', $suffix);
        return 'Cache:' . static::class . '::' . $suffix;
    }
}
