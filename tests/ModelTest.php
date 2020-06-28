<?php namespace Tests\MVC;

use Framework\Database\Definition\Table\TableDefinition;
use Framework\MVC\App;
use PHPUnit\Framework\TestCase;

/**
 * Class ModelTest.
 *
 * @runTestsInSeparateProcesses
 */
class ModelTest extends TestCase
{
	/**
	 * @var ModelMock
	 */
	protected $model;

	protected function setUp() : void
	{
		App::setConfig('database', [
			'host' => \getenv('DB_HOST'),
			'port' => \getenv('DB_PORT'),
			'username' => \getenv('DB_USERNAME'),
			'password' => \getenv('DB_PASSWORD'),
			'schema' => \getenv('DB_SCHEMA'),
		]);
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
		$this->model->allowedColumns = null;
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
		$row = $this->model->create(new EntityMock(['data' => 'Value']));
		$this->assertEquals(3, $row->id);
		$row = $this->model->create(['data' => 'Value']);
		$this->assertEquals(4, $row->id);
		$row = $this->model->create(['data' => 'Value']);
		$this->assertEquals(5, $row->id);
	}

	public function testUpdate()
	{
		$row = $this->model->update(1, new EntityMock(['data' => 'x']));
		$this->assertEquals('x', $row->data);
		$row = $this->model->update(1, ['data' => 'x']);
		$this->assertEquals('x', $row->data);
		$this->model->useDatetime = true;
		$row = $this->model->update(1, ['data' => 'y']);
		$this->assertEquals('y', $row->data);
		$this->assertNotNull($row->updatedAt);
	}

	public function testReplace()
	{
		$row = $this->model->replace(1, new EntityMock(['data' => 'x']));
		$this->assertEquals('x', $row->data);
		$row = $this->model->replace(1, ['data' => 'x']);
		$this->assertEquals('x', $row->data);
		$row = $this->model->replace(1, ['data' => 'y']);
		$this->assertEquals('y', $row->data);
	}

	public function testSave()
	{
		$this->model->allowedColumns = ['data'];
		$row = $this->model->save(['id' => 1, 'data' => 'x']);
		$this->assertEquals(1, $row->id);
		$row = $this->model->save(['data' => 'x']);
		$this->assertEquals(3, $row->id);
		$row = $this->model->save(new EntityMock(['id' => 3, 'data' => 'x']));
		$this->assertEquals(3, $row->id);
	}

	public function testDelete()
	{
		$this->assertTrue($this->model->delete(1));
		$this->assertFalse($this->model->delete(1));
	}

	public function testCount()
	{
		$this->assertEquals(2, $this->model->count());
	}

	/**
	 * @covers \Framework\MVC\Model::paginate
	 */
	public function testPaginate()
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

	public function testValidation()
	{
		$this->model->validationRules = ['data' => 'minLength:200'];
		$row = $this->model->create(['data' => 'Value']);
		$this->assertFalse($row);
		$this->assertArrayHasKey('data', $this->model->getErrors());
		$row = $this->model->update(1, ['data' => 'Value']);
		$this->assertFalse($row);
		$this->assertArrayHasKey('data', $this->model->getErrors());
		$row = $this->model->replace(1, ['data' => 'Value']);
		$this->assertFalse($row);
		$this->assertArrayHasKey('data', $this->model->getErrors());
	}
}
