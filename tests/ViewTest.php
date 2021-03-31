<?php namespace Tests\MVC;

use Framework\MVC\App;
use Framework\MVC\View;
use PHPUnit\Framework\TestCase;

/**
 * Class ViewTest.
 *
 * @runTestsInSeparateProcesses
 */
class ViewTest extends TestCase
{
	protected View $view;
	protected string $basePath = __DIR__ . '/Views/';

	protected function setUp() : void
	{
		$this->view = new View($this->basePath);
	}

	public function testRender()
	{
		$this->assertEquals(
			"<h1>Block</h1>\n",
			$this->view->render('include/block')
		);
		$this->assertEquals(
			"<div>xxx</div>\n",
			$this->view->render('layout', ['contents' => 'xxx'])
		);
	}

	public function testRenderNamespacedView()
	{
		App::autoloader()->setNamespace('Tests\MVC', __DIR__);
		$this->assertEquals(
			"<h1>Block</h1>\n",
			$this->view->render('\Tests\MVC\Views\include/block')
		);
		$this->assertEquals(
			"<div>ns</div>\n",
			$this->view->render('\Tests\MVC\Views\layout', ['contents' => 'ns'])
		);
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Namespaced view path does not match a file: \\Foo\\bar');
		$this->view->render('\\Foo\\bar');
	}

	public function testFileNotFound()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage("View path does not match a file: {$this->basePath}foo/bar");
		$this->view->render('foo/bar');
	}

	public function testFileOutOfBasePath()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(
			'View path out of base path directory: ' . __FILE__
		);
		$this->view->render('../ViewTest');
	}

	public function testBasePathIsNotADirectory()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(
			'View base path is not a valid directory: ' . __FILE__
		);
		$this->view->setBasePath(__FILE__);
	}
}
