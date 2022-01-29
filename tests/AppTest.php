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
use Framework\CLI\Streams\Stdout;
use Framework\Config\Config;
use Framework\Database\Database;
use Framework\Database\Definition\Table\TableDefinition;
use Framework\Debug\ExceptionHandler;
use Framework\Email\Mailer;
use Framework\HTTP\AntiCSRF;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Language\Language;
use Framework\Log\Logger;
use Framework\MVC\View;
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

    public function testInitialization() : void
    {
        App::setConfigProperty(null);
        new App(new Config());
        App::setConfigProperty(null);
        new App([]);
        App::setConfigProperty(null);
        new App(__DIR__ . '/configs');
        App::setConfigProperty(null);
        new App();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('App already initialized');
        new App();
    }

    public function testCall() : void
    {
        $this->app->setRequiredCliVars(); // @phpstan-ignore-line
        self::assertSame('/', $_SERVER['REQUEST_URI']);
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method ' . $this->app::class . '::foo()');
        $this->app->foo(); // @phpstan-ignore-line
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
        App::removeService('foo');
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
        self::assertInstanceOf(ExceptionHandler::class, App::exceptions());
        self::assertInstanceOf(ExceptionHandler::class, App::exceptions());
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

    public function testRunCli() : void
    {
        Stdout::init();
        $this->app->runCli();
        self::assertStringContainsString('Commands', Stdout::getContents());
    }

    public function testRunCliWithConsoleDisabled() : void
    {
        Stdout::init();
        $this->app->runCli(['console' => false]);
        self::assertSame('', Stdout::getContents());
    }

    public function testRunHttp() : void
    {
        App::setIsCli(false);
        \ob_start();
        $this->app->runHttp();
        \ob_end_clean();
        self::assertTrue(App::response()->isSent());
    }

    public function testRunHttpWithOptions() : void
    {
        App::setIsCli(false);
        \ob_start();
        $this->app->runHttp([
            'autoloader' => 'default',
            'exceptions' => 'default',
            'router' => 'default',
        ]);
        \ob_end_clean();
        self::assertTrue(App::response()->isSent());
    }

    public function testRunHttpWithInvalidRouterFile() : void
    {
        $file = __DIR__ . '/foobar.php';
        App::config()->add('router', ['files' => [$file]]);
        App::setIsCli(false);
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid router file: ' . $file);
        $this->app->runHttp();
    }

    public function testRunHttpWithRouterDisabled() : void
    {
        App::setIsCli(false);
        \ob_start();
        $this->app->runHttp(['router' => false]);
        \ob_end_clean();
        self::assertFalse(App::response()->isSent());
    }

    public function testAppIsAlreadyRunning() : void
    {
        $this->app::config()->set('console', ['enabled' => true]);
        \ob_start();
        $this->app->runHttp();
        \ob_end_clean();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('App is already running');
        $this->app->runCli();
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
            'logger_instance' => 'default',
        ]);
        self::assertInstanceOf(Session::class, App::session());
        self::assertFalse(App::session()->isActive());
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
        App::session()->start();
        App::session()->foo = 'Foo'; // @phpstan-ignore-line
        self::assertSame('Foo', App::session()->foo);
        App::session()->stop();
        // @phpstan-ignore-next-line
        $timestamp = App::database()->select('Sessions')->whereEqual('id', \session_id())
            ->limit(1)
            ->run()
            ->fetch()
            ->timestamp;
        self::assertSame(
            (new \DateTime('now', new \DateTimeZone($config['timezone'])))->format('Y-m-d H:i:s'),
            $timestamp
        );
    }

    public function testNegotiateLanguage() : void
    {
        $language = new Language('es');
        $language->setSupportedLocales([
            'en',
            'es',
            'pt-br',
        ]);
        self::assertSame('es', App::negotiateLanguage($language));
        \putenv('LANG=pt_BR.UTF-8');
        self::assertSame('pt-br', App::negotiateLanguage($language));
        \putenv('LANG=jp_JP.UTF-8');
        self::assertSame('es', App::negotiateLanguage($language));
        App::setIsCli(false);
        self::assertSame('en', App::negotiateLanguage($language));
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'pt-BR,es;q=0.8,en;q=0.5,en-US;q=0.3';
        self::assertSame('pt-br', App::negotiateLanguage($language));
        App::removeService('request');
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'jp,es;q=0.8,en;q=0.5,en-US;q=0.3';
        self::assertSame('es', App::negotiateLanguage($language));
    }

    public function testResponse() : void
    {
        App::config()->add('response', ['cache' => false]);
        self::assertSame(0, App::response()->getCacheSeconds());
    }
}
