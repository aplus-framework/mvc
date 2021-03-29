<?php namespace Framework\MVC;

use Framework\Autoload\Autoloader;
use Framework\Autoload\Locator;
use Framework\Cache\Cache;
use Framework\CLI\Command;
use Framework\CLI\Console;
use Framework\Database\Database;
use Framework\Debug\Exceptions;
use Framework\Email\Mailer;
use Framework\Email\SMTP;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Language\Language;
use Framework\Log\Logger;
use Framework\Routing\Router;
use Framework\Session\Session;
use Framework\Validation\Validation;

class App
{
	public const DEBUG = false;
	/**
	 * @var array|array[]
	 */
	protected static array $configs = [];
	protected static array $services = [];
	protected static bool $isRunning = false;

	/**
	 * @return array|array[]
	 */
	public static function getConfigs() : array
	{
		return static::$configs;
	}

	/**
	 * @param string $name
	 * @param string $instance
	 *
	 * @return array|null
	 */
	public static function getConfig(string $name, string $instance = 'default') : ?array
	{
		return static::$configs[$name][$instance] ?? null;
	}

	/**
	 * @param string $name
	 * @param array  $config
	 * @param string $instance
	 *
	 * @return array
	 */
	public static function setConfig(
		string $name,
		array $config,
		string $instance = 'default'
	) : array {
		return static::$configs[$name][$instance] = $config;
	}

	/**
	 * @param array|array[] $configs
	 */
	public static function setConfigs(array $configs) : void
	{
		foreach ($configs as $name => $values) {
			foreach ($values as $instance => $config) {
				static::setConfig($name, $config, $instance);
			}
		}
	}

	/**
	 * @param string $name
	 * @param array  $config
	 * @param string $instance
	 *
	 * @return array
	 */
	public static function addConfig(
		string $name,
		array $config,
		string $instance = 'default'
	) : array {
		if (isset(static::$configs[$name][$instance])) {
			return static::$configs[$name][$instance] = \array_replace_recursive(
				static::$configs[$name][$instance],
				$config
			);
		}
		return static::$configs[$name][$instance] = $config;
	}

	/**
	 * Get a service.
	 *
	 * @param string $name
	 * @param string $instance
	 *
	 * @return mixed|null
	 */
	public static function getService(string $name, string $instance = 'default')
	{
		return static::$services[$name][$instance] ?? null;
	}

	/**
	 * Set a service.
	 *
	 * @param string $name
	 * @param mixed  $service
	 * @param string $instance
	 *
	 * @return mixed
	 */
	public static function setService(string $name, $service, string $instance = 'default')
	{
		return static::$services[$name][$instance] = $service;
	}

	/**
	 * Get the Autoloader service.
	 *
	 * @return Autoloader
	 */
	public static function autoloader() : Autoloader
	{
		$service = static::getService('autoloader');
		if ($service) {
			return $service;
		}
		$service = new Autoloader();
		$config = static::getConfig('autoloader');
		if (isset($config['namespaces'])) {
			$service->setNamespaces($config['namespaces']);
		}
		if (isset($config['classes'])) {
			$service->setClasses($config['classes']);
		}
		return static::setService('autoloader', $service);
	}

	/**
	 * Get a Cache service.
	 *
	 * @param string $instance
	 *
	 * @return Cache
	 */
	public static function cache(string $instance = 'default') : Cache
	{
		$service = static::getService('cache', $instance);
		if ($service) {
			return $service;
		}
		$config = static::getConfig('cache', $instance);
		if ( ! \str_contains($config['driver'], '\\')) {
			$config['driver'] = \ucfirst($config['driver']);
			$config['driver'] = "Framework\\Cache\\{$config['driver']}";
		}
		$service = new $config['driver'](
			$config['configs'],
			$config['prefix'],
			$config['serializer']
		);
		return static::setService('cache', $service, $instance);
	}

	/**
	 * Get the Console service.
	 *
	 * @throws \ReflectionException
	 *
	 * @return Console
	 */
	public static function console() : Console
	{
		$service = static::getService('console');
		if ($service) {
			return $service;
		}
		$service = new Console(static::language());
		$files = static::locator()->getFiles('Commands');
		foreach ($files as $file) {
			$className = static::locator()->getClassName($file);
			if ($className === false) {
				continue;
			}
			$class = new \ReflectionClass($className);
			if ( ! $class->isInstantiable() || ! $class->isSubclassOf(Command::class)) {
				continue;
			}
			$service->addCommand(new $className($service));
			unset($class);
		}
		return static::setService('console', $service);
	}

	/**
	 * Get a Database service.
	 *
	 * @param string $instance
	 *
	 * @return Database
	 */
	public static function database(string $instance = 'default') : Database
	{
		return static::getService('database', $instance)
			?? static::setService(
				'database',
				new Database(static::getConfig('database', $instance)),
				$instance
			);
	}

	/**
	 * Get a Mailer service.
	 *
	 * @param string $instance
	 *
	 * @return Mailer
	 */
	public static function mailer(string $instance = 'default') : Mailer
	{
		$service = static::getService('mailer', $instance);
		if ($service) {
			return $service;
		}
		$config = static::getConfig('mailer', $instance);
		if (empty($config['class'])) {
			$config['class'] = SMTP::class;
		}
		return static::setService(
			'mailer',
			new $config['class']($config),
			$instance
		);
	}

	/**
	 * Get the Language service.
	 *
	 * @return Language
	 */
	public static function language() : Language
	{
		$service = static::getService('language');
		if ($service) {
			return $service;
		}
		$config = static::getConfig('language');
		$service = new Language($config['default'] ?? 'en');
		if (isset($config['supported'])) {
			$service->setSupportedLocales($config['supported']);
		}
		if (isset($config['negotiate'])
			&& $config['negotiate'] === true
			&& ! static::isCLI()
		) {
			$service->setCurrentLocale(
				static::request()->negotiateLanguage(
					$service->getSupportedLocales()
				)
			);
		}
		if (isset($config['fallback_level'])) {
			$service->setFallbackLevel($config['fallback_level']);
		}
		if (isset($config['directories'])) {
			$service->setDirectories($config['directories']);
		} else {
			$directories = [];
			foreach (static::autoloader()->getNamespaces() as $directory) {
				$directory = "{$directory}Languages";
				if (\is_dir($directory)) {
					$directories[] = $directory;
				}
			}
			if ($directories) {
				$service->setDirectories($directories);
			}
		}
		return static::setService('language', $service);
	}

	/**
	 * Get the Locator service.
	 *
	 * @return Locator
	 */
	public static function locator() : Locator
	{
		return static::getService('locator')
			?? static::setService(
				'locator',
				new Locator(static::autoloader())
			);
	}

	/**
	 * Get the Logger service.
	 *
	 * @return Logger
	 */
	public static function logger() : Logger
	{
		$service = static::getService('logger');
		if ($service) {
			return $service;
		}
		$config = static::getConfig('logger');
		return static::setService(
			'logger',
			new Logger($config['directory'], $config['level'])
		);
	}

	/**
	 * Get the Router service.
	 *
	 * @return Router
	 */
	public static function router() : Router
	{
		return static::getService('router')
			?? static::setService('router', new Router());
	}

	/**
	 * Get the Request service.
	 *
	 * @return Request
	 */
	public static function request() : Request
	{
		return static::getService('request')
			?? static::setService('request', new Request());
	}

	/**
	 * Get the Response service.
	 *
	 * @return Response
	 */
	public static function response() : Response
	{
		return static::getService('response')
			?? static::setService('response', new Response(static::request()));
	}

	/**
	 * Get the Session service.
	 *
	 * @return Session
	 */
	public static function session() : Session
	{
		$service = static::getService('session');
		if ($service) {
			return $service;
		}
		$config = static::getConfig('session');
		$service = new Session($config['options'] ?? [], $config['save_handler'] ?? null);
		$service->start();
		return static::setService('session', $service);
	}

	/**
	 * Get a Validation service.
	 *
	 * @param string $instance
	 *
	 * @return Validation
	 */
	public static function validation(string $instance = 'default') : Validation
	{
		$service = static::getService('validation', $instance);
		if ($service) {
			return $service;
		}
		$config = static::getConfig('validation', $instance);
		return static::setService(
			'validation',
			new Validation($config['validators'] ?? null, static::language()),
			$instance
		);
	}

	/**
	 * Get a View service.
	 *
	 * @param string $instance
	 *
	 * @return View
	 */
	public static function view(string $instance = 'default') : View
	{
		$service = static::getService('view', $instance);
		if ($service) {
			return $service;
		}
		$service = new View();
		$config = static::getConfig('view', $instance);
		if (isset($config['base_path'])) {
			$service->setBasePath($config['base_path']);
		}
		if (isset($config['extension'])) {
			$service->setExtension($config['extension']);
		}
		return static::setService('view', $service, $instance);
	}

	public static function run() : void
	{
		if (static::$isRunning) {
			throw new \LogicException('App already is running');
		}
		static::$isRunning = true;
		require __DIR__ . '/helpers.php';
		\ob_start();
		static::prepareConfigs();
		$exceptions = (new Exceptions(
			static::logger(),
			static::language(),
			static::DEBUG ? Exceptions::ENV_DEV : Exceptions::ENV_PROD
		));
		if (isset(static::getConfig('exceptions')['viewsDir'])) {
			$exceptions->setViewsDir(static::getConfig('exceptions')['viewsDir']);
		}
		$exceptions->initialize(static::getConfig('exceptions')['clearBuffer']);
		static::autoloader();
		static::prepareRoutes();
		if (static::isCLI()) {
			static::console()->run();
			return;
		}
		$response = static::router()->match(
			static::request()->getMethod(),
			static::request()->getURL()
		)->run(static::request(), static::response());
		$response = static::makeResponseBodyPart($response);
		static::response()->appendBody($response)->send();
	}

	protected static function isCLI() : bool
	{
		static $is_cli;
		return $is_cli ?? $is_cli = (\PHP_SAPI === 'cli' || \defined('STDIN'));
	}

	/**
	 * @param string $instance
	 *
	 * @return array|string[]
	 */
	protected static function prepareConfigs(string $instance = 'default') : array
	{
		$files = static::getConfig('configs', $instance);
		if ( ! $files) {
			return [];
		}
		$files = \array_unique($files);
		foreach ($files as $file) {
			static::mergeFileConfigs($file);
		}
		return $files;
	}

	/**
	 * @param string $file
	 *
	 * @return array[]
	 */
	protected static function mergeFileConfigs(string $file) : array
	{
		if ( ! \is_file($file)) {
			throw new \RuntimeException(
				"Invalid config file path: {$file}"
			);
		}
		unset($config);
		require $file;
		if ( ! isset($config) || ! \is_array($config)) {
			throw new \LogicException(
				"Configs file must have a config array variable: {$file}"
			);
		}
		foreach ($config as $service => $instances) {
			if ( ! \is_array($instances)) {
				throw new \LogicException(
					"Config service name '{$service}' must be an array on file '{$file}'"
				);
			}
			foreach ($instances as $instance => $config) {
				if ( ! \is_array($config)) {
					throw new \LogicException(
						"Config instance name '{$instance}' of service name '{$service}' must be an array on file '{$file}'"
					);
				}
				static::addConfig($service, $config, $instance);
			}
		}
		return static::getConfigs();
	}

	/**
	 * @param string $instance
	 *
	 * @return array|string[]
	 */
	protected static function prepareRoutes(string $instance = 'default') : array
	{
		$files = static::getConfig('routes', $instance);
		if ( ! $files) {
			return [];
		}
		$files = \array_unique($files);
		foreach ($files as $file) {
			require $file;
		}
		return $files;
	}

	/**
	 * @param mixed $response Scalar or null data returned in a matched route
	 *
	 * @see \Framework\Routing\Route::run()
	 *
	 * @return string
	 */
	protected static function makeResponseBodyPart($response) : string
	{
		if ($response === null || $response instanceof Response) {
			return '';
		}
		if (\is_scalar($response)) {
			return $response;
		}
		if (\is_object($response) && \method_exists($response, '__toString')) {
			return $response;
		}
		$type = \gettype($response);
		if ($type === 'object') {
			$type = \get_class($response);
		}
		$action = static::router()->getMatchedRoute()->getAction();
		if ($action instanceof \Closure) {
			$action = '{closure}';
		}
		throw new \LogicException("Invalid return type '{$type}' on matched route '{$action}'");
	}
}
