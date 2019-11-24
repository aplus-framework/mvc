<?php namespace Tests\MVC;

use Framework\MVC\App;
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

	public function testRenderPage()
	{
		App::autoloader()->setNamespace('Tests\MVC', __DIR__);
		$this->controller->theme->setTitle('Test');
		$this->assertEquals(
			'<!doctype html>
<html lang="en">
<head>
			<title>Test</title>
</head>
<body>
Array
(
    [foo] => 1
    [bar] => 2
)
</body>
</html>
',
			$this->controller->renderPage(
				'\Tests\MVC\Support/view',
				[
				'foo' => 1,
				'bar' => 2,
			]
			)
		);
	}
}
