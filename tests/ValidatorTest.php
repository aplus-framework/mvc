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

use Framework\Config\Config;
use Framework\Database\Definition\Table\TableDefinition;
use PHPUnit\Framework\TestCase;
use Tests\MVC\AppMock as App;

final class ValidatorTest extends TestCase
{
	protected function setUp() : void
	{
		(new App(new Config(__DIR__ . '/configs')));
		App::database()->dropTable()->table('Users')->ifExists()->run();
		App::database()->createTable()
			->table('Users')
			->definition(static function (TableDefinition $definition) : void {
				$definition->column('id')->int()->primaryKey();
				$definition->column('username')->varchar(255);
			})->run();
		App::database()->insert()->into('Users')->values(1, 'foo')->run();
		App::database()->insert()->into('Users')->values(2, 'bar')->run();
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testValidator() : void
	{
		$validation = App::validation();
		$validation->setRule('id', 'inDatabase:Users,id,default');
		$status = $validation->validate(['id' => 1]);
		self::assertTrue($status);
		$status = $validation->validate(['id' => 2]);
		self::assertTrue($status);
		$status = $validation->validate(['id' => 3]);
		self::assertFalse($status);
		self::assertSame(
			'The id field value does not exists.',
			$validation->getError('id')
		);
		$validation->setRule('id', 'notInDatabase:Users,id,default');
		$status = $validation->validate(['id' => 1]);
		self::assertFalse($status);
		self::assertSame(
			'The id field value already exists.',
			$validation->getError('id')
		);
	}
}
