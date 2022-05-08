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

use Framework\MVC\App;
use Framework\MVC\Debug\ViewCollector;
use Framework\MVC\View;
use PHPUnit\Framework\TestCase;

final class ViewTest extends TestCase
{
    protected ViewMock $view;

    protected function setUp() : void
    {
        $this->view = new ViewMock(__DIR__ . '/Views');
    }

    public function testBaseDir() : void
    {
        $dir = \realpath(__DIR__ . '/Views') . \DIRECTORY_SEPARATOR;
        self::assertSame($dir, $this->view->getBaseDir());
        $this->view->setBaseDir($dir);
        self::assertSame($dir, $this->view->getBaseDir());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'View base dir is not a valid directory: /foo/bar'
        );
        $this->view->setBaseDir('/foo/bar');
    }

    public function testBaseDirNotSet() : void
    {
        $view = new View();
        self::assertNull($view->getBaseDir());
    }

    public function testExtension() : void
    {
        self::assertSame('.php', $this->view->getExtension());
        $this->view->setExtension('.phtml');
        self::assertSame('.phtml', $this->view->getExtension());
    }

    public function testLayoutPrefix() : void
    {
        self::assertSame('', $this->view->getLayoutPrefix());
        $this->view->setLayoutPrefix('');
        self::assertSame('', $this->view->getLayoutPrefix());
        $this->view->setLayoutPrefix('/_layouts/');
        self::assertSame('_layouts/', $this->view->getLayoutPrefix());
    }

    public function testIncludePrefix() : void
    {
        self::assertSame('', $this->view->getIncludePrefix());
        $this->view->setIncludePrefix('');
        self::assertSame('', $this->view->getIncludePrefix());
        $this->view->setIncludePrefix('/_includes/');
        self::assertSame('_includes/', $this->view->getIncludePrefix());
    }

    protected function setApp() : App
    {
        return new App([
            'autoloader' => [
                'default' => [
                    'namespaces' => [
                        'App\Views' => __DIR__ . '/Views',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testNamespacedFilepath() : void
    {
        $this->setApp();
        self::assertSame(
            __DIR__ . '/Views/home/index.php',
            $this->view->getNamespacedFilepath('App\Views/home/index')
        );
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Namespaced view path does not match a file: Foo\bar'
        );
        $this->view->getNamespacedFilepath('Foo\bar');
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetFilepath() : void
    {
        $this->setApp();
        self::assertSame(
            __DIR__ . '/Views/welcome.php',
            $this->view->getFilepath('\App\Views/welcome')
        );
        self::assertSame(
            __DIR__ . '/Views/home/index.php',
            $this->view->getFilepath('\App\Views/home/index')
        );
        self::assertSame(
            __DIR__ . '/Views/welcome.php',
            $this->view->getFilepath('welcome')
        );
        self::assertSame(
            __DIR__ . '/Views/home/index.php',
            $this->view->getFilepath('home/index')
        );
    }

    public function testFilepathIsNotFile() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $view = __DIR__ . '/Views/home';
        $this->expectExceptionMessage(
            "View path does not match a file: {$view}"
        );
        $this->view->getFilepath('home');
    }

    public function testFilepathOutOfBaseDir() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $real = __DIR__ . '/ViewMock.php';
        $this->expectExceptionMessage(
            "View path out of base directory: {$real}"
        );
        $this->view->getFilepath('../ViewMock');
    }

    public function testRender() : void
    {
        self::assertStringContainsString(
            '<h1>Welcome</h1>',
            $this->view->render('welcome')
        );
    }

    public function testExtendsWithOpenBlock() : void
    {
        $contents = $this->view->render('open-block');
        self::assertSame(
            <<<EOL
                <h1>Layout Default</h1>
                <h2>Open Block</h2>\n
                EOL,
            $contents
        );
    }

    public function testExtendsWithOpenBlockAndRenderIncludes() : void
    {
        $contents = $this->view->render('open-block-include');
        self::assertSame(
            <<<EOL
                <h1>Layout Default</h1>
                <footer>Footer</footer>
                <h2>Open Block</h2>
                <footer>Footer</footer>\n
                EOL,
            $contents
        );
    }

    public function testRenderLayout() : void
    {
        $contents = $this->view->render('home/index');
        self::assertSame(
            <<<EOL
                <h1>Layout Default</h1>
                <h2>Home Index</h2>
                <div>Foo bar baz</div>
                <footer>Footer</footer>\n
                EOL,
            $contents
        );
    }

    public function testRenderLayoutWithPrefix() : void
    {
        $this->view->setLayoutPrefix('_layouts')
            ->setIncludePrefix('_includes');
        $contents = $this->view->render('home/with-prefix');
        self::assertSame(
            <<<EOL
                <h1>Layout Default</h1>
                <h2>Home Index</h2>
                <div>Foo bar baz</div>
                <footer>Footer</footer>\n
                EOL,
            $contents
        );
    }

    public function testRenderLayoutWithoutPrefix() : void
    {
        $this->view->setLayoutPrefix('_layouts')
            ->setIncludePrefix('_includes');
        $contents = $this->view->render('home/without-prefix');
        self::assertSame(
            <<<EOL
                <h1>Layout Default</h1>
                <h2>Home Index</h2>
                <div>Foo bar baz</div>
                <footer>Footer</footer>\n
                EOL,
            $contents
        );
    }

    public function testInBlockInLayout() : void
    {
        $this->view->setLayoutPrefix('_layouts');
        $this->view->render('in-block-in-layout', [
            'testCase' => $this,
        ]);
    }

    public function testBlock() : void
    {
        $this->view->block('contents');
        self::assertFalse($this->view->hasBlock('contents'));
        self::assertTrue($this->view->inBlock('contents'));
        self::assertSame('contents', $this->view->currentBlock());
        echo 'Block Contents';
        echo $this->view->renderBlock('contents');
        $this->view->endBlock();
        self::assertTrue($this->view->hasBlock('contents'));
        self::assertFalse($this->view->inBlock('contents'));
        self::assertSame(
            'Block Contents',
            $this->view->renderBlock('contents')
        );
        $this->view->block('foo');
        echo 'Foo ';
        echo $this->view->renderBlock('contents');
        self::assertFalse($this->view->hasBlock('foo'));
        self::assertTrue($this->view->hasBlock('contents'));
        self::assertTrue($this->view->inBlock('foo'));
        self::assertFalse($this->view->inBlock('contents'));
        self::assertSame('foo', $this->view->currentBlock());
        $this->view->endBlock();
        self::assertSame(
            'Foo Block Contents',
            $this->view->renderBlock('foo')
        );
        $this->view->removeBlock('foo');
        self::assertNull($this->view->renderBlock('foo'));
    }

    public function testEndBlockWhenNoneIsOpen() : void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Trying to end a view block when none is open');
        $this->view->endBlock();
    }

    public function testDestructWhenBlockIsOpen() : void
    {
        $this->view->block('foo');
        \ob_end_clean();
        $this->view->block('bar');
        \ob_end_clean();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Trying to destruct a View instance while the following blocks stayed open: ' .
            "'foo', 'bar'"
        );
        unset($this->view);
    }

    protected function setDebugCollector() : void
    {
        $collector = new ViewCollector();
        $this->view->setDebugCollector($collector);
    }

    public function testDebugBlock() : void
    {
        $this->setDebugCollector();
        $contents = $this->view->render('home/index');
        self::assertStringContainsString(
            '<!-- Block start: home/index::contents -->',
            $contents
        );
        self::assertStringContainsString(
            '<!-- Block end: home/index::contents -->',
            $contents
        );
    }

    public function testDebugInclude() : void
    {
        $this->setDebugCollector();
        $contents = $this->view->render('home/index');
        self::assertStringContainsString(
            '<!-- Include start: _includes/footer -->',
            $contents
        );
        self::assertStringContainsString(
            '<!-- Include end: _includes/footer -->',
            $contents
        );
        $contents = $this->view->render('home/without-prefix');
        self::assertStringContainsString(
            '<!-- Include start: _includes/footer -->',
            $contents
        );
        self::assertStringContainsString(
            '<!-- Include end: _includes/footer -->',
            $contents
        );
    }

    public function testDebugRender() : void
    {
        $this->setDebugCollector();
        $contents = $this->view->render('home/index');
        self::assertStringContainsString(
            '<!-- Render start: home/index -->',
            $contents
        );
        self::assertStringContainsString(
            '<!-- Render end: home/index -->',
            $contents
        );
    }

    public function testDebugComments() : void
    {
        $this->view->setDebugCollector(new ViewCollector())
            ->setLayoutPrefix('_layouts')
            ->setIncludePrefix('_includes');
        $contents = $this->view->render('comments');
        self::assertSame(
            <<<'EOL'
                <!-- Render start: comments -->
                <!-- Layout start: _layouts/default -->
                <h1>Layout Default</h1>

                <!-- Block start: comments::contents -->
                CONTENTS

                <!-- Include start: _includes/footer -->
                <footer>Footer</footer>

                <!-- Include end: _includes/footer -->

                <!-- Block end: comments::contents -->

                <!-- Layout end: _layouts/default -->
                <!-- Render end: comments -->
                EOL,
            $contents
        );
    }
}
