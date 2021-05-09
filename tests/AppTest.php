<?php namespace Tests\MVC;

use Framework\Autoload\Autoloader;
use Framework\Autoload\Locator;
use Framework\Cache\Cache;
use Framework\CLI\Console;
use Framework\Database\Database;
use Framework\Database\Definition\Table\TableDefinition;
use Framework\Email\Mailer;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Language\Language;
use Framework\Log\Logger;
//use Framework\MVC\App;
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
	protected function setUp() : void
	{
		App::init(new Config(__DIR__ . '/configs'));
	}

	public function testConfigInstance()
	{
		$this->assertInstanceOf(Config::class, App::config());
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

	/**
	 * @runInSeparateProcess
	 */
	public function testValidator()
	{
		App::database()->dropTable()->table('Users')->ifExists()->run();
		App::database()->createTable()
			->table('Users')
			->definition(static function (TableDefinition $definition) {
				$definition->column('id')->int()->primaryKey();
				$definition->column('username')->varchar(255);
			})->run();
		App::database()->insert()->into('Users')->values(1, 'foo')->run();
		App::database()->insert()->into('Users')->values(2, 'bar')->run();
		$validation = App::validation();
		$validation->setRule('id', 'inDatabase:Users,id,default');
		$status = $validation->validate(['id' => 1]);
		$this->assertTrue($status);
		$status = $validation->validate(['id' => 2]);
		$this->assertTrue($status);
		$status = $validation->validate(['id' => 3]);
		$this->assertFalse($status);
		$this->assertStringContainsString(
			'The id field value does not exists.',
			$validation->getError('id')
		);
		$validation->setRule('id', 'notInDatabase:Users,id,default');
		$status = $validation->validate(['id' => 1]);
		$this->assertFalse($status);
		App::database()->dropTable()->table('Users')->run();
	}

	public function testPrepareRoutes()
	{
		App::prepareRoutes();
		$this->assertInstanceOf(Route::class, App::router()->getNamedRoute('home'));
		App::config()->setMany([
			'routes' => [
				'default' => [],
			],
		]);
		App::prepareRoutes();
		App::config()->setMany([
			'routes' => [
				'default' => [
					'file-not-found',
				],
			],
		]);
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Invalid route file: file-not-found');
		App::prepareRoutes();
	}

	public function testRunEmptyConsole()
	{
		$this->assertNull(App::run());
	}

	public function testRunConsole()
	{
		App::config()->set('console', ['enabled' => true]);
		$this->assertNull(App::run());
	}

	public function testRunResponse()
	{
		App::$notIsCLI = true;
		App::run();
		$this->assertTrue(App::response()->isSent());
	}

	public function testAppIsNotInitilized()
	{
		App::setConfigProperty(null);
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('App Config not initialized');
		App::run();
	}

	public function testAppAlreadyIsRunning()
	{
		App::run();
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('App already is running');
		App::run();
	}

	public function testMakeResponseBodyPart()
	{
		$this->assertEquals('', App::makeResponseBodyPart(null));
		$this->assertEquals('', App::makeResponseBodyPart(App::response()));
		$this->assertEquals('1.2', App::makeResponseBodyPart(1.2));
		$stringableObject = new class() {
			public function __toString() : string
			{
				return 'foo';
			}
		};
		$this->assertEquals('foo', App::makeResponseBodyPart($stringableObject));
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage("Invalid return type 'stdClass' on matched route");
		App::makeResponseBodyPart(new \stdClass());
	}
}
