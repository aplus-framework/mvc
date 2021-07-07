<?php declare(strict_types=1);

/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Framework\Cache\Cache;
use Framework\HTTP\Response;
use Framework\MVC\App;
use Framework\Routing\Route;
use Framework\Session\Session;
use JetBrains\PhpStorm\Pure;

if ( ! function_exists('helpers')) {
	/**
	 * Loads helper files.
	 *
	 * @param array<int,string>|string $helper A list of helper names as array
	 * or a helper name as string
	 *
	 * @return array<int,string> A list of all loaded files
	 */
	function helpers(array | string $helper) : array
	{
		if (is_array($helper)) {
			$files = [];
			foreach ($helper as $item) {
				$files[] = helpers($item);
			}
			return array_merge(...$files);
		}
		$files = App::locator()->findFiles("Helpers/{$helper}");
		foreach ($files as $file) {
			require_once $file;
		}
		return $files;
	}
}
if ( ! function_exists('esc')) {
	/**
	 * Escape special characters to HTML entities.
	 *
	 * @param string|null $text The text to be escaped
	 * @param string $encoding The escaped text encoding
	 *
	 * @return string The escaped text
	 */
	#[Pure]
	function esc(
		?string $text,
		string $encoding = 'UTF-8'
	) : string {
		$text = (string) $text;
		return empty($text)
			? $text
			: htmlspecialchars($text, \ENT_QUOTES | \ENT_HTML5, $encoding);
	}
}
if ( ! function_exists('normalize_whitespaces')) {
	/**
	 * Normalize string whitespaces.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function normalize_whitespaces(string $string) : string
	{
		return trim(preg_replace('/\s+/', ' ', $string));
	}
}
if ( ! function_exists('is_cli')) {
	/**
	 * Indicates if the current request is a command line request.
	 *
	 * @return bool TRUE if is a CLI request, otherwise FALSE
	 */
	function is_cli() : bool
	{
		return App::isCLI();
	}
}
if ( ! function_exists('view')) {
	/**
	 * Renders a view.
	 *
	 * @param string $path View path
	 * @param array<string,mixed> $variables Variables passed to the view
	 * @param string $instance The View instance name
	 *
	 * @return string The rendered view contents
	 */
	function view(string $path, array $variables = [], string $instance = 'default') : string
	{
		return App::view($instance)->render($path, $variables);
	}
}
if ( ! function_exists('current_url')) {
	/**
	 * Get the current URL.
	 *
	 * @return string
	 */
	function current_url() : string
	{
		return App::request()->getURL()->getAsString();
	}
}
if ( ! function_exists('current_route')) {
	/**
	 * Get the current Route.
	 *
	 * @return Route
	 */
	function current_route() : Route
	{
		return App::router()->getMatchedRoute();
	}
}
if ( ! function_exists('route_url')) {
	/**
	 * Get an URL based in a Route name.
	 *
	 * @param string $name Route name
	 * @param array<int,string> $pathArgs Route path arguments
	 * @param array<int,string> $originArgs Route origin arguments
	 *
	 * @return string The Route URL
	 */
	function route_url(string $name, array $pathArgs = [], array $originArgs = []) : string
	{
		$route = App::router()->getNamedRoute($name);
		$matched = App::router()->getMatchedRoute();
		if (empty($originArgs)
			&& $matched && $route->getOrigin() === $matched->getOrigin()
		) {
			$originArgs = App::router()->getMatchedOriginArguments();
		}
		return $route->getURL($originArgs, $pathArgs);
	}
}
if ( ! function_exists('lang')) {
	/**
	 * Renders a language file line with dot notation format.
	 *
	 * e.g. home.hello matches 'home' for file and 'hello' for line.
	 *
	 * @param string $line The dot notation file line
	 * @param array<int|string,string> $args The arguments to be used in the
	 * formatted text
	 * @param string|null $locale A custom locale or null to use the current
	 *
	 * @return string|null The rendered text or null if not found
	 */
	function lang(string $line, array $args = [], string $locale = null) : ?string
	{
		return App::language()->lang($line, $args, $locale);
	}
}
if ( ! function_exists('cache')) {
	/**
	 * Get a Cache instance.
	 *
	 * @param string $instance
	 *
	 * @return Cache
	 */
	function cache(string $instance = 'default') : Cache
	{
		return App::cache($instance);
	}
}
if ( ! function_exists('session')) {
	/**
	 * Get the Session instance.
	 *
	 * @return Session
	 */
	function session() : Session
	{
		return App::session();
	}
}
if ( ! function_exists('old')) {
	/**
	 * Get data from old redirect.
	 *
	 * @param string|null $key Set null to return all data
	 * @param bool $escape
	 *
	 * @see Request::getRedirectData
	 * @see Response::redirect
	 * @see redirect()
	 *
	 * @return mixed The old value. If $escape is true and the value is not
	 * stringable, an empty string will return
	 */
	function old(?string $key, bool $escape = true) : mixed
	{
		session();
		$data = App::request()->getRedirectData($key);
		if ($escape) {
			$data = is_scalar($data) || (is_object($data) && method_exists($data, '__toString'))
				? esc((string) $data)
				: '';
		}
		return $data;
	}
}
if ( ! function_exists('csrf_input')) {
	function csrf_input() : string
	{
		return App::csrf()->input();
	}
}
if ( ! function_exists('not_found')) {
	/**
	 * Set Response status line as "404 Not Found" and auto set body as
	 * JSON or HTML page based on Request Content-Type header.
	 *
	 * @param array<string,mixed> $data
	 *
	 * @return Response
	 */
	function not_found(array $data = []) : Response
	{
		$response = App::response();
		$response->setStatusLine($response::CODE_NOT_FOUND);
		if (App::request()->isJSON()) {
			return App::response()->setJSON([
				'error' => [
					'code' => $response::CODE_NOT_FOUND,
					'reason' => $response::getResponseReason(
						$response::CODE_NOT_FOUND
					),
				],
			]);
		}
		$lang = App::language()->getCurrentLocale();
		$data['title'] ??= lang('errors.notFoundTitle');
		$data['message'] ??= lang('errors.notFoundMessage');
		return $response->setBody(
			<<<EOL
				<!doctype html>
				<html lang="{$lang}">
				<head>
					<meta charset="utf-8">
					<title>{$data['title']}</title>
				</head>
				<body>
				<h1>{$data['title']}</h1>
				<p>{$data['message']}</p>
				</body>
				</html>

				EOL
		);
	}
}
if ( ! function_exists('redirect')) {
	/**
	 * Sets the HTTP Redirect Response with data accessible in the next HTTP
	 * Request.
	 *
	 * @param string $location Location Header value
	 * @param array<int|string,mixed> $data Session data available on next
	 * Request
	 * @param int|null $code HTTP Redirect status code. Leave null to determine
	 * based on the current HTTP method.
	 *
	 * @see http://en.wikipedia.org/wiki/Post/Redirect/Get
	 * @see Request::getRedirectData
	 * @see old()
	 *
	 * @throws InvalidArgumentException for invalid Redirection code
	 *
	 * @return Response
	 */
	function redirect(
		string $location,
		array $data = [],
		int $code = null
	) : Response {
		session();
		return App::response()->redirect($location, $data, $code);
	}
}
