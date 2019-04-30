<?php namespace Tests\MVC;

use Framework\MVC\Model;

class ModelMock extends Model
{
	public $returnType = 'object';
	public $allowedColumns = ['data'];
	public $useDatetime = false;
	public $validationRules = [];
	public $protectPrimaryKey = true;
}
