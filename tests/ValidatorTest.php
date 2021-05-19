<?php namespace Tests\MVC;

use Framework\Database\Definition\Table\TableDefinition;
use Framework\MVC\Config;
use PHPUnit\Framework\TestCase;
use Tests\MVC\AppMock as App;

class ValidatorTest extends TestCase
{
	protected function setUp() : void
	{
		App::init(new Config(__DIR__ . '/configs'));
		App::database()->dropTable()->table('Users')->ifExists()->run();
		App::database()->createTable()
			->table('Users')
			->definition(static function (TableDefinition $definition) {
				$definition->column('id')->int()->primaryKey();
				$definition->column('username')->varchar(255);
			})->run();
		App::database()->insert()->into('Users')->values(1, 'foo')->run();
		App::database()->insert()->into('Users')->values(2, 'bar')->run();
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testValidator()
	{
		$validation = App::validation();
		$validation->setRule('id', 'inDatabase:Users,id,default');
		$status = $validation->validate(['id' => 1]);
		$this->assertTrue($status);
		$status = $validation->validate(['id' => 2]);
		$this->assertTrue($status);
		$status = $validation->validate(['id' => 3]);
		$this->assertFalse($status);
		$this->assertEquals(
			'The id field value does not exists.',
			$validation->getError('id')
		);
		$validation->setRule('id', 'notInDatabase:Users,id,default');
		$status = $validation->validate(['id' => 1]);
		$this->assertFalse($status);
		$this->assertEquals(
			'The id field value already exists.',
			$validation->getError('id')
		);
	}
}
