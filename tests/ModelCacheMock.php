<?php namespace Tests\MVC;

use Framework\MVC\Model;

class ModelCacheMock extends Model
{
	use Model\CacheTrait;

	protected string $table = 'ModelMock';
	protected array $allowedColumns = ['data'];
	protected bool $useDatetime = true;
	protected array $validationRules = [
		'data' => 'minLength:3',
	];
}
