<?php namespace Tests\MVC;

use Framework\MVC\Controller;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
	/**
	 * @var ControllerMock
	 */
	protected $controller;

	protected function setUp()
	{
		$this->controller = new ControllerMock();
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(Controller::class, $this->controller);
	}
}
