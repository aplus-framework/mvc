<?php

use Framework\MVC\App;

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
	function esc(?string $text, string $encoding = 'UTF-8') : string
	{
		return App::view()->escape($text, $encoding);
	}
}
if ( ! function_exists('normalize_whitespaces')) {
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
	function current_url() : string
	{
		return App::request()->getURL();
	}
}
if ( ! function_exists('current_route')) {
	function current_route() : Framework\Routing\Route
	{
		return App::router()->getMatchedRoute();
	}
}
if ( ! function_exists('route_url')) {
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
	 * @param string         $line
	 * @param array|string[] $args
	 * @param string|null    $locale
	 *
	 * @return string|null
	 */
	function lang(string $line, $args = [], string $locale = null) : ?string
	{
		return App::language()->lang($line, $args, $locale);
	}
}
if ( ! function_exists('cache')) {
	function cache(string $instance = 'default') : Framework\Cache\Cache
	{
		return App::cache($instance);
	}
}
if ( ! function_exists('session')) {
	function session() : Framework\Session\Session
	{
		return App::session();
	}
}
if ( ! function_exists('old')) {
	function old(string $key = null, bool $escape = true)
	{
		session();
		return $escape
			? esc(App::request()->getRedirectData($key))
			: App::request()->getRedirectData($key);
	}
}
if ( ! function_exists('csrf_input')) {
	function csrf_input() : string
	{
		return App::csrf()->input();
	}
}
