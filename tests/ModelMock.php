<?php namespace Tests\MVC;

use Framework\MVC\App;
use Framework\MVC\Model;
use Framework\Pagination\Pager;

class ModelMock extends Model
{
	public string $returnType = 'object';
	public array $allowedColumns = ['data'];
	public bool $useDatetime = true;
	public array $validationRules = [];
	public bool $protectPrimaryKey = true;

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
		return new PagerMock($page, $per_page, $this->count(), $data, App::language());
	}

	public function makePageLimitAndOffset(int $page, int $per_page = 10) : array
	{
		return parent::makePageLimitAndOffset($page, $per_page);
	}
}
