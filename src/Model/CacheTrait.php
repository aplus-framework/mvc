<?php namespace Framework\MVC\Model;

use Framework\Cache\Cache;
use Framework\MVC\App;
use Framework\MVC\Entity;

/**
 * Trait CacheTrait.
 *
 * Adds a cache layer for a Model
 *
 * @mixin \Framework\MVC\Model
 *
 * @property string $cacheInstance
 * @property int    $cacheTTL
 */
trait CacheTrait
{
	protected function getCache() : Cache
	{
		return App::cache($this->cacheInstance ?? 'default');
	}

	protected function getCacheKey(int | string $primary_key) : string
	{
		return 'Cache:' . __CLASS__ . '::' . $primary_key;
	}

	protected function getCacheTTL() : int
	{
		return $this->cacheTTL ?? 60;
	}

	public function find(int | string $primary_key) : \stdClass | Entity | array | null
	{
		$this->checkPrimaryKey($primary_key);
		$data = $this->getCache()->get($this->getCacheKey($primary_key));
		if ($data === 'not-found') {
			return null;
		}
		if (\is_array($data)) {
			return $this->makeEntity($data);
		}
		$data = $this->getDatabaseForRead()
			->select()
			->from($this->getTable())
			->whereEqual($this->primaryKey, $primary_key)
			->limit(1)
			->run()
			->fetchArray();
		if ($data === null) {
			$data = 'not-found';
		}
		$this->getCache()->set($this->getCacheKey($primary_key), $data, $this->getCacheTTL());
		return \is_array($data) ? $this->makeEntity($data) : null;
	}

	public function create(array | Entity | \stdClass $data) : false | int
	{
		$created = parent::create($data);
		if ($created === false) {
			return false;
		}
		$this->updateCachedRow($created);
		return $created;
	}

	protected function updateCachedRow(int | string $primary_key) : void
	{
		$data = $this->getDatabaseForRead()
			->select()
			->from($this->getTable())
			->whereEqual($this->primaryKey, $primary_key)
			->limit(1)
			->run()
			->fetchArray();
		$this->getCache()->set($this->getCacheKey($primary_key), $data, $this->getCacheTTL());
	}

	public function update(int | string $primary_key, array | Entity | \stdClass $data) : false | int
	{
		$updated = parent::update($primary_key, $data);
		if ($updated === false) {
			return false;
		}
		$this->updateCachedRow($primary_key);
		return $updated;
	}

	public function replace(int | string $primary_key, array | Entity | \stdClass $data)
	{
		$replaced = parent::replace($primary_key, $data);
		if ($replaced === false) {
			return false;
		}
		$this->updateCachedRow($primary_key);
		return $replaced;
	}

	public function delete(int | string $primary_key) : int
	{
		$deleted = parent::delete($primary_key);
		$this->getCache()->delete($this->getCacheKey($primary_key));
		return $deleted;
	}
}
