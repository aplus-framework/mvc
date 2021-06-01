<?php namespace Tests\MVC;

use Framework\Debug\ExceptionHandler;
use Framework\MVC\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
	protected Config $config;

	protected function setUp() : void
	{
		$this->config = new Config(__DIR__ . '/configs');
	}

	public function testLoadException()
	{
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Config file not found: bar');
		$this->config->load('bar');
	}

	public function testSetDirException()
	{
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Config directory not found: ' . __DIR__ . '/unknown');
		(new Config(__DIR__ . '/unknown'));
	}

	public function testGetAll()
	{
		$this->assertEquals([], $this->config->getAll());
		$this->config->load('console');
		$this->assertEquals([
			'console' => [
				'default' => [
					'enabled' => false,
				],
			],
		], $this->config->getAll());
		$this->config->load('exceptions');
		$this->assertEquals([
			'console' => [
				'default' => [
					'enabled' => false,
				],
			],
			'exceptions' => [
				'default' => [
					'environment' => ExceptionHandler::ENV_PROD,
					'views_dir' => null,
					'log' => true,
				],
			],
		], $this->config->getAll());
	}

	public function testAdd()
	{
		$this->config->add('foo', ['baz']);
		$this->assertEquals(['baz'], $this->config->get('foo'));
		$this->config->set('foo', ['bar']);
		$this->assertEquals(['bar'], $this->config->get('foo'));
		$this->config->add('foo', ['baz', 'hi']);
		$this->assertEquals(['baz', 'hi'], $this->config->get('foo'));
	}
}
