<?php
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\MVC;

use Framework\Config\Config;
use Framework\MVC\View;
use PHPUnit\Framework\TestCase;
use Tests\MVC\AppMock as App;

/**
 * Class ViewTest.
 *
 * @runTestsInSeparateProcesses
 */
final class ViewTest extends TestCase
{
    protected View $view;
    protected string $basePath = __DIR__ . '/Views/';

    protected function setUp() : void
    {
        $this->view = new View($this->basePath);
    }

    public function testRender() : void
    {
        self::assertSame(
            "<h1>Block</h1>\n",
            $this->view->render('include/block')
        );
        self::assertSame(
            "<div>xxx</div>\n",
            $this->view->render('foo', ['contents' => 'xxx'])
        );
    }

    public function testRenderNamespacedView() : void
    {
        (new App(new Config(__DIR__ . '/configs')));
        App::autoloader()->setNamespace('Tests\MVC', __DIR__);
        self::assertSame(
            "<h1>Block</h1>\n",
            $this->view->render('\Tests\MVC\Views\include/block')
        );
        self::assertSame(
            "<div>ns</div>\n",
            $this->view->render('\Tests\MVC\Views\foo', ['contents' => 'ns'])
        );
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Namespaced view path does not match a file: \\Foo\\bar');
        $this->view->render('\\Foo\\bar');
    }

    public function testFileNotFound() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("View path does not match a file: {$this->basePath}foo/bar");
        $this->view->render('foo/bar');
    }

    public function testFileOutOfBasePath() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'View path out of base path directory: ' . __FILE__
        );
        $this->view->render('../ViewTest');
    }

    public function testBasePathIsNotADirectory() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'View base path is not a valid directory: ' . __FILE__
        );
        $this->view->setBasePath(__FILE__);
    }

    public function testEscape() : void
    {
        self::assertSame('&gt;&apos;&quot;', $this->view->escape('>\'"'));
        self::assertSame('', $this->view->escape(null));
    }

    public function testSection() : void
    {
        $this->view->startSection('foo');
        echo 'bar';
        $this->view->endSection();
        self::assertSame('bar', $this->view->renderSection('foo'));
    }

    public function testSectionNotFound() : void
    {
        self::assertSame('', $this->view->renderSection('foo'));
    }

    public function testLayout() : void
    {
        $html = <<<'EOL'
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
        self::assertSame($html, $this->view->render('home', [
            'name' => 'Natan Felles >\'"',
            'title' => 'Layout & Sections',
        ]));
    }
}
