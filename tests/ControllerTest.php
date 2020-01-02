<?php namespace Tests\MVC;

use Framework\MVC\App;
use Framework\MVC\Controller;
use PHPUnit\Framework\TestCase;

/**
 * Class ControllerTest.
 *
 * @runTestsInSeparateProcesses
 */
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
		$content = '<!doctype html>
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
';
		$this->assertEquals(
			$content,
			$this->controller->renderPage(
				'\Tests\MVC\Support/pages/view',
				[
					'foo' => 1,
					'bar' => 2,
				]
			)
		);
		App::view()->setBasePath(__DIR__ . '/Support');
		$this->assertEquals(
			$content,
			$this->controller->renderPage(
				'view',
				[
					'foo' => 1,
					'bar' => 2,
				]
			)
		);
	}
}
