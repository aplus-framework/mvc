<?php namespace Tests\MVC;

use Framework\MVC\Model;
use Framework\Validation\Validation;

class ModelMock extends Model
{
	public string $returnType = 'object';
	public array $allowedColumns = ['data'];
	public bool $useDatetime = true;
	public array $validationRules = [];
	public bool $protectPrimaryKey = true;

	public function makePageLimitAndOffset(int $page, int $per_page = 10) : array
	{
		return parent::makePageLimitAndOffset($page, $per_page);
	}

	public function getValidation() : Validation
	{
		return parent::getValidation();
	}
}
