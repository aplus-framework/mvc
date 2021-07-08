<?php
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\MVC;

use Framework\Cache\Cache;
use Framework\Config\Config;
use Framework\HTTP\Response;
use Framework\Session\Session;
use PHPUnit\Framework\TestCase;
use Tests\MVC\AppMock as App;

/**
 * Class HelpersTest.
 *
 * @runTestsInSeparateProcesses
 */
final class HelpersTest extends TestCase
{
	protected AppMock $app;

	protected function setUp() : void
	{
		$this->app = new App(new Config(__DIR__ . '/configs', [], '.config.php'));
		$this->app->loadHelpers();
	}

	public function testCache() : void
	{
		self::assertInstanceOf(Cache::class, cache());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSession() : void
	{
		self::assertInstanceOf(Session::class, session());
	}

	public function testLang() : void
	{
		self::assertSame('foo.bar', lang('foo.bar'));
		App::language()->addLines('en', 'foo', ['bar' => 'Hello!']);
		self::assertSame('Hello!', lang('foo.bar'));
	}

	public function testView() : void
	{
		App::view()->setBaseDir(__DIR__ . '/Views');
		self::assertSame("<div>bar</div>\n", view('foo', ['contents' => 'bar']));
	}

	public function testCurrentUrl() : void
	{
		self::assertSame('http://localhost:8080/contact', current_url());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCurrentRoute() : void
	{
		App::setIsCLI(false);
		$this->app->run();
		self::assertSame('contact', current_route()->getName());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testRouteUrl() : void
	{
		App::setIsCLI(false);
		$this->app->run();
		self::assertSame('http://localhost:8080/users', route_url('users'));
		self::assertSame('http://localhost:8080/users/25', route_url('users.show', [25]));
		self::assertSame(
			'http://blog-1.domain.tld/posts/hello-world',
			route_url('sub.posts', ['hello-world'], ['blog-1'])
		);
	}

	public function testIsCli() : void
	{
		self::assertTrue(is_cli());
		App::setIsCLI(false);
		self::assertFalse(is_cli());
	}

	public function testEsc() : void
	{
		self::assertSame('&gt;&apos;&quot;', esc('>\'"'));
		self::assertSame('', esc(null));
	}

	public function testNormalizeWhitespaces() : void
	{
		self::assertSame('foo bar', normalize_whitespaces(' foo    bar '));
	}

	public function testHelpers() : void
	{
		self::assertFalse(\function_exists('foo'));
		self::assertSame([__DIR__ . '/Helpers/foo.php'], helpers(['foo']));
		self::assertTrue(\function_exists('foo'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOld() : void
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
		self::assertSame('', old(null));
		self::assertSame($data, old(null, false));
		self::assertSame('bar', old('foo[0]'));
		self::assertSame('bar', old('foo[0]', false));
		self::assertSame('', old('foo'));
		self::assertSame(['bar'], old('foo', false));
		self::assertSame('bazz', old('baz'));
		self::assertSame($data['baz'], old('baz', false));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCsrfInput() : void
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';
		self::assertStringStartsWith('<input type="hidden" name="', csrf_input());
		self::assertFalse(App::csrf()->verify());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCsrfInputDisabled() : void
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';
		App::config()->load('csrf');
		App::config()->add('csrf', ['enabled' => false]);
		self::assertSame('', csrf_input());
		self::assertTrue(App::csrf()->verify());
	}

	public function testNotFoundAsHTML() : void
	{
		$response = not_found();
		self::assertStringContainsString('<p>Page not found</p>', $response->getBody());
		self::assertStringContainsString('<html lang="en">', $response->getBody());
	}

	public function testNotFoundAsHTMLWithCustomLanguage() : void
	{
		App::language()->setCurrentLocale('pt-br');
		$response = not_found();
		self::assertStringContainsString('<p>Página não encontrada</p>', $response->getBody());
		self::assertStringContainsString('<html lang="pt-br">', $response->getBody());
	}

	public function testNotFoundAsHTMLWithCustomData() : void
	{
		$response = not_found([
			'title' => 'Foo',
			'message' => 'Bar',
		]);
		self::assertStringContainsString('<h1>Foo</h1>', $response->getBody());
		self::assertStringContainsString('<p>Bar</p>', $response->getBody());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testNotFoundAsJSON() : void
	{
		$_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
		$response = not_found();
		self::assertSame([
			'error' => [
				'code' => 404,
				'reason' => 'Not Found',
			],
		], \json_decode($response->getBody(), true));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testRedirect() : void
	{
		self::assertSame(200, App::response()->getStatusCode());
		self::assertNull(App::response()->getHeader('Location'));
		self::assertInstanceOf(Response::class, redirect('http://localhost'));
		self::assertSame(307, App::response()->getStatusCode());
		self::assertSame('http://localhost', App::response()->getHeader('Location'));
	}
}
