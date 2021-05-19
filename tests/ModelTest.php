<?php namespace Tests\MVC;

use Framework\Database\Definition\Table\TableDefinition;
use Framework\MVC\App;
use Framework\MVC\Config;
use Framework\Validation\Validation;
use PHPUnit\Framework\TestCase;

/**
 * Class ModelTest.
 *
 * @runTestsInSeparateProcesses
 */
class ModelTest extends TestCase
{
	protected ?ModelMock $model;

	protected function setUp() : void
	{
		App::init(new Config(__DIR__ . '/configs'));
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
		$this->model = new ModelMock();
	}

	protected function tearDown() : void
	{
		App::database()->dropTable()->ifExists()->table('ModelMock')->run();
	}

	public function testFind()
	{
		$this->assertIsObject($this->model->find(1));
		$this->model->returnType = 'array';
		$this->assertIsArray($this->model->find(1));
		$this->model->returnType = EntityMock::class;
		$this->assertInstanceOf(EntityMock::class, $this->model->find(1));
		$this->assertNull($this->model->find(100));
	}

	public function testAllowedColumnsNotDefined()
	{
		$this->model->allowedColumns = [];
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Allowed columns not defined for INSERT and UPDATE');
		$this->model->create(['data' => 'Value']);
	}

	public function testProtectedPrimaryKeyCanNotBeSet()
	{
		$this->model->allowedColumns = ['id', 'data'];
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Protected Primary Key column can not be SET');
		$this->model->create(['id' => 1, 'data' => 'x']);
	}

	public function testEmptyPrimaryKey()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Primary Key can not be empty');
		$this->model->update(0, ['data' => 'x']);
	}

	public function testCreate()
	{
		$insert_id = $this->model->create(new EntityMock(['data' => 'Value']));
		$this->assertEquals(3, $insert_id);
		$insert_id = $this->model->create(['data' => 'Value']);
		$this->assertEquals(4, $insert_id);
		$insert_id = $this->model->create(['data' => 'Value']);
		$this->assertEquals(5, $insert_id);
	}

	public function testCreateExceptionDefaultValue()
	{
		$this->expectException(\mysqli_sql_exception::class);
		$this->expectExceptionMessage("Field 'data' doesn't have a default value");
		$this->model->create([]);
	}

	public function testCreateExceptionUnknownColumn()
	{
		$this->model->allowedColumns[] = 'not-exists';
		$this->expectException(\mysqli_sql_exception::class);
		$this->expectExceptionMessage("Unknown column 'not-exists' in 'field list'");
		$this->model->create(['not-exists' => 'Value']);
	}

	public function testUpdate()
	{
		$affected_rows = $this->model->update(1, new EntityMock(['data' => 'x']));
		$this->assertEquals(1, $affected_rows);
		$affected_rows = $this->model->update(1, ['data' => 'x']);
		$this->assertEquals(0, $affected_rows); // same data
		\sleep(1); // change updatedAt value
		$affected_rows = $this->model->update(1, ['data' => 'x']);
		$this->assertEquals(1, $affected_rows);
		$affected_rows = $this->model->update(25, ['data' => 'foo']);
		$this->assertEquals(0, $affected_rows);
	}

	public function testUpdateExceptionUnknownColumn()
	{
		$this->model->allowedColumns[] = 'not-exists';
		$this->expectException(\mysqli_sql_exception::class);
		$this->expectExceptionMessage("Unknown column 'not-exists' in 'field list'");
		$this->model->update(1, ['not-exists' => 'Value']);
	}

	public function testReplace()
	{
		$affected_rows = $this->model->replace(1, new EntityMock(['data' => 'foo']));
		$this->assertEquals(2, $affected_rows); // Deleted and inserted
		$affected_rows = $this->model->replace(1, ['data' => 'bar']);
		$this->assertEquals(2, $affected_rows); // Deleted and inserted
		$affected_rows = $this->model->replace(25, ['data' => 'baz']);
		$this->assertEquals(1, $affected_rows); // Inserted
	}

	public function testReplaceExceptionUnknownColumn()
	{
		$this->model->allowedColumns[] = 'not-exists';
		$this->expectException(\mysqli_sql_exception::class);
		$this->expectExceptionMessage("Unknown column 'not-exists' in 'field list'");
		$this->model->replace(1, ['not-exists' => 'Value']);
	}

	public function testSave()
	{
		$this->model->allowedColumns = ['data'];
		$affected_rows = $this->model->save(['id' => 1, 'data' => 'x']);
		$this->assertEquals(1, $affected_rows);
		$insert_id = $this->model->save(['data' => 'x']);
		$this->assertEquals(3, $insert_id);
		$affected_rows = $this->model->save(new EntityMock(['id' => 3, 'data' => 'x']));
		$this->assertEquals(0, $affected_rows); // same data exists
		$affected_rows = $this->model->save(new EntityMock(['id' => 25, 'data' => 'foo']));
		$this->assertEquals(0, $affected_rows);
	}

	public function testDelete()
	{
		$this->assertEquals(1, $this->model->delete(1));
		$this->assertEquals(0, $this->model->delete(1));
		$this->assertEquals(0, $this->model->delete(25));
	}

	public function testCount()
	{
		$this->assertEquals(2, $this->model->count());
	}

	public function testPaginatedItems()
	{
		$this->model->returnType = 'array';
		$this->assertEquals([
			[
				'id' => 1,
				'data' => 'foo',
				'createdAt' => \date('Y-m-d H:i:s'),
				'updatedAt' => \date('Y-m-d H:i:s'),
			],
		], $this->model->paginate(-1, 1)->getItems());
		$this->assertEquals([
			[
				'id' => 1,
				'data' => 'foo',
				'createdAt' => \date('Y-m-d H:i:s'),
				'updatedAt' => \date('Y-m-d H:i:s'),
			],
		], $this->model->paginate(1, 1)->getItems());
		$this->assertEquals([
			[
				'id' => 2,
				'data' => 'bar',
				'createdAt' => \date('Y-m-d H:i:s'),
				'updatedAt' => \date('Y-m-d H:i:s'),
			],
		], $this->model->paginate(2, 1)->getItems());
		$this->assertEquals([], $this->model->paginate(3, 1)->getItems());
	}

	public function testPaginatedUrl()
	{
		$this->assertEquals(
			'http://localhost:8080/contact?page=5',
			$this->model->paginate(5)->getCurrentPageURL()
		);
		$this->assertEquals(
			'http://localhost:8080/contact?page=10',
			$this->model->paginate(10, 25)->getCurrentPageURL()
		);
	}

	public function testMakePageLimitAndOffset()
	{
		$this->assertEquals([10, null], $this->model->makePageLimitAndOffset(0));
		$this->assertEquals([10, null], $this->model->makePageLimitAndOffset(1));
		$this->assertEquals([10, 10], $this->model->makePageLimitAndOffset(2));
		$this->assertEquals([20, null], $this->model->makePageLimitAndOffset(1, 20));
		$this->assertEquals([20, 20], $this->model->makePageLimitAndOffset(2, 20));
		$this->assertEquals([20, 40], $this->model->makePageLimitAndOffset('-3', '-20'));
	}

	public function testValidation()
	{
		$this->model->validationRules = ['data' => 'minLength:200'];
		$row = $this->model->create(['data' => 'Value']);
		$this->assertFalse($row);
		$this->assertArrayHasKey('data', $this->model->getErrors());
		$row = $this->model->update(1, ['data' => 'Value']);
		$this->assertFalse($row);
		$this->assertArrayHasKey('data', $this->model->getErrors());
	}

	public function testValidationUnset()
	{
		$id = 'Model:' . \spl_object_hash($this->model);
		$this->assertNull(App::getService('validation', $id));
		$this->model->getValidation();
		$this->assertInstanceOf(Validation::class, App::getService('validation', $id));
		$this->model = null;
		$this->assertNull(App::getService('validation', $id));
	}
}
