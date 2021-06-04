<?php namespace Tests\MVC;

use Framework\Config\Config;
use Framework\MVC\Controller;
use Framework\MVC\Model;
use PHPUnit\Framework\TestCase;
use Tests\MVC\AppMock as App;

/**
 * Class ControllerTest.
 *
 * @runTestsInSeparateProcesses
 */
class ControllerTest extends TestCase
{
	protected ControllerMock $controller;

	protected function setUp() : void
	{
		(new App(new Config(__DIR__ . '/configs')));
		$this->controller = new ControllerMock();
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(Controller::class, $this->controller);
	}

	public function testModelInstance()
	{
		$this->assertInstanceOf(Model::class, $this->controller->model);
	}

	public function testValidate()
	{
		$rules = [
			'foo' => 'minLength:5',
		];
		$this->assertArrayHasKey('foo', $this->controller->validate($rules, []));
		$this->assertEquals([
			'foo' => 'The foo field requires 5 or more characters in length.',
		], $this->controller->validate($rules, ['foo' => '1234']));
		$this->assertEquals([], $this->controller->validate($rules, ['foo' => '12345']));
		$this->assertEquals([
			'foo' => 'The Foo field requires 5 or more characters in length.',
		], $this->controller->validate($rules, [], ['foo' => 'Foo']));
	}
}
