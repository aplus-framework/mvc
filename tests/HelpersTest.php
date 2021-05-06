<?php namespace Tests\MVC;

use Framework\Cache\Cache;
use Framework\Session\Session;
use PHPUnit\Framework\TestCase;
use Tests\MVC\AppMock as App;

class HelpersTest extends TestCase
{
	protected function setUp() : void
	{
		$config = [];
		require __DIR__ . '/../src/configs.php';
		App::setConfigs($config);
		App::loadHelpers();
	}

	public function testCache()
	{
		$this->assertInstanceOf(Cache::class, cache());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSession()
	{
		$this->assertInstanceOf(Session::class, session());
	}

	public function testLang()
	{
		$this->assertEquals('foo.bar', lang('foo.bar'));
		App::language()->addLines('en', 'foo', ['bar' => 'Hello!']);
		$this->assertEquals('Hello!', lang('foo.bar'));
	}

	public function testView()
	{
		App::view()->setBasePath(__DIR__ . '/Views');
		$this->assertEquals("<div>bar</div>\n", view('foo', ['contents' => 'bar']));
	}

	public function testIsCli()
	{
		$this->assertTrue(is_cli());
	}

	public function testEsc()
	{
		$this->assertEquals('&gt;&apos;&quot;', esc('>\'"'));
	}

	public function testNormalizeWhitespaces()
	{
		$this->assertEquals('foo bar', normalize_whitespaces(' foo    bar '));
	}

	public function testHelpers()
	{
		$this->assertEquals([], helpers(['foo']));
		$this->assertFalse(\function_exists('foo'));
		App::autoloader()->setNamespace('Tests\MVC', __DIR__);
		$this->assertEquals([__DIR__ . '/Helpers/foo.php'], helpers(['foo']));
		$this->assertTrue(\function_exists('foo'));
	}
}
