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
class AppTest extends TestCase
{
	public function setup() : void
	{
		$config = [];
		require __DIR__ . '/../src/configs.php';
		App::setConfigs($config);
	}

	public function testConfigs()
	{
		$this->assertEquals([
			'host' => \getenv('DB_HOST'),
			'port' => \getenv('DB_PORT'),
			'username' => \getenv('DB_USERNAME'),
			'password' => \getenv('DB_PASSWORD'),
			'schema' => \getenv('DB_SCHEMA'),
		], App::getConfig('database'));
		$this->assertNull(App::getConfig('database', 'other'));
		App::setConfig('foo', ['bar', 'baz']);
		$this->assertEquals(['bar', 'baz'], App::getConfig('foo'));
		App::setConfig('foo', ['replaced']);
		$this->assertEquals(['replaced'], App::getConfig('foo'));
		App::addConfig('foo', ['added']);
		$this->assertEquals(['added'], App::getConfig('foo'));
		App::addConfig('database', ['password' => 'foo'], 'other');
		$this->assertEquals(['password' => 'foo'], App::getConfig('database', 'other'));
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
		App::setConfig('autoloader', [
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

	/*public function testPrepareRoutes()
	{
		$this->assertStringEndsWith('src/routes.php', App::prepareRoutes()[0]);
		$this->assertCount(1, App::router()->getRoutes());
	}*/
	public function testMergeFileConfigs()
	{
		$this->assertEquals([
			'enabled' => true,
			'defaults' => true,
		], App::getConfig('console'));
		App::mergeFileConfigs(__DIR__ . '/Support/configs.php');
		$this->assertEquals([
			'enabled' => false,
			'defaults' => true,
		], App::getConfig('console'));
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Invalid config file path: /tmp/unknown');
		App::mergeFileConfigs('/tmp/unknown');
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
}
