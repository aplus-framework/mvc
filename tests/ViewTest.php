<?php namespace Tests\MVC;

use Framework\MVC\Config;
use Framework\MVC\View;
use PHPUnit\Framework\TestCase;
use Tests\MVC\AppMock as App;

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
			$this->view->render('foo', ['contents' => 'xxx'])
		);
	}

	public function testRenderNamespacedView()
	{
		(new App(new Config(__DIR__ . '/configs')));
		App::autoloader()->setNamespace('Tests\MVC', __DIR__);
		$this->assertEquals(
			"<h1>Block</h1>\n",
			$this->view->render('\Tests\MVC\Views\include/block')
		);
		$this->assertEquals(
			"<div>ns</div>\n",
			$this->view->render('\Tests\MVC\Views\foo', ['contents' => 'ns'])
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

	public function testEscape()
	{
		$this->assertEquals('&gt;&apos;&quot;', $this->view->escape('>\'"'));
		$this->assertEquals('', $this->view->escape(null));
	}

	public function testSection()
	{
		$this->view->startSection('foo');
		echo 'bar';
		$this->view->endSection();
		$this->assertEquals('bar', $this->view->renderSection('foo'));
	}

	public function testSectionNotFound()
	{
		$this->assertEquals('', $this->view->renderSection('foo'));
	}

	public function testLayout()
	{
		$html = <<<EOL
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Layout & Sections</title>
</head>
<body>
	<div>CONTENTS - Natan Felles &gt;&apos;&quot;</div>
	<script>
		console.log('Oi')
	</script>
</body>
</html>

EOL;
		$this->assertEquals($html, $this->view->render('home', [
			'name' => 'Natan Felles >\'"',
			'title' => 'Layout & Sections',
		]));
	}
}
