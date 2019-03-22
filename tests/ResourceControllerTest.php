<?php namespace Tests\MVC;

use Framework\MVC\ResourceController;
use PHPUnit\Framework\TestCase;

class ResourceControllerTest extends TestCase
{
	/**
	 * @var ResourceControllerMock
	 */
	protected $resourceController;

	protected function setUp()
	{
		$this->resourceController = new ResourceControllerMock();
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(ResourceController::class, $this->resourceController);
	}

	public function testArgumentsCast()
	{
		$this->assertIsInt($this->resourceController->show(25));
		$this->assertIsInt($this->resourceController->show('25'));
		$this->assertIsInt($this->resourceController->replace(25));
		$this->assertIsString($this->resourceController->replace('25'));
	}

	public function testHasResourceMethods()
	{
		$this->assertTrue(\method_exists($this->resourceController, 'respondOK'));
	}
}
