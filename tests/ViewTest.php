<?php namespace Tests\MVC;

use Framework\MVC\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
	/**
	 * @var View
	 */
	protected $view;
	protected $basePath = __DIR__ . '/Views/';

	protected function setUp()
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
			'View base path is not a directory: ' . __FILE__
		);
		$this->view->setBasePath(__FILE__);
	}
}
