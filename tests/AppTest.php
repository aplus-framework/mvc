<?php namespace Tests\MVC;

use Framework\Autoload\Autoloader;
use Framework\Autoload\Locator;
use Framework\Cache\Cache;
use Framework\CLI\Console;
use Framework\Database\Database;
use Framework\Email\Mailer;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Language\Language;
use Framework\Log\Logger;
use Framework\MVC\App;
use Framework\MVC\View;
use Framework\Routing\Router;
use Framework\Session\Session;
use Framework\Validation\Validation;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
	public function setup() : void
	{
		App::setConfig('database', [
			'host' => \getenv('DB_HOST'),
			'port' => \getenv('DB_PORT'),
			'username' => \getenv('DB_USERNAME'),
			'password' => \getenv('DB_PASSWORD'),
			'schema' => \getenv('DB_SCHEMA'),
		]);
	}

	public function testConfigs()
	{
		$this->assertEquals([
			'database' => [
				'default' => [
					'host' => \getenv('DB_HOST'),
					'port' => \getenv('DB_PORT'),
					'username' => \getenv('DB_USERNAME'),
					'password' => \getenv('DB_PASSWORD'),
					'schema' => \getenv('DB_SCHEMA'),
				],
			],
		], App::getConfigs());
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
		$this->assertEquals(['replaced', 'added'], App::getConfig('foo'));
		App::addConfig('database', ['password' => 'foo'], 'other');
		$this->assertEquals(['password' => 'foo'], App::getConfig('database', 'other'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testServicesInstances()
	{
		App::setConfig('cache', [
			'driver' => 'Files',
			'configs' => [
				'directory' => '/tmp',
				'length' => 4096,
			],
			'prefix' => null,
			'serializer' => 'php',
		]);
		App::setConfig('logger', [
			'directory' => '/tmp',
			'level' => 0,
		]);
		$this->assertInstanceOf(Autoloader::class, App::getAutoloader());
		$this->assertInstanceOf(Autoloader::class, App::getAutoloader());
		$this->assertInstanceOf(Cache::class, App::getCache());
		$this->assertInstanceOf(Cache::class, App::getCache());
		$this->assertInstanceOf(Console::class, App::getConsole());
		$this->assertInstanceOf(Console::class, App::getConsole());
		$this->assertInstanceOf(Database::class, App::getDatabase());
		$this->assertInstanceOf(Database::class, App::getDatabase());
		$this->assertInstanceOf(Language::class, App::getLanguage());
		$this->assertInstanceOf(Language::class, App::getLanguage());
		$this->assertInstanceOf(Locator::class, App::getLocator());
		$this->assertInstanceOf(Locator::class, App::getLocator());
		$this->assertInstanceOf(Logger::class, App::getLogger());
		$this->assertInstanceOf(Logger::class, App::getLogger());
		$this->assertInstanceOf(Mailer::class, App::getMailer());
		$this->assertInstanceOf(Mailer::class, App::getMailer());
		//$this->assertInstanceOf(Request::class, App::getRequest());
		//$this->assertInstanceOf(Response::class, App::getResponse());
		$this->assertInstanceOf(Router::class, App::getRouter());
		$this->assertInstanceOf(Router::class, App::getRouter());
		$this->assertInstanceOf(Session::class, App::getSession());
		$this->assertInstanceOf(Session::class, App::getSession());
		$this->assertInstanceOf(Validation::class, App::getValidation());
		$this->assertInstanceOf(Validation::class, App::getValidation());
		$this->assertInstanceOf(View::class, App::getView());
		$this->assertInstanceOf(View::class, App::getView());
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
		], App::getAutoloader()->getNamespaces());
		$this->assertEquals([
			__CLASS__ => __FILE__,
		], App::getAutoloader()->getClasses());
	}
}
