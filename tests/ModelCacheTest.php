<?php namespace Tests\MVC;

use Framework\MVC\App;

/**
 * Class ModelCacheTest.
 *
 * @runTestsInSeparateProcesses
 */
class ModelCacheTest extends ModelTestCase
{
	protected ?ModelCacheMock $model;

	protected function setUp() : void
	{
		parent::setUp();
		$this->model = new ModelCacheMock();
	}

	protected function tearDown() : void
	{
		App::cache()->flush();
	}

	public function testFind()
	{
		$this->assertIsObject($this->model->find(1));
		$this->assertIsObject($this->model->find(1));
	}

	public function testFindNotFound()
	{
		$this->assertNull($this->model->find(3));
		$this->assertNull($this->model->find(3));
	}

	public function testCreate()
	{
		$this->assertNull($this->model->find(3));
		$this->assertEquals(3, $this->model->create([
			'data' => 'foo',
		]));
		$this->assertIsObject($this->model->find(3));
	}

	public function testCreateValidationFail()
	{
		$this->assertNull($this->model->find(3));
		$this->assertFalse($this->model->create([
			'data' => 'x',
		]));
		$this->assertNull($this->model->find(3));
	}

	public function testUpdate()
	{
		$this->assertEquals('foo', $this->model->find(1)->data);
		$this->assertEquals(1, $this->model->update(1, [
			'data' => 'bar',
		]));
		$this->assertEquals('bar', $this->model->find(1)->data);
	}

	public function testUpdateValidationFail()
	{
		$this->assertEquals('foo', $this->model->find(1)->data);
		$this->assertFalse($this->model->update(1, [
			'data' => 'x',
		]));
		$this->assertEquals('foo', $this->model->find(1)->data);
	}

	public function testReplace()
	{
		$this->assertEquals('foo', $this->model->find(1)->data);
		$this->assertEquals(2, $this->model->replace(1, [ // Deleted and inserted
			'data' => 'baz',
		]));
		$this->assertEquals('baz', $this->model->find(1)->data);
	}

	public function testReplaceValidationFail()
	{
		$this->assertEquals('foo', $this->model->find(1)->data);
		$this->assertFalse($this->model->replace(1, [
			'data' => 'x',
		]));
		$this->assertEquals('foo', $this->model->find(1)->data);
	}

	public function testDelete()
	{
		$this->assertIsObject($this->model->find(1));
		$this->assertEquals(1, $this->model->delete(1));
		$this->assertNull($this->model->find(1));
	}
}
