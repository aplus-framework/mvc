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

	public function testCurrentUrl()
	{
		$this->assertEquals('http://localhost:8080/contact', current_url());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCurrentRoute()
	{
		App::setIsCLI(false);
		App::run();
		$this->assertEquals('contact', current_route()->getName());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testRouteUrl()
	{
		App::setIsCLI(false);
		App::run();
		$this->assertEquals('http://localhost:8080/users', route_url('users'));
		$this->assertEquals('http://localhost:8080/users/25', route_url('users.show', [25]));
		$this->assertEquals(
			'http://blog-1.domain.tld/posts/hello-world',
			route_url('sub.posts', ['hello-world'], ['blog-1'])
		);
	}

	public function testIsCli()
	{
		$this->assertTrue(is_cli());
		App::setIsCLI(false);
		$this->assertFalse(is_cli());
	}

	public function testEsc()
	{
		$this->assertEquals('&gt;&apos;&quot;', esc('>\'"'));
		$this->assertEquals('', esc(null));
	}

	public function testNormalizeWhitespaces()
	{
		$this->assertEquals('foo bar', normalize_whitespaces(' foo    bar '));
	}

	public function testHelpers()
	{
		$this->assertFalse(\function_exists('foo'));
		$this->assertEquals([__DIR__ . '/Helpers/foo.php'], helpers(['foo']));
		$this->assertTrue(\function_exists('foo'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOld()
	{
		App::session();
		$data = [
			'foo' => ['bar'],
			'baz' => new class() {
				public function __toString()
				{
					return 'bazz';
				}
			},
		];
		App::response()->redirect('http://localhost', $data);
		$this->assertEquals('', old(null));
		$this->assertEquals($data, old(null, false));
		$this->assertEquals('bar', old('foo[0]'));
		$this->assertEquals('bar', old('foo[0]', false));
		$this->assertEquals('', old('foo'));
		$this->assertEquals(['bar'], old('foo', false));
		$this->assertEquals('bazz', old('baz'));
		$this->assertEquals($data['baz'], old('baz', false));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCsrfInput()
	{
		$this->assertStringStartsWith('<input type="hidden" name="', csrf_input());
	}

	public function testNotFoundAsHTML()
	{
		App::language()->addDirectory(__DIR__ . '/../src/Languages');
		$response = not_found();
		$this->assertStringContainsString('<p>Page not found</p>', $response->getBody());
		$this->assertStringContainsString('<html lang="en">', $response->getBody());
	}

	public function testNotFoundAsHTMLWithCustomLanguage()
	{
		App::language()->addDirectory(__DIR__ . '/../src/Languages');
		App::language()->setCurrentLocale('pt-br');
		$response = not_found();
		$this->assertStringContainsString('<p>Página não encontrada</p>', $response->getBody());
		$this->assertStringContainsString('<html lang="pt-br">', $response->getBody());
	}

	public function testNotFoundAsHTMLWithCustomData()
	{
		$response = not_found([
			'title' => 'Foo',
			'message' => 'Bar',
		]);
		$this->assertStringContainsString('<h1>Foo</h1>', $response->getBody());
		$this->assertStringContainsString('<p>Bar</p>', $response->getBody());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testNotFoundAsJSON()
	{
		$_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
		$response = not_found();
		$this->assertEquals([
			'error' => [
				'code' => 404,
				'reason' => 'Not Found',
			],
		], \json_decode($response->getBody(), true));
	}
}
