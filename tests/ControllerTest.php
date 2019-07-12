<?php namespace Tests\MVC;

use Framework\MVC\Controller;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
	/**
	 * @var ControllerMock
	 */
	protected $controller;

	protected function setUp() : void
	{
		$this->controller = new ControllerMock();
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(Controller::class, $this->controller);
	}

	public function testValidate()
	{
		$rules = [
			'foo' => 'minLength:5',
		];
		$this->assertArrayHasKey('foo', $this->controller->validate($rules, []));
		$this->assertArrayHasKey('foo', $this->controller->validate($rules, ['foo' => '1234']));
		$this->assertEquals([], $this->controller->validate($rules, ['foo' => '12345']));
	}
}
