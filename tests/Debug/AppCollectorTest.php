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
use Framework\MVC\Debug\AppCollector;
use PHPUnit\Framework\TestCase;
use Tests\MVC\AppMock;

/**
 * @runTestsInSeparateProcesses
 */
final class AppCollectorTest extends TestCase
{
    protected AppCollector $collector;
    protected AppMock $app;

    protected function setUp() : void
    {
        $this->app = new AppMock([
            'autoloader' => [
                'default' => [],
                'autoloader-foo' => [],
                'autoloader-bar' => [],
            ],
        ], true);
        // @phpstan-ignore-next-line
        $this->collector = $this->app::debugger()
            ->getCollection('App')
            ->getCollectors()[0];
    }

    protected function getExternalCollector() : AppCollector
    {
        AppMock::setConfigProperty(null);
        $this->app = new AppMock([], true);
        $debugger = new Debugger();
        $collector = new AppCollector();
        $debugger->addCollector($collector, 'App');
        $collector->setApp($this->app);
        AppMock::setDebugCollector($collector);
        return $collector;
    }

    public function testHeader() : void
    {
        $contents = $this->collector->getContents();
        self::assertStringContainsString('Started at:', $contents);
        self::assertStringContainsString('Runtime:', $contents);
        self::assertStringContainsString('Memory:', $contents);
    }

    public function testNoServiceLoaded() : void
    {
        $contents = $this->getExternalCollector()->getContents();
        self::assertStringContainsString(
            'No service instance has been loaded',
            $contents
        );
    }

    public function testOneServiceLoaded() : void
    {
        $collector = $this->getExternalCollector();
        $this->app::autoloader();
        $this->app::debugger();
        $contents = $collector->getContents();
        self::assertStringContainsString(
            'Total of 1 service instance loaded',
            $contents
        );
        self::assertStringContainsString('autoloader', $contents);
    }

    public function testManyServiceLoaded() : void
    {
        $this->app::autoloader();
        $this->app::language();
        $this->app::validation();
        $contents = $this->collector->getContents();
        self::assertStringContainsString(
            'Total of 4 service instances loaded',
            $contents
        );
        self::assertStringContainsString('debugger', $contents);
        self::assertStringContainsString('autoloader', $contents);
        self::assertStringContainsString('language', $contents);
        self::assertStringContainsString('validation', $contents);
    }

    public function testManyServiceLoadedWithManyInstances() : void
    {
        $this->app::autoloader();
        $this->app::autoloader('autoloader-foo');
        $this->app::autoloader('autoloader-bar');
        $contents = $this->collector->getContents();
        self::assertStringContainsString(
            'Total of 4 service instances loaded',
            $contents
        );
        self::assertStringContainsString('debugger', $contents);
        self::assertStringContainsString('autoloader', $contents);
        self::assertStringContainsString(
            '<td rowspan="3">autoloader</td>',
            $contents
        );
        self::assertStringContainsString('default', $contents);
        self::assertStringContainsString('autoloader-foo', $contents);
        self::assertStringContainsString('autoloader-bar', $contents);
    }

    public function testServicesAvailable() : void
    {
        $contents = $this->collector->getContents();
        self::assertStringContainsString(
            'There are 18 services available',
            $contents
        );
        self::assertStringContainsString(
            '<td rowspan="3">autoloader</td>',
            $contents
        );
        self::assertStringContainsString('default', $contents);
        self::assertStringContainsString('autoloader-foo', $contents);
        self::assertStringContainsString('autoloader-bar', $contents);
        self::assertStringContainsString(
            '<td rowspan="1">request</td>',
            $contents
        );
        self::assertStringContainsString(
            '<td rowspan="1">response</td>',
            $contents
        );
    }

    public function testActivities() : void
    {
        AppMock::autoloader();
        AppMock::autoloader('autoloader-foo');
        $this->collector->getContents();
        $activities = $this->collector->getActivities();
        self::assertSame('Runtime', $activities[0]['description']);
        self::assertSame(
            'Load service debugger:default',
            $activities[1]['description']
        );
        self::assertSame(
            'Load service autoloader:default',
            $activities[2]['description']
        );
        self::assertSame(
            'Load service autoloader:autoloader-foo',
            $activities[3]['description']
        );
    }

    public function testDebugbar() : void
    {
        \ob_start();
        $this->app->runHttp();
        $contents = (string) \ob_get_clean();
        self::assertStringContainsString('debugbar', $contents);
        self::assertStringContainsString('App', $contents);
        self::assertStringContainsString('Runtime', $contents);
    }
}
