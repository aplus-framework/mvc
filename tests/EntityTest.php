<?php namespace Tests\MVC;

use Framework\Date\Date;
use Framework\MVC\Entity;
use PHPUnit\Framework\TestCase;

/**
 * Class EntityTest.
 *
 * @runTestsInSeparateProcesses
 */
class EntityTest extends TestCase
{
	/**
	 * @var EntityMock
	 */
	protected $entity;

	protected function setUp() : void
	{
		$this->entity = new EntityMock([
			'id' => '10',
		]);
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(Entity::class, $this->entity);
	}

	public function testMagicSetAndGet()
	{
		$this->assertEquals(10, $this->entity->id);
		$this->entity->id = '20';
		$this->assertEquals(20, $this->entity->id);
		$this->assertEquals('', $this->entity->data);
		$this->entity->data = 25;
		$this->assertEquals('25', $this->entity->data);
	}

	public function testFromDateTime()
	{
		$this->assertNull($this->entity->datetime);
		$datetime = new Date();
		$this->entity->datetime = $datetime;
		$this->assertEquals($datetime, $this->entity->datetime);
		$this->entity->datetime = '2018-12-24 10:00:00';
		$this->assertEquals('2018-12-24 10:00:00', $this->entity->datetime->format('Y-m-d H:i:s'));
		$this->entity->datetime = null;
		$this->assertNull($this->entity->datetime);
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Value type must be string or Framework\Date\Date');
		$this->entity->datetime = [];
	}

	public function testFromJSON()
	{
		$this->assertNull($this->entity->settings);
		$settings = new class() extends \stdClass {
			public $foo = 'foo';
		};
		$this->entity->settings = $settings;
		$this->assertEquals('foo', $this->entity->settings->foo);
		$settings = [
			'foo' => 'bar',
		];
		$this->entity->settings = $settings;
		$this->assertEquals('bar', $this->entity->settings->foo);
		$this->entity->settings = '{"foo":"baz"}';
		$this->assertEquals('baz', $this->entity->settings->foo);
		$this->entity->settings = null;
		$this->assertNull($this->entity->settings);
	}

	public function testToArray()
	{
		$datetime = new Date();
		$this->entity->datetime = $datetime;
		$settings = new class() extends \stdClass {
			public $foo = 'foo';
		};
		$this->entity->settings = $settings;
		$this->assertEquals([
			'id' => 10,
			'datetime' => $datetime->format(Date::ATOM),
			'settings' => \json_encode($settings),
			'data' => '',
			'createdAt' => null,
			'updatedAt' => null,
		], $this->entity->toArray());
	}

	public function testUnknowTypeToScalar()
	{
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Property was not converted to scalar: createdAt');
		$this->entity->createdAt = new \DateTimeZone('UTC');
		$this->entity->toArray();
	}

	public function testTryGetUndefinedProperty()
	{
		$this->expectException(\OutOfBoundsException::class);
		$this->expectExceptionMessage('Property not defined: foo');
		$this->entity->foo;
	}

	public function testTrySetUndefinedProperty()
	{
		$this->expectException(\OutOfBoundsException::class);
		$this->expectExceptionMessage('Property not defined: foo');
		$this->entity->foo = 'bar';
	}

	public function testIssetAndUnset()
	{
		$this->assertTrue(isset($this->entity->id));
		unset($this->entity->id);
		$this->assertFalse(isset($this->entity->id));
	}

	public function testToString()
	{
		$this->assertEquals(\json_encode($this->entity->toArray()), (string) $this->entity);
	}

	public function testJsonSerialize()
	{
		$this->assertEquals(\json_encode($this->entity->toArray()), \json_encode($this->entity));
	}
}
