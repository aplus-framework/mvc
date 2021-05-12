<?php namespace Tests\MVC;

use Framework\Cache\Cache;
use Framework\MVC\Config;
use Framework\Session\Session;
use PHPUnit\Framework\TestCase;
use Tests\MVC\AppMock as App;

class HelpersTest extends TestCase
{
	protected function setUp() : void
	{
		App::init(new Config(__DIR__ . '/configs'));
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

	public function testCurrentUrl()
	{
		$this->assertEquals('http://localhost:8080/contact', current_url());
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

	/**
	 * @runInSeparateProcess
	 */
	public function testOld()
	{
		App::session();
		App::response()->redirect('http://localhost', ['foo' => ['bar']]);
		$this->assertEquals('bar', old('foo[0]'));
		$this->assertEquals(['bar'], old('foo', false));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCsrfInput()
	{
		$this->assertStringStartsWith('<input type="hidden" name="', csrf_input());
	}
}
