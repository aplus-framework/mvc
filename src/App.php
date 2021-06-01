<?php namespace Framework\MVC;

use Framework\Autoload\Autoloader;
use Framework\Autoload\Locator;
use Framework\Cache\Cache;
use Framework\CLI\Command;
use Framework\CLI\Console;
use Framework\Database\Database;
use Framework\Debug\ExceptionHandler;
use Framework\Email\Mailer;
use Framework\Email\SMTP;
use Framework\HTTP\CSRF;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Language\Language;
use Framework\Log\Logger;
use Framework\Routing\Router;
use Framework\Session\Session;
use Framework\Validation\Validation;
use LogicException;

/**
 * Class App.
 */
class App
{
	protected static array $services = [];
	protected static bool $isRunning = false;
	protected static ?Config $config;
	protected static ?bool $isCLI = null;

	/**
	 * Initialize the App.
	 *
	 * @param Config $config
	 */
	public function __construct(Config $config)
	{
		if (isset(static::$config)) {
			throw new LogicException('App already initialized');
		}
		static::$config = $config;
	}

	protected function prepareExceptionHandler() : void
	{
		$environment = static::config()->get('exceptions')['environment']
			?? ExceptionHandler::ENV_PROD;
		$logger = static::config()->get('exceptions')['log'] ? static::logger() : null;
		$exceptions = new ExceptionHandler(
			$environment,
			$logger,
			static::language()
		);
		if (isset(static::config()->get('exceptions')['views_dir'])) {
			$exceptions->setViewsDir(static::config()->get('exceptions')['views_dir']);
		}
		$exceptions->initialize();
	}

	protected function loadHelpers() : void
	{
		require __DIR__ . '/helpers.php';
	}

	public function run() : void
	{
		if (static::$isRunning) {
			throw new LogicException('App already is running');
		}
		static::$isRunning = true;
		$this->loadHelpers();
		$this->prepareExceptionHandler();
		static::autoloader();
		$this->prepareRoutes();
		if (static::isCLI()) {
			if (empty(static::config()->get('console')['enabled'])) {
				return;
			}
			static::console()->run();
			return;
		}
		$response = static::router()->match(
			static::request()->getMethod(),
			static::request()->getURL()
		)->run(static::request(), static::response());
		$response = $this->makeResponseBodyPart($response);
		static::response()->appendBody($response)->send();
	}

	/**
	 * Load files to set routes.
	 *
	 * @param string $instance
	 */
	protected function prepareRoutes(string $instance = 'default') : void
	{
		$files = static::config()->get('routes', $instance);
		if ( ! $files) {
			return;
		}
		$files = \array_unique($files);
		foreach ($files as $file) {
			if ( ! \is_file($file)) {
				throw new LogicException('Invalid route file: ' . $file);
			}
			require $file;
		}
	}

	/**
	 * @param mixed $response Scalar or null data returned in a matched route
	 *
	 * @see \Framework\Routing\Route::run
	 *
	 * @return string
	 */
	protected function makeResponseBodyPart(mixed $response) : string
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
		if (\is_array($response) || $response instanceof \JsonSerializable) {
			static::response()->setJSON($response);
			return '';
		}
		$type = \get_debug_type($response);
		throw new LogicException("Invalid return type '{$type}' on matched route");
	}

	/**
	 * Get the Config instance.
	 *
	 * @return Config
	 */
	public static function config() : Config
	{
		return static::$config;
	}

	/**
	 * Get a service.
	 *
	 * @param string $name
	 * @param string $instance
	 *
	 * @return mixed The service instance or null
	 */
	public static function getService(string $name, string $instance = 'default') : mixed
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
	public static function setService(string $name, mixed $service, string $instance = 'default') : mixed
	{
		return static::$services[$name][$instance] = $service;
	}

	/**
	 * Remove a service.
	 *
	 * @param string      $name
	 * @param string|null $instance Instance name or null to remove all
	 */
	public static function removeService(string $name, ?string $instance) : void
	{
		if ($instance === null) {
			unset(static::$services[$name]);
			return;
		}
		unset(static::$services[$name][$instance]);
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
		$config = static::config()->get('autoloader');
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
		$config = static::config()->get('cache', $instance);
		$service = new $config['class'](
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
			if (empty($className)) {
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
	 * Get the CSRF service.
	 *
	 * @return CSRF
	 */
	public static function csrf() : CSRF
	{
		$service = static::getService('csrf');
		if ($service) {
			return $service;
		}
		static::session();
		return static::setService('csrf', new CSRF(static::request()));
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
				new Database(static::config()->get('database', $instance)),
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
		$config = static::config()->get('mailer', $instance);
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
		$config = static::config()->get('language');
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
		$service->addDirectory(__DIR__ . '/Languages');
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
		$config = static::config()->get('logger');
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
		$config = static::config()->get('session');
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
		$config = static::config()->get('validation', $instance);
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
		$config = static::config()->get('view', $instance);
		if (isset($config['base_path'])) {
			$service->setBasePath($config['base_path']);
		}
		if (isset($config['extension'])) {
			$service->setExtension($config['extension']);
		}
		return static::setService('view', $service, $instance);
	}

	/**
	 * Tell if is a command-line request.
	 *
	 * @return bool
	 */
	public static function isCLI() : bool
	{
		if (static::$isCLI === null) {
			static::$isCLI = \PHP_SAPI === 'cli' || \defined('STDIN');
		}
		return static::$isCLI;
	}

	/**
	 * Set if is a CLI request. Used for tests.
	 *
	 * @param bool $is
	 */
	public static function setIsCLI(bool $is) : void
	{
		static::$isCLI = $is;
	}
}
