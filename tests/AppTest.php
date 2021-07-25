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

use Framework\Autoload\Autoloader;
use Framework\Autoload\Locator;
use Framework\Cache\Cache;
use Framework\CLI\Console;
use Framework\CLI\Stream;
use Framework\Config\Config;
use Framework\Database\Database;
use Framework\Database\Definition\Table\TableDefinition;
use Framework\Email\Mailer;
use Framework\HTTP\AntiCSRF;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Language\Language;
use Framework\Log\Logger;
use Framework\MVC\View;
use Framework\Routing\Route;
use Framework\Routing\Router;
use Framework\Session\Session;
use Framework\Validation\Validation;
use PHPUnit\Framework\TestCase;
use Tests\MVC\AppMock as App;

/**
 * Class AppTest.
 *
 * @runTestsInSeparateProcesses
 */
final class AppTest extends TestCase
{
    protected AppMock $app;

    protected function setUp() : void
    {
        $this->app = new App(new Config(__DIR__ . '/configs', [], '.config.php'));
    }

    public function testConfigInstance() : void
    {
        self::assertInstanceOf(Config::class, App::config());
    }

    public function testServices() : void
    {
        self::assertNull(App::getService('foo'));
        App::setService('foo', new \stdClass());
        self::assertInstanceOf(\stdClass::class, App::getService('foo'));
        App::removeService('foo', 'default');
        self::assertNull(App::getService('foo'));
        App::setService('foo', new \stdClass());
        App::setService('foo', new \stdClass(), 'other');
        self::assertInstanceOf(\stdClass::class, App::getService('foo'));
        self::assertInstanceOf(\stdClass::class, App::getService('foo', 'other'));
        App::removeService('foo', null);
        self::assertNull(App::getService('foo'));
        self::assertNull(App::getService('foo', 'other'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testServicesInstances() : void
    {
        self::assertInstanceOf(Autoloader::class, App::autoloader());
        self::assertInstanceOf(Autoloader::class, App::autoloader());
        self::assertInstanceOf(Cache::class, App::cache());
        self::assertInstanceOf(Cache::class, App::cache());
        self::assertInstanceOf(Console::class, App::console());
        self::assertInstanceOf(Console::class, App::console());
        self::assertInstanceOf(AntiCSRF::class, App::antiCsrf());
        self::assertInstanceOf(AntiCSRF::class, App::antiCsrf());
        self::assertInstanceOf(Console::class, App::console());
        self::assertInstanceOf(Database::class, App::database());
        self::assertInstanceOf(Database::class, App::database());
        self::assertInstanceOf(Language::class, App::language());
        self::assertInstanceOf(Language::class, App::language());
        self::assertInstanceOf(Locator::class, App::locator());
        self::assertInstanceOf(Locator::class, App::locator());
        self::assertInstanceOf(Logger::class, App::logger());
        self::assertInstanceOf(Logger::class, App::logger());
        self::assertInstanceOf(Mailer::class, App::mailer());
        self::assertInstanceOf(Mailer::class, App::mailer());
        self::assertInstanceOf(Request::class, App::request());
        self::assertInstanceOf(Request::class, App::request());
        self::assertInstanceOf(Response::class, App::response());
        self::assertInstanceOf(Response::class, App::response());
        self::assertInstanceOf(Router::class, App::router());
        self::assertInstanceOf(Router::class, App::router());
        self::assertInstanceOf(Session::class, App::session());
        self::assertInstanceOf(Session::class, App::session());
        self::assertInstanceOf(Validation::class, App::validation());
        self::assertInstanceOf(Validation::class, App::validation());
        self::assertInstanceOf(View::class, App::view());
        self::assertInstanceOf(View::class, App::view());
    }

    public function testAutoloaderWithConfigs() : void
    {
        App::config()->set('autoloader', [
            'namespaces' => [
                __NAMESPACE__ => __DIR__,
            ],
            'classes' => [
                __CLASS__ => __FILE__,
            ],
        ]);
        self::assertSame([
            __NAMESPACE__ => __DIR__ . '/',
        ], App::autoloader()->getNamespaces());
        self::assertSame([
            __CLASS__ => __FILE__,
        ], App::autoloader()->getClasses());
    }

    public function testPrepareRoutes() : void
    {
        $this->app->prepareRoutes();
        self::assertInstanceOf(Route::class, App::router()->getNamedRoute('home'));
        App::config()->setMany([
            'routes' => [
                'default' => [],
            ],
        ]);
        $this->app->prepareRoutes();
        App::config()->setMany([
            'routes' => [
                'default' => [
                    'file-not-found',
                ],
            ],
        ]);
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid route file: file-not-found');
        $this->app->prepareRoutes();
    }

    public function testRunEmptyConsole() : void
    {
        Stream::init();
        $this->app->run();
        self::assertSame('', Stream::getOutput());
    }

    public function testRunConsole() : void
    {
        App::config()->set('console', ['enabled' => true]);
        Stream::init();
        $this->app->run();
        self::assertStringContainsString('Commands', Stream::getOutput());
    }

    public function testRunResponse() : void
    {
        App::setIsCli(false);
        $this->app->run();
        self::assertTrue(App::response()->isSent());
    }

    public function testAppAlreadyInitialized() : void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('App already initialized');
        (new App(new Config(__DIR__ . '/configs')));
    }

    public function testAppIsAlreadyRunning() : void
    {
        $this->app->run();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('App is already running');
        $this->app->run();
    }

    public function testIsCli() : void
    {
        self::assertTrue(App::isCli());
        App::setIsCli(false);
        self::assertFalse(App::isCli());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionWithFilesHandler() : void
    {
        App::config()->add('session', [
            'save_handler' => [
                'class' => \Framework\Session\SaveHandlers\FilesHandler::class,
                'config' => [
                    'directory' => \sys_get_temp_dir(),
                ],
            ],
        ]);
        self::assertInstanceOf(Session::class, App::session());
        App::session()->foo = 'Foo'; // @phpstan-ignore-line
        self::assertSame('Foo', App::session()->foo);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionWithDatabaseHandler() : void
    {
        App::database()->dropTable('Sessions')->ifExists()->run();
        App::database()->createTable('Sessions')
            ->definition(static function (TableDefinition $definition) : void {
                $definition->column('id')->varchar(128)->primaryKey();
                $definition->column('data')->blob();
                $definition->column('timestamp')->timestamp();
            })->run();
        $config = App::database()->getConfig();
        App::config()->add('session', [
            'save_handler' => [
                'class' => \Framework\Session\SaveHandlers\DatabaseHandler::class,
                'config' => [
                    'table' => 'Sessions',
                    'host' => $config['host'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'schema' => $config['schema'],
                ],
            ],
        ]);
        self::assertInstanceOf(Session::class, App::session());
        App::session()->foo = 'Foo'; // @phpstan-ignore-line
        self::assertSame('Foo', App::session()->foo);
        App::session()->stop();
        self::assertSame(
            (new \DateTime('now', new \DateTimeZone($config['timezone'])))->format('Y-m-d H:i:s'),
            // @phpstan-ignore-next-line
            App::database()
                ->select()
                ->from('Sessions')
                // @phpstan-ignore-next-line
                ->whereEqual('id', \session_id()) // @phpstan-ignore-line
                ->limit(1)
                ->run()
                ->fetch()
                ->timestamp
        );
    }
}
