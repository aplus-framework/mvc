<?php

use Framework\MVC\App;
use JetBrains\PhpStorm\Pure;

if ( ! function_exists('helpers')) {
	/**
	 * Loads helper files.
	 *
	 * @param array|string|string[] $helper
	 *
	 * @return array|string[] A list of all loaded files
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
	 * @param string|null $text     The text to be escaped
	 * @param string      $encoding
	 *
	 * @return string The escaped text
	 */
	#[Pure]
	function esc(?string $text, string $encoding = 'UTF-8') : string
	{
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
	 * @param string $path     View path
	 * @param array  $data     Data passed to the view
	 * @param string $instance
	 *
	 * @return string The rendered view contents
	 */
	function view(string $path, array $data = [], string $instance = 'default') : string
	{
		return App::view($instance)->render($path, $data);
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
		return App::request()->getURL();
	}
}
if ( ! function_exists('current_route')) {
	/**
	 * Get the current Route.
	 *
	 * @return \Framework\Routing\Route
	 */
	function current_route() : Framework\Routing\Route
	{
		return App::router()->getMatchedRoute();
	}
}
if ( ! function_exists('route_url')) {
	/**
	 * Get an URL based in a Route name.
	 *
	 * @param string $name          Route name
	 * @param array  $path_params   Route path parameters
	 * @param array  $origin_params Route origin parameters
	 *
	 * @return string The URL
	 */
	function route_url(string $name, array $path_params = [], array $origin_params = []) : string
	{
		$route = App::router()->getNamedRoute($name);
		$matched = App::router()->getMatchedRoute();
		if (empty($origin_params)
			&& $matched && $route->getOrigin() === $matched->getOrigin()
		) {
			$origin_params = App::router()->getMatchedOriginParams();
		}
		return $route->getURL($origin_params, $path_params);
	}
}
if ( ! function_exists('lang')) {
	/**
	 * Renders a language file line with dot notation format.
	 *
	 * e.g. home.hello matches home for file and hello for line.
	 *
	 * @param string         $line   The dot notation file line
	 * @param array|string[] $args   The arguments to be used in the formatted text
	 * @param string|null    $locale A custom locale or null to use the current
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
	 * @return \Framework\Cache\Cache
	 */
	function cache(string $instance = 'default') : Framework\Cache\Cache
	{
		return App::cache($instance);
	}
}
if ( ! function_exists('session')) {
	/**
	 * Get the Session instance.
	 *
	 * @return \Framework\Session\Session
	 */
	function session() : Framework\Session\Session
	{
		return App::session();
	}
}
if ( ! function_exists('old')) {
	/**
	 * Get data from old redirect.
	 *
	 * @param string|null $key    Set null to return all data
	 * @param bool        $escape
	 *
	 * @see \Framework\HTTP\Request::getRedirectData
	 * @see \Framework\HTTP\Response::redirect
	 *
	 * @return mixed The old value. If $escape is true and the value is not
	 *               stringable, an empty string will return
	 */
	function old(?string $key, bool $escape = true) : mixed
	{
		session();
		$data = App::request()->getRedirectData($key);
		if ($escape) {
			if (is_scalar($data) || (is_object($data) && method_exists($data, '__toString'))) {
				$data = esc((string) $data);
			} else {
				$data = '';
			}
		}
		return $data;
	}
}
if ( ! function_exists('csrf_input')) {
	function csrf_input() : string
	{
		$config = App::config()->get('csrf');
		if (empty($config['enabled'])) {
			return '';
		}
		return App::csrf()->input();
	}
}
if ( ! function_exists('not_found')) {
	/**
	 * Set Response status line as "404 Not Found" and auto set body as
	 * JSON or HTML page based on Request Content-Type header.
	 *
	 * @param array $data
	 *
	 * @return \Framework\HTTP\Response
	 */
	function not_found(array $data = []) : Framework\HTTP\Response
	{
		App::response()->setStatusLine(404);
		if (App::request()->isJSON()) {
			return App::response()->setJSON([
				'error' => [
					'code' => 404,
					'reason' => 'Not Found',
				],
			]);
		}
		$lang = App::language()->getCurrentLocale();
		$data['title'] ??= lang('errors.notFoundTitle');
		$data['message'] ??= lang('errors.notFoundMessage');
		return App::response()->setBody(
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
