<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC\Model;

use Framework\Cache\Cache;
use Framework\MVC\App;
use Framework\MVC\Entity;
use Framework\MVC\Model;
use stdClass;

/**
 * Trait CacheTrait.
 *
 * Adds a cache layer for a Model
 *
 * @mixin Model
 */
trait CacheTrait
{
    protected function getCache() : Cache
    {
        return App::cache($this->cacheInstance ?? 'default');
    }

    protected function getCacheKey(int | string $primaryKey) : string
    {
        return 'Cache:' . static::class . '::' . $primaryKey;
    }

    protected function getCacheTTL() : int
    {
        return $this->cacheTTL ?? 60;
    }

    protected function getCacheDataNotFound() : int | string
    {
        return $this->cacheDataNotFound ?? 0;
    }

    public function find(int | string $primaryKey) : array | Entity | stdClass | null
    {
        $this->checkPrimaryKey($primaryKey);
        $data = $this->getCache()->get($this->getCacheKey($primaryKey));
        if ($data === $this->getCacheDataNotFound()) {
            return null;
        }
        if (\is_array($data)) {
            return $this->makeEntity($data);
        }
        $data = $this->getDatabaseForRead()
            ->select()
            ->from($this->getTable())
            ->whereEqual($this->primaryKey, $primaryKey)
            ->limit(1)
            ->run()
            ->fetchArray();
        if ($data === null) {
            $data = $this->getCacheDataNotFound();
        }
        $this->getCache()->set($this->getCacheKey($primaryKey), $data, $this->getCacheTTL());
        return \is_array($data) ? $this->makeEntity($data) : null;
    }

    public function create(array | Entity | stdClass $data) : false | int
    {
        $created = parent::create($data);
        if ($created === false) {
            return false;
        }
        $this->updateCachedRow($created);
        return $created;
    }

    protected function updateCachedRow(int | string $primaryKey) : void
    {
        $data = $this->getDatabaseForRead()
            ->select()
            ->from($this->getTable())
            ->whereEqual($this->primaryKey, $primaryKey)
            ->limit(1)
            ->run()
            ->fetchArray();
        if ($data === null) {
            $data = $this->getCacheDataNotFound();
        }
        $this->getCache()->set($this->getCacheKey($primaryKey), $data, $this->getCacheTTL());
    }

    public function update(int | string $primaryKey, array | Entity | stdClass $data) : false | int
    {
        $updated = parent::update($primaryKey, $data);
        if ($updated === false) {
            return false;
        }
        $this->updateCachedRow($primaryKey);
        return $updated;
    }

    public function replace(int | string $primaryKey, array | Entity | stdClass $data) : false | int
    {
        $replaced = parent::replace($primaryKey, $data);
        if ($replaced === false) {
            return false;
        }
        $this->updateCachedRow($primaryKey);
        return $replaced;
    }

    public function delete(int | string $primaryKey) : int
    {
        $deleted = parent::delete($primaryKey);
        $this->getCache()->delete($this->getCacheKey($primaryKey));
        return $deleted;
    }
}
