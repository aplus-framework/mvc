<?php namespace Tests\MVC;

use Framework\MVC\App;
use Framework\MVC\Model;
use Framework\Pagination\Pager;

class ModelMock extends Model
{
	public $returnType = 'object';
	public $allowedColumns = ['data'];
	public $useDatetime = false;
	public $validationRules = [];
	public $protectPrimaryKey = true;

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
}
