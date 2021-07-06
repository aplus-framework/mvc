<?php
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\MVC;

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
