<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\MVC\Debug;

use Framework\Debug\Debugger;
use Framework\MVC\Debug\ViewCollector;
use Framework\MVC\View;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
final class ViewCollectorTest extends TestCase
{
    protected Debugger $debugger;
    protected ViewCollector $collector;
    protected View $view;

    protected function setUp() : void
    {
        $this->debugger = new Debugger();
        $this->collector = new ViewCollector();
        $this->debugger->addCollector($this->collector, 'View');
        $this->view = new View();
        $this->view->setDebugCollector($this->collector);
    }

    public function testEmptyConfig() : void
    {
        $contents = $this->collector->getContents();
        self::assertStringNotContainsString('Base Directory:', $contents);
        self::assertStringContainsString('Extension:', $contents);
        self::assertStringNotContainsString('Layout Prefix:', $contents);
        self::assertStringNotContainsString('Include Prefix:', $contents);
        self::assertStringContainsString('Rendered Views', $contents);
        self::assertStringContainsString('No view has been rendered', $contents);
    }

    public function testBasicConfig() : void
    {
        $this->view->setBaseDir(__DIR__ . '/../Views')
            ->setLayoutPrefix('_layouts')
            ->setIncludePrefix('_includes');
        $contents = $this->collector->getContents();
        self::assertStringContainsString('Base Directory:', $contents);
        self::assertStringContainsString('Extension:', $contents);
        self::assertStringContainsString('Layout Prefix:', $contents);
        self::assertStringContainsString('Include Prefix:', $contents);
        self::assertStringContainsString('Rendered Views', $contents);
        self::assertStringContainsString('No view has been rendered', $contents);
    }

    public function testRenderedViews() : void
    {
        $this->view->setBaseDir(__DIR__ . '/../Views');
        $this->view->render('home/index');
        $contents = $this->collector->getContents();
        self::assertStringContainsString('Rendered Views', $contents);
        self::assertStringNotContainsString('No view has been rendered', $contents);
        self::assertStringContainsString('home/index', $contents);
        self::assertStringContainsString('_includes/footer', $contents);
        self::assertStringContainsString('_layouts/default', $contents);
        self::assertStringContainsString(
            'Total of 3 rendered view files',
            $contents
        );
    }

    public function testActivities() : void
    {
        $this->view->setBaseDir(__DIR__ . '/../Views');
        $this->view->render('home/index');
        $activities = $this->collector->getActivities();
        self::assertSame('Render view 1', $activities[0]['description']);
        self::assertSame('Render view 2', $activities[1]['description']);
    }

    public function testDebugbar() : void
    {
        $this->view->setBaseDir(__DIR__ . '/../Views');
        $this->view->render('home/index');
        $contents = $this->debugger->renderDebugbar();
        self::assertStringContainsString('home/index', $contents);
        self::assertStringContainsString('_layouts/default', $contents);
    }
}
