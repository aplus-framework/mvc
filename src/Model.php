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

use BadMethodCallException;
use DateTime;
use DateTimeZone;
use Exception;
use Framework\Cache\Cache;
use Framework\Database\Database;
use Framework\Database\Manipulation\Traits\Where;
use Framework\Language\Language;
use Framework\Pagination\Pager;
use Framework\Validation\Debug\ValidationCollector;
use Framework\Validation\FilesValidator;
use Framework\Validation\Validation;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\Pure;
use LogicException;
use mysqli_sql_exception;
use RuntimeException;
use stdClass;

/**
 * Class Model.
 *
 * @package mvc
 *
 * @method false|int|string createById(array|Entity|stdClass $data) Create a new row and return the id.
 * @method array|Entity|stdClass|null readById(int|string $id) Read a row by id.
 * @method false|int|string updateById(int|string $id, array|Entity|stdClass $data) Update rows by id.
 * @method false|int|string deleteById(int|string $id) Delete rows by id.
 * @method false|int|string replaceById(int|string $id, array|Entity|stdClass $data) Replace rows by id.
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
    protected string $returnType = stdClass::class;
    /**
     * Allowed columns for INSERT and UPDATE.
     *
     * @var array<int,string>
     */
    protected array $allowedFields;
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
    protected array $validationLabels;
    /**
     * Validation error messages.
     *
     * @var array<string,array<string,string>>
     */
    protected array $validationMessages;
    /**
     * Validation rules.
     *
     * @see Validation::setRules
     *
     * @var array<string,array<string>|string>
     */
    protected array $validationRules;
    /**
     * Validation Validators.
     *
     * @var array<int,string>
     */
    protected array $validationValidators = [
        Validator::class,
        FilesValidator::class,
    ];
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
    /**
     * Default pager view.
     *
     * @var string
     */
    protected string $pagerView;
    /**
     * @var string
     */
    protected string $pagerQuery;
    /**
     * @var array<string>|null
     */
    protected array | null $pagerAllowedQueries = null;
    /**
     * Pager URL.
     *
     * @var string
     */
    protected string $pagerUrl;
    protected bool $cacheActive = false;
    protected string $cacheInstance = 'default';
    protected int $cacheTtl = 60;
    protected int | string $cacheDataNotFound = 0;
    protected string $languageInstance = 'default';
    protected string $columnCase = 'camel';

    /**
     * @param string $method
     * @param array<string,mixed> $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments) : mixed
    {
        if (\str_starts_with($method, 'createBy')) {
            $method = \substr($method, 8);
            $method = $this->convertCase($method, $this->columnCase);
            return $this->createBy($method, $arguments[0]); // @phpstan-ignore-line
        }
        if (\str_starts_with($method, 'readBy')) {
            $method = \substr($method, 6);
            $method = $this->convertCase($method, $this->columnCase);
            return $this->readBy($method, $arguments[0]); // @phpstan-ignore-line
        }
        if (\str_starts_with($method, 'updateBy')) {
            $method = \substr($method, 8);
            $method = $this->convertCase($method, $this->columnCase);
            return $this->updateBy($method, $arguments[0], $arguments[1]); // @phpstan-ignore-line
        }
        if (\str_starts_with($method, 'deleteBy')) {
            $method = \substr($method, 8);
            $method = $this->convertCase($method, $this->columnCase);
            return $this->deleteBy($method, $arguments[0]); // @phpstan-ignore-line
        }
        if (\str_starts_with($method, 'replaceBy')) {
            $method = \substr($method, 9);
            $method = $this->convertCase($method, $this->columnCase);
            return $this->replaceBy($method, $arguments[0], $arguments[1]); // @phpstan-ignore-line
        }
        // @codeCoverageIgnoreStart
        if (\str_starts_with($method, 'findBy')) {
            $method = \substr($method, 6);
            $method = $this->convertCase($method, $this->columnCase);
            return $this->findBy($method, $arguments[0]); // @phpstan-ignore-line
        }
        // @codeCoverageIgnoreEnd
        $class = static::class;
        if (\method_exists($this, $method)) {
            throw new BadMethodCallException(
                "Method not allowed: {$class}::{$method}"
            );
        }
        throw new BadMethodCallException("Method not found: {$class}::{$method}");
    }

    /**
     * Convert a value to specific case.
     *
     * @param string $value
     * @param string $case camel, pascal or snake
     *
     * @return string The converted value
     */
    protected function convertCase(string $value, string $case) : string
    {
        if ($case === 'camel' || $case === 'pascal') {
            $value = \preg_replace('/([a-z])([A-Z])/', '\\1 \\2', $value);
            $value = \preg_replace('@[^a-zA-Z0-9\-_ ]+@', '', $value);
            $value = \str_replace(['-', '_'], ' ', $value);
            $value = \str_replace(' ', '', \ucwords(\strtolower($value)));
            $value = \strtolower($value[0]) . \substr($value, 1);
            return $case === 'camel' ? \lcfirst($value) : \ucfirst($value);
        }
        if ($case === 'snake') {
            $value = \preg_replace('/([a-z])([A-Z])/', '\\1_\\2', $value);
            return \strtolower($value);
        }
        throw new InvalidArgumentException('Invalid case: ' . $case);
    }

    #[Pure]
    protected function getConnectionRead() : string
    {
        return $this->connectionRead;
    }

    #[Pure]
    protected function getConnectionWrite() : string
    {
        return $this->connectionWrite;
    }

    protected function getTable() : string
    {
        return $this->table ??= $this->makeTableName();
    }

    protected function makeTableName() : string
    {
        $name = static::class;
        $pos = \strrpos($name, '\\');
        if ($pos) {
            $name = \substr($name, $pos + 1);
        }
        if (\str_ends_with($name, 'Model')) {
            $name = \substr($name, 0, -5);
        }
        return $name;
    }

    #[Pure]
    protected function getPrimaryKey() : string
    {
        return $this->primaryKey;
    }

    #[Pure]
    protected function isProtectPrimaryKey() : bool
    {
        return $this->protectPrimaryKey;
    }

    #[Pure]
    protected function getReturnType() : string
    {
        return $this->returnType;
    }

    /**
     * @return array<int,string>
     */
    protected function getAllowedFields() : array
    {
        if (empty($this->allowedFields)) {
            throw new LogicException(
                'Allowed fields not defined for database writes'
            );
        }
        return $this->allowedFields;
    }

    #[Pure]
    protected function isAutoTimestamps() : bool
    {
        return $this->autoTimestamps;
    }

    #[Pure]
    protected function getFieldCreated() : string
    {
        return $this->fieldCreated;
    }

    #[Pure]
    protected function getFieldUpdated() : string
    {
        return $this->fieldUpdated;
    }

    #[Pure]
    protected function getTimestampFormat() : string
    {
        return $this->timestampFormat;
    }

    protected function getLanguageInstance() : string
    {
        return $this->languageInstance;
    }

    protected function getLanguage() : Language
    {
        return App::language($this->getLanguageInstance());
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
     * @template T
     *
     * @param array<string,T> $data
     *
     * @return array<string,T>
     */
    protected function filterAllowedFields(array $data) : array
    {
        $fields = \array_intersect_key($data, \array_flip($this->getAllowedFields()));
        if ($this->isProtectPrimaryKey() !== false
            && \array_key_exists($this->getPrimaryKey(), $fields)
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
    protected function getDatabaseToRead() : Database
    {
        return App::database($this->getConnectionRead());
    }

    /**
     * @see Model::$connectionWrite
     *
     * @return Database
     */
    protected function getDatabaseToWrite() : Database
    {
        return App::database($this->getConnectionWrite());
    }

    /**
     * A basic function to count rows in the table.
     *
     * @param array<array<mixed>> $where Array in this format: `[['id', '=', 25]]`
     *
     * @see Where
     *
     * @return int
     */
    public function count(array $where = []) : int
    {
        $select = $this->getDatabaseToRead()
            ->select()
            ->expressions([
                'count' => static function () : string {
                    return 'COUNT(*)';
                },
            ])
            ->from($this->getTable());
        foreach ($where as $args) {
            $select->where(...$args);
        }
        return $select->run()->fetch()->count; // @phpstan-ignore-line
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
        $page = $this->sanitizePageNumber($page);
        $perPage = $this->sanitizePageNumber($perPage);
        $page = $page <= 1 ? null : $page * $perPage - $perPage;
        if ($page > \PHP_INT_MAX) {
            $page = \PHP_INT_MAX;
        }
        if ($perPage === \PHP_INT_MAX && $page !== null) {
            $page = \PHP_INT_MAX;
        }
        return [
            $perPage,
            $page,
        ];
    }

    protected function sanitizePageNumber(int $number) : int
    {
        if ($number < 0) {
            if ($number === \PHP_INT_MIN) {
                $number++;
            }
            $number *= -1;
        }
        return $number;
    }

    /**
     * A basic function to paginate all rows of the table.
     *
     * @param mixed $page The current page
     * @param mixed $perPage Items per page
     * @param array<string>|string $orderBy Order by columns
     * @param string $orderByDirection asc or desc
     * @param array<array<mixed>> $where Array in this format: `[['id', '=', 25]]`
     *
     * @see Where
     *
     * @return array<int,array<mixed>|Entity|stdClass>
     */
    public function paginate(
        mixed $page,
        mixed $perPage = 10,
        array $where = [],
        array | string $orderBy = null,
        string $orderByDirection = 'asc',
    ) : array {
        $page = Pager::sanitize($page);
        $perPage = Pager::sanitize($perPage);
        $select = $this->getDatabaseToRead()
            ->select()
            ->from($this->getTable())
            ->limit(...$this->makePageLimitAndOffset($page, $perPage));
        if ($where) {
            foreach ($where as $args) {
                $select->where(...$args);
            }
        }
        if ($orderBy !== null) {
            $orderBy = (array) $orderBy;
            $orderByDir = \strtolower($orderByDirection);
            if (!\in_array($orderByDir, [
                'asc',
                'desc',
            ])) {
                throw new InvalidArgumentException(
                    'Invalid ORDER BY direction: ' . $orderByDirection
                );
            }
            $orderByDir === 'asc'
                ? $select->orderByAsc(...$orderBy)
                : $select->orderByDesc(...$orderBy);
        }
        $data = $select->run()->fetchArrayAll();
        foreach ($data as &$row) {
            $row = $this->makeEntity($row);
        }
        unset($row);
        $this->setPager(new Pager($page, $perPage, $this->count($where)));
        return $data;
    }

    /**
     * Set the Pager.
     *
     * @param Pager $pager
     *
     * @return static
     */
    protected function setPager(Pager $pager) : static
    {
        $pager->setLanguage($this->getLanguage());
        $temp = $this->getPagerQuery();
        if (isset($temp)) {
            $pager->setQuery($temp);
        }
        $temp = $this->getPagerUrl();
        if (isset($temp)) {
            $pager->setUrl($temp);
        }
        $pager->setAllowedQueries($this->getPagerAllowedQueries());
        $temp = $this->getPagerView();
        if (isset($temp)) {
            $pager->setDefaultView($temp);
        }
        $this->pager = $pager;
        return $this;
    }

    /**
     * Get the custom URL to be used in the Pager.
     *
     * @return string|null
     */
    protected function getPagerUrl() : ?string
    {
        return $this->pagerUrl ?? null;
    }

    /**
     * Get the custom view to be used in the Pager.
     *
     * @return string|null
     */
    protected function getPagerView() : ?string
    {
        return $this->pagerView ?? null;
    }

    /**
     * Get the custom query to be used in the Pager.
     *
     * @return string|null
     */
    protected function getPagerQuery() : ?string
    {
        return $this->pagerQuery ?? null;
    }

    /**
     * Get allowed queries to be used in the Pager.
     *
     * @return array<string>|null
     */
    protected function getPagerAllowedQueries() : ?array
    {
        return $this->pagerAllowedQueries;
    }

    /**
     * Get the Pager.
     *
     * Allowed only after calling a method that sets the Pager.
     *
     * @see Model::paginate()
     *
     * @return Pager
     */
    public function getPager() : Pager
    {
        return $this->pager;
    }

    /**
     * Read a row by column name and value.
     *
     * @param string $column
     * @param int|string $value
     *
     * @since 3.6
     *
     * @return array<string,float|int|string|null>|Entity|stdClass|null
     */
    public function readBy(
        string $column,
        int | string $value
    ) : array | Entity | stdClass | null {
        if ($this->isCacheActive()) {
            return $this->readWithCache($column, $value);
        }
        $data = $this->readRow($column, $value);
        return $data ? $this->makeEntity($data) : null;
    }

    /**
     * Find a row by column name and value.
     *
     * @param string $column
     * @param int|string $value
     *
     * @deprecated
     *
     * @codeCoverageIgnore
     *
     * @return array<string,float|int|string|null>|Entity|stdClass|null
     */
    #[Deprecated(
        reason: 'since MVC Library version 3.6, use readBy() instead',
        replacement: '%class%->readBy(%parameter0%, %parameter1%)'
    )]
    public function findBy(
        string $column,
        int | string $value
    ) : array | Entity | stdClass | null {
        \trigger_error(
            'Method ' . __METHOD__ . ' is deprecated',
            \E_USER_DEPRECATED
        );
        return $this->readBy($column, $value);
    }

    /**
     * Read a row based on Primary Key.
     *
     * @param int|string $id
     *
     * @since 3.6
     *
     * @return array<string,float|int|string|null>|Entity|stdClass|null The
     * selected row as configured on $returnType property or null if row was
     * not found
     */
    public function read(int | string $id) : array | Entity | stdClass | null
    {
        $this->checkPrimaryKey($id);
        return $this->readBy($this->getPrimaryKey(), $id);
    }

    /**
     * @param int|string $id
     *
     * @return array|Entity|float[]|int[]|null[]|stdClass|string[]|null
     *
     * @deprecated
     *
     * @codeCoverageIgnore
     */
    #[Deprecated(
        reason: 'since MVC Library version 3.6, use read() instead',
        replacement: '%class%->read(%parameter0%)'
    )]
    public function find(int | string $id) : array | Entity | stdClass | null
    {
        \trigger_error(
            'Method ' . __METHOD__ . ' is deprecated',
            \E_USER_DEPRECATED
        );
        return $this->read($id);
    }

    /**
     * @param string $column
     * @param int|string $value
     *
     * @since 3.6
     *
     * @return array<string,float|int|string|null>|null
     */
    protected function readRow(string $column, int | string $value) : array | null
    {
        return $this->getDatabaseToRead()
            ->select()
            ->from($this->getTable())
            ->whereEqual($column, $value)
            ->limit(1)
            ->run()
            ->fetchArray();
    }

    /**
     * @param string $column
     * @param int|string $value
     *
     * @return array<string,float|int|string|null>|null
     *
     * @deprecated
     *
     * @codeCoverageIgnore
     */
    #[Deprecated(
        reason: 'since MVC Library version 3.6, use readRow() instead',
        replacement: '%class%->readRow(%parameter0%, %parameter1%)'
    )]
    protected function findRow(string $column, int | string $value) : array | null
    {
        \trigger_error(
            'Method ' . __METHOD__ . ' is deprecated',
            \E_USER_DEPRECATED
        );
        return $this->readRow($column, $value);
    }

    /**
     * @param string $column
     * @param int|string $value
     *
     * @return array<string,float|int|string|null>|Entity|stdClass|null
     *
     * @deprecated
     *
     * @codeCoverageIgnore
     */
    #[Deprecated(
        reason: 'since MVC Library version 3.6, use readWithCache() instead',
        replacement: '%class%->readWithCache(%parameter0%, %parameter1%)'
    )]
    protected function findWithCache(string $column, int | string $value) : array | Entity | stdClass | null
    {
        \trigger_error(
            'Method ' . __METHOD__ . ' is deprecated',
            \E_USER_DEPRECATED
        );
        return $this->readWithCache($column, $value);
    }

    /**
     * @param string $column
     * @param int|string $value
     *
     * @since 3.6
     *
     * @return array<string,float|int|string|null>|Entity|stdClass|null
     */
    protected function readWithCache(string $column, int | string $value) : array | Entity | stdClass | null
    {
        $cacheKey = $this->getCacheKey([
            $column => $value,
        ]);
        $data = $this->getCache()->get($cacheKey);
        if ($data === $this->getCacheDataNotFound()) {
            return null;
        }
        if (\is_array($data)) {
            return $this->makeEntity($data);
        }
        $data = $this->readRow($column, $value);
        if ($data === null) {
            $data = $this->getCacheDataNotFound();
        }
        $this->getCache()->set($cacheKey, $data, $this->getCacheTtl());
        return \is_array($data) ? $this->makeEntity($data) : null;
    }

    /**
     * Find all rows with limit and offset.
     *
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return array<int,array<mixed>|Entity|stdClass>
     *
     * @deprecated
     *
     * @codeCoverageIgnore
     */
    #[Deprecated(
        reason: 'since MVC Library version 3.6, use list() instead',
        replacement: '%class%->readAll(%parameter0%, %parameter1%)'
    )]
    public function findAll(int $limit = null, int $offset = null) : array
    {
        \trigger_error(
            'Method ' . __METHOD__ . ' is deprecated',
            \E_USER_DEPRECATED
        );
        return $this->list($limit, $offset);
    }

    /**
     * List rows, optionally with limit and offset.
     *
     * @param int|null $limit
     * @param int|null $offset
     *
     * @since 3.6
     *
     * @return array<int,array<mixed>|Entity|stdClass>
     */
    public function list(int $limit = null, int $offset = null) : array
    {
        $data = $this->getDatabaseToRead()
            ->select()
            ->from($this->getTable());
        if ($limit !== null) {
            $data->limit($limit, $offset);
        }
        $data = $data->run()->fetchArrayAll();
        foreach ($data as &$row) {
            $row = $this->makeEntity($row);
        }
        unset($row);
        return $data;
    }

    /**
     * @param array<string,float|int|string|null> $data
     *
     * @return array<string,float|int|string|null>|Entity|stdClass
     */
    protected function makeEntity(array $data) : array | Entity | stdClass
    {
        $returnType = $this->getReturnType();
        if ($returnType === 'array') {
            return $data;
        }
        if ($returnType === 'object' || $returnType === stdClass::class) {
            return (object) $data;
        }
        return new $returnType($data); // @phpstan-ignore-line
    }

    /**
     * @param array<string,mixed>|Entity|stdClass $data
     *
     * @return array<string,mixed>
     */
    protected function makeArray(array | Entity | stdClass $data) : array
    {
        return $data instanceof Entity
            ? $data->toModel()
            : (array) $data;
    }

    /**
     * Used to auto set the timestamp fields.
     *
     * @throws Exception if a DateTime error occur
     *
     * @return string The timestamp in the $timestampFormat property format
     */
    protected function getTimestamp() : string
    {
        return (new DateTime('now', $this->timezone()))->format(
            $this->getTimestampFormat()
        );
    }

    /**
     * Get the timezone from database write connection config. As fallback, uses
     * the UTC timezone.
     *
     * @throws Exception if database config has a bad timezone
     *
     * @return DateTimeZone
     */
    protected function timezone() : DateTimeZone
    {
        $timezone = $this->getDatabaseToWrite()->getConfig()['timezone'] ?? '+00:00';
        return new DateTimeZone($timezone);
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
        $data = $this->makeArray($data);
        if ($this->getValidation()->validate($data) === false) {
            return false;
        }
        $data = $this->filterAllowedFields($data);
        if ($this->isAutoTimestamps()) {
            $timestamp = $this->getTimestamp();
            $data[$this->getFieldCreated()] ??= $timestamp;
            $data[$this->getFieldUpdated()] ??= $timestamp;
        }
        $database = $this->getDatabaseToWrite();
        try {
            $affectedRows = $database->insert()
                ->into($this->getTable())
                ->set($data)
                ->run();
        } catch (mysqli_sql_exception $exception) {
            $this->checkMysqliException($exception);
            return false;
        }
        $insertId = $affectedRows > 0 // $affectedRows is -1 if fail with MYSQLI_REPORT_OFF
            ? $database->insertId()
            : false;
        if ($insertId && $this->isCacheActive()) {
            $this->updateCachedRow($this->getPrimaryKey(), $insertId);
        }
        return $insertId;
    }

    /**
     * Insert a new row and return the inserted column value.
     *
     * @param string $column Column name
     * @param array<string,float|int|string|null>|Entity|stdClass $data
     *
     * @return false|int|string The value from the column data or false if
     * validation fail
     */
    public function createBy(string $column, array | Entity | stdClass $data) : false | int | string
    {
        $data = $this->makeArray($data);
        if ($this->getValidation()->validate($data) === false) {
            return false;
        }
        $data = $this->filterAllowedFields($data);
        if (!isset($data[$column])) {
            throw new LogicException('Value of column ' . $column . ' is not set');
        }
        if ($this->isAutoTimestamps()) {
            $timestamp = $this->getTimestamp();
            $data[$this->getFieldCreated()] ??= $timestamp;
            $data[$this->getFieldUpdated()] ??= $timestamp;
        }
        try {
            $this->getDatabaseToWrite()->insert()
                ->into($this->getTable())
                ->set($data)
                ->run();
        } catch (mysqli_sql_exception $exception) {
            $this->checkMysqliException($exception);
            return false;
        }
        if ($this->isCacheActive()) {
            $this->updateCachedRow($column, $data[$column]);
        }
        return $data[$column];
    }

    /**
     * @param mysqli_sql_exception $exception
     *
     * @throws mysqli_sql_exception if message is not for duplicate entry
     */
    protected function checkMysqliException(mysqli_sql_exception $exception) : void
    {
        $message = $exception->getMessage();
        if (\str_starts_with($message, 'Duplicate entry')) {
            $this->setDuplicateEntryError($message);
            return;
        }
        throw $exception;
    }

    /**
     * Set "Duplicate entry" as 'unique' error in the Validation.
     *
     * NOTE: We will get the index key name and not the column name. Usually the
     * names are the same. If table have different column and index names,
     * override this method and get the column name from the information_schema
     * table.
     *
     * @param string $message The "Duplicate entry" message from the mysqli_sql_exception
     */
    protected function setDuplicateEntryError(string $message) : void
    {
        $field = \rtrim($message, "'");
        $field = \substr($field, \strrpos($field, "'") + 1);
        if ($field === 'PRIMARY') {
            $field = $this->getPrimaryKey();
        }
        $validation = $this->getValidation();
        $validation->setError($field, 'unique');
        $validation->getDebugCollector()
            ?->setErrorInDebugData($field, $validation->getError($field));
    }

    /**
     * @param string $column
     * @param int|string $value
     */
    protected function updateCachedRow(string $column, int | string $value) : void
    {
        $data = $this->readRow($column, $value);
        if ($data === null) {
            $data = $this->getCacheDataNotFound();
        }
        $this->getCache()->set(
            $this->getCacheKey([$column => $value]),
            $data,
            $this->getCacheTtl()
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
        $id = $data[$this->getPrimaryKey()] ?? null;
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
     * @return false|int|string The number of affected rows or false if
     * validation fails
     */
    public function update(int | string $id, array | Entity | stdClass $data) : false | int | string
    {
        $this->checkPrimaryKey($id);
        return $this->updateBy($this->getPrimaryKey(), $id, $data);
    }

    /**
     * Update based on column value and return the number of affected rows.
     *
     * @param string $column
     * @param int|string $value
     * @param array<string,float|int|string|null>|Entity|stdClass $data
     *
     * @return false|int|string The number of affected rows or false if
     * validation fails
     */
    public function updateBy(
        string $column,
        int | string $value,
        array | Entity | stdClass $data
    ) : false | int | string {
        $data = $this->makeArray($data);
        $data[$column] ??= $value;
        if ($this->getValidation()->validateOnly($data) === false) {
            return false;
        }
        $data = $this->filterAllowedFields($data);
        if ($this->isAutoTimestamps()) {
            $data[$this->getFieldUpdated()] ??= $this->getTimestamp();
        }
        try {
            $affectedRows = $this->getDatabaseToWrite()
                ->update()
                ->table($this->getTable())
                ->set($data)
                ->whereEqual($column, $value)
                ->run();
        } catch (mysqli_sql_exception $exception) {
            $this->checkMysqliException($exception);
            return false;
        }
        if ($this->isCacheActive()) {
            $this->updateCachedRow($column, $value);
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
     * @return false|int|string The number of affected rows or false if
     * validation fails
     */
    public function replace(int | string $id, array | Entity | stdClass $data) : false | int | string
    {
        $this->checkPrimaryKey($id);
        return $this->replaceBy($this->getPrimaryKey(), $id, $data);
    }

    /**
     * Replace based on column value and return the number of affected rows.
     *
     * @param string $column
     * @param int|string $value
     * @param array<string,float|int|string|null>|Entity|stdClass $data
     *
     * @return false|int|string The number of affected rows or false if
     * validation fails
     */
    public function replaceBy(
        string $column,
        int | string $value,
        array | Entity | stdClass $data
    ) : false | int | string {
        $data = $this->makeArray($data);
        $data[$column] ??= $value;
        if ($this->getValidation()->validate($data) === false) {
            return false;
        }
        $data = $this->filterAllowedFields($data);
        $data[$column] = $value;
        if ($this->isAutoTimestamps()) {
            $timestamp = $this->getTimestamp();
            $data[$this->getFieldCreated()] ??= $timestamp;
            $data[$this->getFieldUpdated()] ??= $timestamp;
        }
        $affectedRows = $this->getDatabaseToWrite()
            ->replace()
            ->into($this->getTable())
            ->set($data)
            ->run();
        if ($this->isCacheActive()) {
            $this->updateCachedRow($column, $value);
        }
        return $affectedRows;
    }

    /**
     * Delete based on Primary Key.
     *
     * @param int|string $id
     *
     * @return false|int|string The number of affected rows
     */
    public function delete(int | string $id) : false | int | string
    {
        $this->checkPrimaryKey($id);
        return $this->deleteBy($this->getPrimaryKey(), $id);
    }

    /**
     * Delete based on column value.
     *
     * @param string $column
     * @param int|string $value
     *
     * @return false|int|string The number of affected rows
     */
    public function deleteBy(string $column, int | string $value) : false | int | string
    {
        $affectedRows = $this->getDatabaseToWrite()
            ->delete()
            ->from($this->getTable())
            ->whereEqual($column, $value)
            ->run();
        if ($this->isCacheActive()) {
            $this->getCache()->delete(
                $this->getCacheKey([$column => $value])
            );
        }
        return $affectedRows;
    }

    protected function getValidation() : Validation
    {
        if (isset($this->validation)) {
            return $this->validation;
        }
        $this->validation = new Validation(
            $this->getValidationValidators(),
            $this->getLanguage()
        );
        $this->validation->setRules($this->getValidationRules())
            ->setLabels($this->getValidationLabels())
            ->setMessages($this->getValidationMessages());
        if (App::isDebugging()) {
            $name = 'model ' . static::class;
            $collector = new ValidationCollector($name);
            App::debugger()->addCollector($collector, 'Validation');
            $this->validation->setDebugCollector($collector);
        }
        return $this->validation;
    }

    /**
     * @return array<string,string>
     */
    protected function getValidationLabels() : array
    {
        return $this->validationLabels ?? [];
    }

    /**
     * @return array<string,array<string,string>>
     */
    public function getValidationMessages() : array
    {
        return $this->validationMessages ?? [];
    }

    /**
     * @return array<string,array<string>|string>
     */
    protected function getValidationRules() : array
    {
        if (!isset($this->validationRules)) {
            throw new RuntimeException('Validation rules are not set');
        }
        return $this->validationRules;
    }

    /**
     * @return array<int,string>
     */
    public function getValidationValidators() : array
    {
        return $this->validationValidators;
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
    protected function isCacheActive() : bool
    {
        return $this->cacheActive;
    }

    #[Pure]
    protected function getCacheInstance() : string
    {
        return $this->cacheInstance;
    }

    #[Pure]
    protected function getCacheTtl() : int
    {
        return $this->cacheTtl;
    }

    #[Pure]
    protected function getCacheDataNotFound() : int | string
    {
        return $this->cacheDataNotFound;
    }

    protected function getCache() : Cache
    {
        return App::cache($this->getCacheInstance());
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
        $suffix = \implode(';', $suffix);
        return 'Model:' . static::class . '::' . $suffix;
    }
}
