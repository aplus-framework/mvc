<?php namespace Tests\MVC;

use Framework\MVC\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
	protected Config $config;

	protected function setUp() : void
	{
		$this->config = new Config(__DIR__ . '/configs');
	}

	public function testLoad()
	{
		$this->assertNull($this->config->get('foo'));
		$this->config->load('foo');
		$this->assertEquals(['host' => 'localhost'], $this->config->get('foo'));
		$this->assertEquals(['host' => 'foo'], $this->config->get('foo', 'other'));
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
		$this->config->load('foo');
		$this->assertEquals([
			'foo' => [
				'default' => [
					'host' => 'localhost',
				],
				'custom' => [
					'host' => 'mysql.domain.tld',
				],
				'other' => [
					'host' => 'foo',
				],
			],
		], $this->config->getAll());
	}

	public function testAdd()
	{
		$this->assertNull($this->config->get('foo'));
		$this->config->add('foo', ['baz']);
		$this->assertEquals(['baz'], $this->config->get('foo'));
		$this->config->set('foo', ['bar']);
		$this->assertEquals(['bar'], $this->config->get('foo'));
		$this->config->add('foo', ['baz', 'hi']);
		$this->assertEquals(['baz', 'hi'], $this->config->get('foo'));
	}
}
