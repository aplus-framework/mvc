<?php namespace Tests\MVC;

use Framework\Autoload\Autoloader;
use Framework\Autoload\Locator;
use Framework\Cache\Cache;
use Framework\CLI\Console;
use Framework\CLI\Stream;
use Framework\Database\Database;
use Framework\Database\Definition\Table\TableDefinition;
use Framework\Email\Mailer;
use Framework\HTTP\CSRF;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Language\Language;
use Framework\Log\Logger;
use Framework\MVC\Config;
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
class AppTest extends TestCase
{
	protected AppMock $app;

	protected function setUp() : void
	{
		$this->app = new App(new Config(__DIR__ . '/configs'));
	}

	public function testConfigInstance()
	{
		$this->assertInstanceOf(Config::class, App::config());
	}

	public function testServices()
	{
		$this->assertNull(App::getService('foo'));
		App::setService('foo', new \stdClass());
		$this->assertInstanceOf(\stdClass::class, App::getService('foo'));
		App::removeService('foo', 'default');
		$this->assertNull(App::getService('foo'));
		App::setService('foo', new \stdClass());
		App::setService('foo', new \stdClass(), 'other');
		$this->assertInstanceOf(\stdClass::class, App::getService('foo'));
		$this->assertInstanceOf(\stdClass::class, App::getService('foo', 'other'));
		App::removeService('foo', null);
		$this->assertNull(App::getService('foo'));
		$this->assertNull(App::getService('foo', 'other'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testServicesInstances()
	{
		$this->assertInstanceOf(Autoloader::class, App::autoloader());
		$this->assertInstanceOf(Autoloader::class, App::autoloader());
		$this->assertInstanceOf(Cache::class, App::cache());
		$this->assertInstanceOf(Cache::class, App::cache());
		$this->assertInstanceOf(Console::class, App::console());
		$this->assertInstanceOf(Console::class, App::console());
		$this->assertInstanceOf(CSRF::class, App::csrf());
		$this->assertInstanceOf(CSRF::class, App::csrf());
		$this->assertInstanceOf(Console::class, App::console());
		$this->assertInstanceOf(Database::class, App::database());
		$this->assertInstanceOf(Database::class, App::database());
		$this->assertInstanceOf(Language::class, App::language());
		$this->assertInstanceOf(Language::class, App::language());
		$this->assertInstanceOf(Locator::class, App::locator());
		$this->assertInstanceOf(Locator::class, App::locator());
		$this->assertInstanceOf(Logger::class, App::logger());
		$this->assertInstanceOf(Logger::class, App::logger());
		$this->assertInstanceOf(Mailer::class, App::mailer());
		$this->assertInstanceOf(Mailer::class, App::mailer());
		$this->assertInstanceOf(Request::class, App::request());
		$this->assertInstanceOf(Request::class, App::request());
		$this->assertInstanceOf(Response::class, App::response());
		$this->assertInstanceOf(Response::class, App::response());
		$this->assertInstanceOf(Router::class, App::router());
		$this->assertInstanceOf(Router::class, App::router());
		$this->assertInstanceOf(Session::class, App::session());
		$this->assertInstanceOf(Session::class, App::session());
		$this->assertInstanceOf(Validation::class, App::validation());
		$this->assertInstanceOf(Validation::class, App::validation());
		$this->assertInstanceOf(View::class, App::view());
		$this->assertInstanceOf(View::class, App::view());
	}

	public function testAutoloaderWithConfigs()
	{
		App::config()->set('autoloader', [
			'namespaces' => [
				__NAMESPACE__ => __DIR__,
			],
			'classes' => [
				__CLASS__ => __FILE__,
			],
		]);
		$this->assertEquals([
			__NAMESPACE__ => __DIR__ . '/',
		], App::autoloader()->getNamespaces());
		$this->assertEquals([
			__CLASS__ => __FILE__,
		], App::autoloader()->getClasses());
	}

	public function testPrepareRoutes()
	{
		$this->app->prepareRoutes();
		$this->assertInstanceOf(Route::class, App::router()->getNamedRoute('home'));
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

	public function testRunEmptyConsole()
	{
		Stream::init();
		$this->app->run();
		$this->assertEquals('', Stream::getOutput());
	}

	public function testRunConsole()
	{
		App::config()->set('console', ['enabled' => true]);
		Stream::init();
		$this->app->run();
		$this->assertStringContainsString('Commands', Stream::getOutput());
	}

	public function testRunResponse()
	{
		App::setIsCLI(false);
		$this->app->run();
		$this->assertTrue(App::response()->isSent());
	}

	public function testAppIsAlreadyInitilized()
	{
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('App already initialized');
		(new App(new Config(__DIR__ . '/configs')));
	}

	public function testAppAlreadyIsRunning()
	{
		$this->app->run();
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('App already is running');
		$this->app->run();
	}

	public function testMakeResponseBodyPart()
	{
		$this->assertEquals('', $this->app->makeResponseBodyPart(null));
		$this->assertEquals('', $this->app->makeResponseBodyPart(App::response()));
		$this->assertEquals('1.2', $this->app->makeResponseBodyPart(1.2));
		$stringableObject = new class() {
			public function __toString() : string
			{
				return 'foo';
			}
		};
		$this->assertEquals('foo', $this->app->makeResponseBodyPart($stringableObject));
		$this->assertNull(App::response()->getHeader('content-type'));
		$this->assertEquals('', $this->app->makeResponseBodyPart(['id' => 1]));
		$this->assertEquals(
			'application/json; charset=UTF-8',
			App::response()->getHeader('content-type')
		);
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage("Invalid return type 'Tests\\MVC\\AppMock' on matched route");
		$this->app->makeResponseBodyPart($this->app);
	}

	public function testIsCli()
	{
		$this->assertTrue(App::isCLI());
		App::setIsCLI(false);
		$this->assertFalse(App::isCLI());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSessionWithCacheHandler()
	{
		App::config()->add('session', [
			'save_handler' => [
				'class' => \Framework\Session\SaveHandlers\Cache::class,
				'config' => 'default',
			],
		]);
		$this->assertInstanceOf(Session::class, App::session());
		App::session()->foo = 'Foo';
		$this->assertEquals('Foo', App::session()->foo);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSessionWithDatabaseHandler()
	{
		App::database()->dropTable('Sessions')->ifExists()->run();
		App::database()->createTable('Sessions')
			->definition(static function (TableDefinition $definition) {
				$definition->column('id')->varchar(128)->primaryKey();
				$definition->column('ip')->varchar(45)->null();
				$definition->column('ua')->varchar(255)->null();
				$definition->column('timestamp')->int(10)->unsigned();
				$definition->column('data')->blob();
				$definition->index('ip')->key('ip');
				$definition->index('ua')->key('ua');
				$definition->index('timestamp')->key('timestamp');
			})->run();
		App::config()->add('session', [
			'save_handler' => [
				'class' => \Framework\Session\SaveHandlers\Database::class,
				'config' => 'default',
			],
		]);
		$this->assertInstanceOf(Session::class, App::session());
		App::session()->foo = 'Foo';
		$this->assertEquals('Foo', App::session()->foo);
		App::session()->stop();
		$this->assertEquals(
			\time(),
			App::database()
				->select()
				->from('Sessions')
				->whereEqual('id', \session_id())
				->limit(1)
				->run()
				->fetch()
				->timestamp
		);
	}
}
