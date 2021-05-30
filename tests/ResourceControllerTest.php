<?php namespace Tests\MVC;

use Framework\HTTP\Response;
use Framework\MVC\ResourceController;
use PHPUnit\Framework\TestCase;

/**
 * Class ResourceControllerTest.
 *
 * @runTestsInSeparateProcesses
 */
class ResourceControllerTest extends TestCase
{
	protected ResourceControllerMock $resourceController;

	protected function setUp() : void
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

	public function testResourceMethods()
	{
		$this->assertInstanceOf(Response::class, $this->resourceController->respondOK([]));
		$this->assertInstanceOf(Response::class, $this->resourceController->respondOK());
		$this->assertInstanceOf(Response::class, $this->resourceController->respondNotModified());
		$this->assertInstanceOf(Response::class, $this->resourceController->respondNotFound());
		$this->assertInstanceOf(Response::class, $this->resourceController->respondNoContent());
		$this->assertInstanceOf(Response::class, $this->resourceController->respondForbidden());
		$this->assertInstanceOf(Response::class, $this->resourceController->respondCreated());
		$this->assertInstanceOf(Response::class, $this->resourceController->respondBadRequest());
		$this->assertInstanceOf(Response::class, $this->resourceController->respondUnauthorized());
		$this->assertInstanceOf(Response::class, $this->resourceController->respondAccepted());
	}
}
