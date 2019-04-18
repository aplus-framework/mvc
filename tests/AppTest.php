<?php namespace Tests\MVC;

use Framework\Autoload\Autoloader;
use Framework\Autoload\Locator;
use Framework\Database\Database;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Language\Language;
use Framework\MVC\App;
use Framework\MVC\View;
use Framework\Routing\Router;
use Framework\Validation\Validation;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
	public function setup()
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

	public function testServicesInstances()
	{
		$this->assertInstanceOf(Autoloader::class, App::getAutoloader());
		$this->assertInstanceOf(Database::class, App::getDatabase());
		$this->assertInstanceOf(Language::class, App::getLanguage());
		$this->assertInstanceOf(Locator::class, App::getLocator());
		//$this->assertInstanceOf(Request::class, App::getRequest());
		//$this->assertInstanceOf(Response::class, App::getResponse());
		$this->assertInstanceOf(Router::class, App::getRouter());
		$this->assertInstanceOf(Validation::class, App::getValidation());
		$this->assertInstanceOf(View::class, App::getView());
	}
}
