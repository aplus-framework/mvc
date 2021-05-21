<?php namespace Tests\MVC;

use Framework\Database\Definition\Table\TableDefinition;
use Framework\MVC\App;
use Framework\MVC\Config;
use PHPUnit\Framework\TestCase;

class ModelTestCase extends TestCase
{
	protected function setUp() : void
	{
		App::init(new Config(__DIR__ . '/configs'));
		App::database()->dropTable()->ifExists()->table('ModelMock')->run();
		App::database()->createTable()->table('ModelMock')
			->definition(static function (TableDefinition $definition) {
				$definition->column('id')->int(11)->notNull()->autoIncrement()->primaryKey();
				$definition->column('data')->varchar(255);
				$definition->column('createdAt')->datetime();
				$definition->column('updatedAt')->datetime();
			})->run();
		App::database()->insert()->into('ModelMock')->set([
			'data' => 'foo',
			'createdAt' => \date('Y-m-d H:i:s'),
			'updatedAt' => \date('Y-m-d H:i:s'),
		])->run();
		App::database()->insert()->into('ModelMock')->set([
			'data' => 'bar',
			'createdAt' => \date('Y-m-d H:i:s'),
			'updatedAt' => \date('Y-m-d H:i:s'),
		])->run();
	}
}
