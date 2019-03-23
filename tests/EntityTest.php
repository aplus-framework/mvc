<?php namespace Tests\MVC;

use Framework\MVC\Entity;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
	/**
	 * @var EntityMock
	 */
	protected $entity;

	protected function setUp()
	{
		$this->entity = new EntityMock([
			'id' => '10',
		]);
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(Entity::class, $this->entity);
	}
}
