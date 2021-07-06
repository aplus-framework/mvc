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
