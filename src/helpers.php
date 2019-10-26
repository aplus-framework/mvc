<?php
if ( ! function_exists('helpers')) {
	/**
	 * Loads helper files.
	 *
	 * @param array|string $helper
	 *
	 * @return array A list of all loaded files
	 */
	function helpers($helper) : array
	{
		if (is_array($helper)) {
			$files = [];
			foreach ($helper as $item) {
				$files[] = helpers($item);
			}
			return array_merge(...$files);
		}
		$files = App::getLocator()->findFiles("Helpers/{$helper}");
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
		$text = (string) $text;
		return empty($text)
			? $text
			: htmlspecialchars($text, \ENT_QUOTES | \ENT_HTML5, $encoding);
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
		return \PHP_SAPI === 'cli' || defined('STDIN');
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
		return App::getView($instance)->render($path, $data);
	}
}
if ( ! function_exists('current_url')) {
	function current_url() : string
	{
		return App::getRequest()->getURL();
	}
}
if ( ! function_exists('current_route')) {
	function current_route() : Framework\Routing\Route
	{
		return App::getRouter()->getMatchedRoute();
	}
}
if ( ! function_exists('route_url')) {
	function route_url(string $name, array $path_params = [], array $origin_params = []) : string
	{
		$route = App::getRouter()->getNamedRoute($name);
		if ($route === null) {
			throw new OutOfBoundsException("Named route not found: {$name}");
		}
		if (empty($origin_params)
			&& $route->getOrigin() === App::getRouter()->getMatchedRoute()->getOrigin()
		) {
			$origin_params = App::getRouter()->getMatchedOriginParams();
		}
		return $route->getURL($origin_params, $path_params);
	}
}
if ( ! function_exists('lang')) {
	function lang(string $line, $args = [], string $locale = null) : ?string
	{
		return App::getLanguage()->lang($line, $args, $locale);
	}
}
if ( ! function_exists('cache')) {
	function cache(string $instance = 'default') : Framework\Cache\Cache
	{
		return App::getCache($instance);
	}
}
if ( ! function_exists('session')) {
	function session() : Framework\Session\Session
	{
		return App::getSession();
	}
}
if ( ! function_exists('old')) {
	function old(string $key = null, bool $escape = true)
	{
		session();
		return $escape
			? esc(App::getRequest()->getRedirectData($key))
			: App::getRequest()->getRedirectData($key);
	}
}
