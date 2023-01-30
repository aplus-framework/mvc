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

use Closure;
use DateTime;
use DateTimeZone;
use Exception;
use Framework\Cache\Cache;
use Framework\Database\Database;
use Framework\Language\Language;
use Framework\Pagination\Pager;
use Framework\Validation\Debug\ValidationCollector;
use Framework\Validation\FilesValidator;
use Framework\Validation\Validation;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use LogicException;
use RuntimeException;
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
     * @var array<string>
     */
    protected array $pagerAllowedQueries;
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
     * A basic function to count all rows in the table.
     *
     * @return int
     */
    public function count() : int
    {
        $result = $this->getDatabaseToRead()
            ->select()
            ->expressions([
                'count' => static function () : string {
                    return 'COUNT(*)';
                },
            ])
            ->from($this->getTable())
            ->run()
            ->fetch();
        return $result->count; // @phpstan-ignore-line
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
     * @param int $page The current page
     * @param int $perPage Items per page
     *
     * @return array<int,array<mixed>|Entity|stdClass>
     */
    public function paginate(int $page, int $perPage = 10) : array
    {
        $data = $this->getDatabaseToRead()
            ->select()
            ->from($this->getTable())
            ->limit(...$this->makePageLimitAndOffset($page, $perPage))
            ->run()
            ->fetchArrayAll();
        foreach ($data as &$row) {
            $row = $this->makeEntity($row);
        }
        unset($row);
        $this->setPager(new Pager($page, $perPage, $this->count()));
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
        $temp = $this->getPagerAllowedQueries();
        if (isset($temp)) {
            $pager->setAllowedQueries($temp);
        }
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
        return $this->pagerAllowedQueries ?? null;
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
     * Find a row by column name and value.
     *
     * @param string $column
     * @param Closure|float|int|string|null $value
     *
     * @return array<string,float|int|string|null>|Entity|stdClass|null
     */
    public function findBy(
        string $column,
        Closure | float | int | string | null $value
    ) : array | Entity | stdClass | null {
        $data = $this->getDatabaseToRead()
            ->select()
            ->from($this->getTable())
            ->whereEqual($column, $value)
            ->limit(1)
            ->run()
            ->fetchArray();
        return $data ? $this->makeEntity($data) : null;
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
        if ($this->isCacheActive()) {
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
        return $this->getDatabaseToRead()
            ->select()
            ->from($this->getTable())
            ->whereEqual($this->getPrimaryKey(), $id)
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
            $this->getPrimaryKey() => $id,
        ]);
        $data = $this->getCache()->get($cacheKey);
        if ($data === $this->getCacheDataNotFound()) {
            return null;
        }
        if (\is_array($data)) {
            return $this->makeEntity($data);
        }
        $data = $this->findRow($id);
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
     */
    public function findAll(int $limit = null, int $offset = null) : array
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
        $affectedRows = $database->insert()
            ->into($this->getTable())
            ->set($data)
            ->run();
        $insertId = $affectedRows > 0 // $affectedRows is -1 if fail with MYSQLI_REPORT_OFF
            ? $database->insertId()
            : false;
        if ($insertId && $this->isCacheActive()) {
            $this->updateCachedRow($insertId);
        }
        return $insertId;
    }

    protected function updateCachedRow(int | string $id) : void
    {
        $data = $this->findRow($id);
        if ($data === null) {
            $data = $this->getCacheDataNotFound();
        }
        $this->getCache()->set(
            $this->getCacheKey([$this->getPrimaryKey() => $id]),
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
        $data = $this->makeArray($data);
        $data[$this->getPrimaryKey()] = $id;
        if ($this->getValidation()->validateOnly($data) === false) {
            return false;
        }
        $data = $this->filterAllowedFields($data);
        if ($this->isAutoTimestamps()) {
            $data[$this->getFieldUpdated()] ??= $this->getTimestamp();
        }
        $affectedRows = $this->getDatabaseToWrite()
            ->update()
            ->table($this->getTable())
            ->set($data)
            ->whereEqual($this->getPrimaryKey(), $id)
            ->run();
        if ($this->isCacheActive()) {
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
     * @return false|int|string The number of affected rows or false if
     * validation fails
     */
    public function replace(int | string $id, array | Entity | stdClass $data) : false | int | string
    {
        $this->checkPrimaryKey($id);
        $data = $this->makeArray($data);
        $data[$this->getPrimaryKey()] = $id;
        if ($this->getValidation()->validate($data) === false) {
            return false;
        }
        $data = $this->filterAllowedFields($data);
        $data[$this->getPrimaryKey()] = $id;
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
            $this->updateCachedRow($id);
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
        $affectedRows = $this->getDatabaseToWrite()
            ->delete()
            ->from($this->getTable())
            ->whereEqual($this->getPrimaryKey(), $id)
            ->run();
        if ($this->isCacheActive()) {
            $this->getCache()->delete(
                $this->getCacheKey([$this->getPrimaryKey() => $id])
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
        if ( ! isset($this->validationRules)) {
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
