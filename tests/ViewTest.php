<?php
/*
 * This file is part of Aplus Framework MVC Library.
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
    protected string $baseDir = __DIR__ . '/Views/';

    protected function setUp() : void
    {
        $this->view = new View($this->baseDir);
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
        (new App(new Config(__DIR__ . '/configs', [], '.config.php')));
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
        $this->expectExceptionMessage("View path does not match a file: {$this->baseDir}foo/bar");
        $this->view->render('foo/bar');
    }

    public function testFileOutOfBaseDir() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'View path out of base directory: ' . __FILE__
        );
        $this->view->render('../ViewTest');
    }

    public function testBasePathIsNotADirectory() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'View base dir is not a valid directory: ' . __FILE__
        );
        $this->view->setBaseDir(__FILE__);
    }

    public function testBlock() : void
    {
        $this->view->block('foo');
        echo 'bar';
        $this->view->endBlock();
        self::assertSame('bar', $this->view->renderBlock('foo'));
    }

    public function testBlockNotFound() : void
    {
        self::assertSame('', $this->view->renderBlock('foo'));
    }

    public function testLayout() : void
    {
        $html = <<<'EOL'
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <title>Layout & Blocks</title>
            </head>
            <body>
                <div>CONTENTS - Natan Felles >'"</div>
                <script>
                    console.log('Oi')
                </script>
            </body>
            </html>

            EOL;
        $this->view->setLayoutPrefix('layouts');
        self::assertSame($html, $this->view->render('home', [
            'name' => 'Natan Felles >\'"',
            'title' => 'Layout & Blocks',
        ]));
    }

    public function testLayoutWithoutPrefix() : void
    {
        $html = <<<'EOL'
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <title>Layout & Blocks</title>
            </head>
            <body>
                <div>CONTENTS - Natan Felles >'"</div>
                <script>
                    console.log('Oi')
                </script>
            </body>
            </html>

            EOL;
        $this->view->setLayoutPrefix('layouts');
        self::assertSame($html, $this->view->render('noprefix', [
            'name' => 'Natan Felles >\'"',
            'title' => 'Layout & Blocks',
        ]));
    }

    public function testLayoutPrefix() : void
    {
        self::assertSame('', $this->view->getLayoutPrefix());
        $this->view->setLayoutPrefix('foo');
        self::assertSame('foo/', $this->view->getLayoutPrefix());
        $this->view->setLayoutPrefix('/foo/bar/');
        self::assertSame('foo/bar/', $this->view->getLayoutPrefix());
        $this->view->setLayoutPrefix('');
        self::assertSame('', $this->view->getLayoutPrefix());
    }

    public function testIncludePrefix() : void
    {
        self::assertSame('', $this->view->getIncludePrefix());
        $this->view->setIncludePrefix('foo');
        self::assertSame('foo/', $this->view->getIncludePrefix());
        $this->view->setIncludePrefix('/foo/bar/');
        self::assertSame('foo/bar/', $this->view->getIncludePrefix());
        $this->view->setIncludePrefix('');
        self::assertSame('', $this->view->getIncludePrefix());
    }
}
