<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC;

use Framework\Autoload\Autoloader;
use Framework\Autoload\Locator;
use Framework\Cache\Cache;
use Framework\Cache\Debug\CacheCollector;
use Framework\Cache\Serializer;
use Framework\CLI\Command;
use Framework\CLI\Console;
use Framework\Config\Config;
use Framework\Database\Database;
use Framework\Database\Debug\DatabaseCollector;
use Framework\Database\Extra\Migrator;
use Framework\Debug\Debugger;
use Framework\Debug\ExceptionHandler;
use Framework\Email\Debug\EmailCollector;
use Framework\Email\Mailer;
use Framework\Email\Mailers\SMTPMailer;
use Framework\Helpers\Isolation;
use Framework\HTTP\AntiCSRF;
use Framework\HTTP\Debug\HTTPCollector;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Language\Debug\LanguageCollector;
use Framework\Language\FallbackLevel;
use Framework\Language\Language;
use Framework\Log\Debug\LogCollector;
use Framework\Log\Logger;
use Framework\Log\Loggers\MultiFileLogger;
use Framework\Log\LogLevel;
use Framework\MVC\Debug\AppCollector;
use Framework\MVC\Debug\ViewCollector;
use Framework\Routing\Debug\RoutingCollector;
use Framework\Routing\Router;
use Framework\Session\Debug\SessionCollector;
use Framework\Session\SaveHandlers\DatabaseHandler;
use Framework\Session\Session;
use Framework\Validation\Debug\ValidationCollector;
use Framework\Validation\FilesValidator;
use Framework\Validation\Validation;
use LogicException;
use ReflectionClass;
use ReflectionException;

/**
 * Class App.
 *
 * @package mvc
 */
class App
{
    /**
     * Array with keys with names of services and their values have arrays where
     * the keys are the names of the instances and the values are the objects.
     *
     * @var array<string,array<string,object>>
     */
    protected static array $services = [];
    /**
     * Tells if the App is running.
     *
     * @var bool
     */
    protected static bool $isRunning = false;
    /**
     * The Config instance.
     *
     * @var Config|null
     */
    protected static ?Config $config;
    /**
     * Tells if the request is by command line. Updating directly makes it
     * possible to run tests simulating HTTP or CLI.
     *
     * @var bool|null
     */
    protected static ?bool $isCli = null;
    /**
     * The App collector instance that is set when in debug mode.
     *
     * @var AppCollector
     */
    protected static AppCollector $debugCollector;
    /**
     * Variables set in the $_SERVER super-global in command-line requests.
     *
     * @var array<string,mixed>
     */
    protected static array $defaultServerVars = [
        'REMOTE_ADDR' => '127.0.0.1',
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
        'HTTP_HOST' => 'localhost',
    ];

    /**
     * Initialize the App.
     *
     * @param array<string,mixed>|Config|string|null $config The config
     * @param bool $debug Set true to enable debug mode. False to disable.
     */
    public function __construct(Config | array | string $config = null, bool $debug = false)
    {
        if ($debug) {
            $this->debugStart();
        }
        if (isset(static::$config)) {
            throw new LogicException('App already initialized');
        }
        if ( ! $config instanceof Config) {
            $config = new Config($config);
        }
        static::$config = $config;
        if ($debug) {
            static::debugger()->addCollector(static::$debugCollector, 'App');
        }
    }

    /**
     * Start debugging the App.
     */
    protected function debugStart() : void
    {
        static::$debugCollector = new AppCollector();
        static::$debugCollector->setStartTime()->setStartMemory();
        static::$debugCollector->setApp($this);
    }

    /**
     * Load service configs catching exceptions.
     *
     * @param string $name The service name
     *
     * @return array<string,array<string,mixed>>|null The service configs or null
     */
    protected function loadConfigs(string $name) : array | null
    {
        $config = static::config();
        try {
            $config->load($name);
        } catch (\LogicException) {
        }
        return $config->getInstances($name);
    }

    /**
     * Make sure to load the autoloader service if its default config is set.
     */
    protected function loadAutoloader() : void
    {
        $config = static::config();
        $autoloaderConfigs = $config->getInstances('autoloader');
        if ($config->getDir() !== null) {
            $autoloaderConfigs ??= $this->loadConfigs('autoloader');
        }
        if (isset($autoloaderConfigs['default'])) {
            static::autoloader();
        }
    }

    /**
     * Make sure to load the exceptionHandler service if its default config is set.
     */
    protected function loadExceptionHandler() : void
    {
        $config = static::config();
        $exceptionHandlerConfigs = $config->getInstances('exceptionHandler');
        if ($config->getDir() !== null) {
            $exceptionHandlerConfigs ??= $this->loadConfigs('exceptionHandler');
        }
        if ( ! isset($exceptionHandlerConfigs['default'])) {
            $environment = static::isDebugging()
                ? ExceptionHandler::DEVELOPMENT
                : ExceptionHandler::PRODUCTION;
            $config->set('exceptionHandler', [
                'environment' => $environment,
            ]);
            $exceptionHandlerConfigs = $config->getInstances('exceptionHandler');
        }
        if (isset($exceptionHandlerConfigs['default'])) {
            static::exceptionHandler();
        }
    }

    /**
     * Prepare the App to run via CLI or HTTP.
     */
    protected function prepareToRun() : void
    {
        if (static::$isRunning) {
            throw new LogicException('App is already running');
        }
        static::$isRunning = true;
        $this->loadAutoloader();
        $this->loadExceptionHandler();
    }

    /**
     * Run the App on HTTP requests.
     */
    public function runHttp() : void
    {
        $this->prepareToRun();
        $router = static::router();
        $response = $router->getResponse();
        $router->match()
            ->run($response->getRequest(), $response)
            ->send();
        if (static::isDebugging()) {
            $this->debugEnd();
        }
    }

    /**
     * Ends the debugging of the App and prints the debugbar if there is no
     * download file, if the request is not via AJAX and the Content-Type is
     * text/html.
     */
    protected function debugEnd() : void
    {
        static::$debugCollector->setEndTime()->setEndMemory();
        $response = static::router()->getResponse();
        if ( ! $response->hasDownload()
            && ! $response->getRequest()->isAjax()
            && \str_contains(
                (string) $response->getHeader('Content-Type'),
                'text/html'
            )
        ) {
            echo static::debugger()->renderDebugbar();
        }
    }

    /**
     * Detects if the request is via command-line and runs as a CLI request,
     * otherwise runs as HTTP.
     */
    public function run() : void
    {
        static::isCli() ? $this->runCli() : $this->runHttp();
    }

    /**
     * Run the App on CLI requests.
     */
    public function runCli() : void
    {
        $this->prepareToRun();
        static::console()->run();
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
     * @param string $name Service name
     * @param string $instance Service instance name
     *
     * @return object|null The service object or null
     */
    public static function getService(string $name, string $instance = 'default') : ?object
    {
        return static::$services[$name][$instance] ?? null;
    }

    /**
     * Set a service.
     *
     * @template T of object
     *
     * @param string $name Service name
     * @param T $service Service object
     * @param string $instance Service instance name
     *
     * @return T The service object
     */
    public static function setService(
        string $name,
        object $service,
        string $instance = 'default'
    ) : object {
        return static::$services[$name][$instance] = $service;
    }

    /**
     * Remove services.
     *
     * @param string $name Service name
     * @param string|null $instance Service instance name or null to remove all instances
     */
    public static function removeService(string $name, ?string $instance = 'default') : void
    {
        if ($instance === null) {
            unset(static::$services[$name]);
            return;
        }
        unset(static::$services[$name][$instance]);
    }

    /**
     * Get a autoloader service.
     *
     * @param string $instance The autoloader instance name
     *
     * @return Autoloader
     */
    public static function autoloader(string $instance = 'default') : Autoloader
    {
        $service = static::getService('autoloader', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setAutoloader($instance);
            $end = \microtime(true);
            $service->setDebugCollector(name: $instance);
            static::debugger()->addCollector($service->getDebugCollector(), 'Autoload');
            static::addDebugData('autoloader', $instance, $start, $end);
            return $service;
        }
        return static::setAutoloader($instance);
    }

    /**
     * Set a autoloader service.
     *
     * @param string $instance The autoloader instance name
     *
     * @return Autoloader
     */
    protected static function setAutoloader(string $instance) : Autoloader
    {
        $config = static::config()->get('autoloader', $instance);
        $service = new Autoloader($config['register'] ?? true, $config['extensions'] ?? '.php');
        if (isset($config['namespaces'])) {
            $service->setNamespaces($config['namespaces']);
        }
        if (isset($config['classes'])) {
            $service->setClasses($config['classes']);
        }
        return static::setService('autoloader', $service, $instance);
    }

    /**
     * Get a cache service.
     *
     * @param string $instance The cache instance name
     *
     * @return Cache
     */
    public static function cache(string $instance = 'default') : Cache
    {
        $service = static::getService('cache', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setCache($instance);
            $end = \microtime(true);
            $collector = new CacheCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Cache');
            static::addDebugData('cache', $instance, $start, $end);
            return $service;
        }
        return static::setCache($instance);
    }

    /**
     * Set a cache service.
     *
     * @param string $instance The cache instance name
     *
     * @return Cache
     */
    protected static function setCache(string $instance) : Cache
    {
        $config = static::config()->get('cache', $instance);
        $logger = null;
        if (isset($config['logger_instance'])) {
            $logger = static::logger($config['logger_instance']);
        }
        $config['serializer'] ??= Serializer::PHP;
        if (\is_string($config['serializer'])) {
            $config['serializer'] = Serializer::from($config['serializer']);
        }
        /**
         * @var Cache $service
         */
        $service = new $config['class'](
            $config['configs'] ?? [],
            $config['prefix'] ?? null,
            $config['serializer'],
            $logger
        );
        return static::setService('cache', $service, $instance);
    }

    /**
     * Get a console service.
     *
     * @param string $instance The console instance name
     *
     * @throws ReflectionException
     *
     * @return Console
     */
    public static function console(string $instance = 'default') : Console
    {
        $service = static::getService('console', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setConsole($instance);
            $end = \microtime(true);
            static::addDebugData('console', $instance, $start, $end);
            return $service;
        }
        return static::setConsole($instance);
    }

    /**
     * Set a console service.
     *
     * @param string $instance The console instance name
     *
     * @throws ReflectionException
     *
     * @return Console
     */
    protected static function setConsole(string $instance) : Console
    {
        $config = static::config()->get('console', $instance);
        $language = null;
        if (isset($config['language_instance'])) {
            $language = static::language($config['language_instance']);
        }
        $service = new Console($language);
        $locator = static::locator($config['locator_instance'] ?? 'default');
        if (isset($config['find_in_namespaces']) && $config['find_in_namespaces'] === true) {
            foreach ($locator->getFiles('Commands') as $file) {
                static::addCommand($file, $service, $locator);
            }
        }
        if (isset($config['directories'])) {
            foreach ($config['directories'] as $dir) {
                foreach ((array) $locator->listFiles($dir) as $file) {
                    static::addCommand($file, $service, $locator);
                }
            }
        }
        return static::setService('console', $service, $instance);
    }

    /**
     * Detects if the file has a command and adds it to the console.
     *
     * @param string $file The file to get the command class
     * @param Console $console The console to add the class
     * @param Locator $locator The locator to get the class name in the file
     *
     * @throws ReflectionException
     *
     * @return bool True if the command was added. If not, it's false.
     */
    protected static function addCommand(string $file, Console $console, Locator $locator) : bool
    {
        $className = $locator->getClassName($file);
        if ($className === null) {
            return false;
        }
        if ( ! \class_exists($className)) {
            Isolation::require($file);
        }
        $class = new ReflectionClass($className); // @phpstan-ignore-line
        if ($class->isInstantiable() && $class->isSubclassOf(Command::class)) {
            $console->addCommand($className); // @phpstan-ignore-line
            return true;
        }
        return false;
    }

    /**
     * Get a debugger service.
     *
     * @param string $instance The debugger instance name
     *
     * @return Debugger
     */
    public static function debugger(string $instance = 'default') : Debugger
    {
        $service = static::getService('debugger', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setDebugger($instance);
            $end = \microtime(true);
            static::addDebugData('debugger', $instance, $start, $end);
            return $service;
        }
        return static::setDebugger($instance);
    }

    /**
     * Set a debugger service.
     *
     * @param string $instance The debugger instance name
     *
     * @return Debugger
     */
    protected static function setDebugger(string $instance) : Debugger
    {
        $config = static::config()->get('debugger');
        $service = new Debugger();
        if (isset($config['debugbar_view'])) {
            $service->setDebugbarView($config['debugbar_view']);
        }
        if (isset($config['options'])) {
            $service->setOptions($config['options']);
        }
        return static::setService('debugger', $service, $instance);
    }

    /**
     * Get a exceptionHandler service.
     *
     * @param string $instance The exceptionHandler instance name
     *
     * @return ExceptionHandler
     */
    public static function exceptionHandler(string $instance = 'default') : ExceptionHandler
    {
        $service = static::getService('exceptionHandler', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setExceptionHandler($instance);
            $end = \microtime(true);
            static::addDebugData('exceptionHandler', $instance, $start, $end);
            return $service;
        }
        return static::setExceptionHandler($instance);
    }

    /**
     * Set a exceptionHandler service.
     *
     * @param string $instance The exceptionHandler instance name
     *
     * @return ExceptionHandler
     */
    protected static function setExceptionHandler(string $instance) : ExceptionHandler
    {
        $config = static::config()->get('exceptionHandler');
        $environment = $config['environment'] ?? ExceptionHandler::PRODUCTION;
        $logger = null;
        if (isset($config['logger_instance'])) {
            $logger = static::logger($config['logger_instance']);
        }
        $language = null;
        if (isset($config['language_instance'])) {
            $language = static::language($config['language_instance']);
        }
        $service = new ExceptionHandler($environment, $logger, $language);
        if (isset($config['development_view'])) {
            $service->setDevelopmentView($config['development_view']);
        }
        if (isset($config['production_view'])) {
            $service->setProductionView($config['production_view']);
        }
        $config['initialize'] ??= true;
        if ($config['initialize'] === true) {
            $service->initialize($config['handle_errors'] ?? true);
        }
        return static::setService('exceptionHandler', $service, $instance);
    }

    /**
     * Get a antiCsrf service.
     *
     * @param string $instance The antiCsrf instance name
     *
     * @return AntiCSRF
     */
    public static function antiCsrf(string $instance = 'default') : AntiCSRF
    {
        $service = static::getService('antiCsrf', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setAntiCsrf($instance);
            $end = \microtime(true);
            static::addDebugData('antiCsrf', $instance, $start, $end);
            return $service;
        }
        return static::setAntiCsrf($instance);
    }

    /**
     * Set a antiCsrf service.
     *
     * @param string $instance The antiCsrf instance name
     *
     * @return AntiCSRF
     */
    protected static function setAntiCsrf(string $instance) : AntiCSRF
    {
        $config = static::config()->get('antiCsrf', $instance);
        static::session($config['session_instance'] ?? 'default');
        $service = new AntiCSRF(static::request($config['request_instance'] ?? 'default'));
        if (isset($config['token_name'])) {
            $service->setTokenName($config['token_name']);
        }
        if (isset($config['enabled']) && $config['enabled'] === false) {
            $service->disable();
        }
        return static::setService('antiCsrf', $service, $instance);
    }

    /**
     * Get a database service.
     *
     * @param string $instance The database instance name
     *
     * @return Database
     */
    public static function database(string $instance = 'default') : Database
    {
        $service = static::getService('database', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setDatabase($instance);
            $end = \microtime(true);
            $collector = new DatabaseCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Database');
            static::addDebugData('database', $instance, $start, $end);
            return $service;
        }
        return static::setDatabase($instance);
    }

    /**
     * Set a database service.
     *
     * @param string $instance The database instance name
     *
     * @return Database
     */
    protected static function setDatabase(string $instance) : Database
    {
        $config = static::config()->get('database', $instance);
        $logger = null;
        if (isset($config['logger_instance'])) {
            $logger = static::logger($config['logger_instance']);
        }
        return static::setService(
            'database',
            new Database($config['config'], logger: $logger),
            $instance
        );
    }

    /**
     * Get a mailer service.
     *
     * @param string $instance The mailer instance name
     *
     * @return Mailer
     */
    public static function mailer(string $instance = 'default') : Mailer
    {
        $service = static::getService('mailer', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setMailer($instance);
            $end = \microtime(true);
            $collector = new EmailCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Email');
            static::addDebugData('mailer', $instance, $start, $end);
            return $service;
        }
        return static::setMailer($instance);
    }

    /**
     * Set a mailer service.
     *
     * @param string $instance The mailer instance name
     *
     * @return Mailer
     */
    protected static function setMailer(string $instance) : Mailer
    {
        $config = static::config()->get('mailer', $instance);
        /**
         * @var class-string<Mailer> $class
         */
        $class = $config['class'] ?? SMTPMailer::class;
        return static::setService(
            'mailer',
            new $class($config['config']),
            $instance
        );
    }

    /**
     * Get a migrator service.
     *
     * @param string $instance The migrator instance name
     *
     * @return Migrator
     */
    public static function migrator(string $instance = 'default') : Migrator
    {
        $service = static::getService('migrator', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setMigrator($instance);
            $end = \microtime(true);
            static::addDebugData('migrator', $instance, $start, $end);
            return $service;
        }
        return static::setMigrator($instance);
    }

    /**
     * Set a migrator service.
     *
     * @param string $instance The migrator instance name
     *
     * @return Migrator
     */
    protected static function setMigrator(string $instance) : Migrator
    {
        $config = static::config()->get('migrator', $instance);
        return static::setService(
            'migrator',
            new Migrator(
                static::database($config['database_instance'] ?? 'default'),
                $config['directories'],
                $config['table'] ?? 'Migrations',
            ),
            $instance
        );
    }

    /**
     * Get a language service.
     *
     * @param string $instance The language instance name
     *
     * @return Language
     */
    public static function language(string $instance = 'default') : Language
    {
        $service = static::getService('language', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setLanguage($instance);
            $end = \microtime(true);
            $collector = new LanguageCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Language');
            static::addDebugData('language', $instance, $start, $end);
            return $service;
        }
        return static::setLanguage($instance);
    }

    /**
     * Set a language service.
     *
     * @param string $instance The language instance name
     *
     * @return Language
     */
    protected static function setLanguage(string $instance) : Language
    {
        $config = static::config()->get('language', $instance);
        $service = new Language($config['default'] ?? 'en');
        if (isset($config['supported'])) {
            $service->setSupportedLocales($config['supported']);
        }
        if (isset($config['negotiate']) && $config['negotiate'] === true) {
            $service->setCurrentLocale(
                static::negotiateLanguage($service, $config['request_instance'] ?? 'default')
            );
        }
        if (isset($config['fallback_level'])) {
            if (\is_int($config['fallback_level'])) {
                $config['fallback_level'] = FallbackLevel::from($config['fallback_level']);
            }
            $service->setFallbackLevel($config['fallback_level']);
        }
        $config['directories'] ??= [];
        if (isset($config['find_in_namespaces']) && $config['find_in_namespaces'] === true) {
            foreach (static::autoloader($config['autoloader_instance'] ?? 'default')
                ->getNamespaces() as $directories) {
                foreach ($directories as $directory) {
                    $directory .= 'Languages';
                    if (\is_dir($directory)) {
                        $config['directories'][] = $directory;
                    }
                }
            }
        }
        if ($config['directories']) {
            $service->setDirectories($config['directories']);
        }
        $service->addDirectory(__DIR__ . '/Languages');
        return static::setService('language', $service, $instance);
    }

    /**
     * Negotiates the language either via the command line or over HTTP.
     *
     * @param Language $language The current Language instance
     * @param string $requestInstance The name of the Request instance to be used
     *
     * @return string The negotiated language
     */
    protected static function negotiateLanguage(Language $language, string $requestInstance = 'default') : string
    {
        if (static::isCli()) {
            $supported = \array_map('\strtolower', $language->getSupportedLocales());
            $lang = \getenv('LANG');
            if ($lang) {
                $lang = \explode('.', $lang, 2);
                $lang = \strtolower($lang[0]);
                $lang = \strtr($lang, ['_' => '-']);
                if (\in_array($lang, $supported, true)) {
                    return $lang;
                }
            }
            return $language->getDefaultLocale();
        }
        return static::request($requestInstance)->negotiateLanguage(
            $language->getSupportedLocales()
        );
    }

    /**
     * Get a locator service.
     *
     * @param string $instance The locator instance name
     *
     * @return Locator
     */
    public static function locator(string $instance = 'default') : Locator
    {
        $service = static::getService('locator', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setLocator($instance);
            $end = \microtime(true);
            static::addDebugData('locator', $instance, $start, $end);
            return $service;
        }
        return static::setLocator($instance);
    }

    /**
     * Set a locator service.
     *
     * @param string $instance The locator instance name
     *
     * @return Locator
     */
    protected static function setLocator(string $instance) : Locator
    {
        $config = static::config()->get('locator', $instance);
        return static::setService(
            'locator',
            new Locator(static::autoloader($config['autoloader_instance'] ?? 'default')),
            $instance
        );
    }

    /**
     * Get a logger service.
     *
     * @param string $instance The logger instance name
     *
     * @return Logger
     */
    public static function logger(string $instance = 'default') : Logger
    {
        $service = static::getService('logger', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setLogger($instance);
            $end = \microtime(true);
            $collector = new LogCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Log');
            static::addDebugData('logger', $instance, $start, $end);
            return $service;
        }
        return static::setLogger($instance);
    }

    /**
     * Set a logger service.
     *
     * @param string $instance The logger instance name
     *
     * @return Logger
     */
    protected static function setLogger(string $instance) : Logger
    {
        $config = static::config()->get('logger', $instance);
        /**
         * @var class-string<Logger> $class
         */
        $class = $config['class'] ?? MultiFileLogger::class;
        $config['level'] ??= LogLevel::DEBUG;
        if (\is_int($config['level'])) {
            $config['level'] = LogLevel::from($config['level']);
        }
        return static::setService(
            'logger',
            new $class(
                $config['destination'],
                $config['level'],
                $config['config'] ?? [],
            ),
            $instance
        );
    }

    /**
     * Get a router service.
     *
     * @param string $instance The router instance name
     *
     * @return Router
     */
    public static function router(string $instance = 'default') : Router
    {
        $service = static::getService('router', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $config = (array) static::config()->get('router', $instance);
            $service = static::setRouter($instance, $config);
            $collector = new RoutingCollector($instance);
            $service->setDebugCollector($collector);
            if (isset($config['files'])) {
                static::requireRouterFiles($config['files']);
            }
            $end = \microtime(true);
            static::debugger()->addCollector($collector, 'Routing');
            static::addDebugData('router', $instance, $start, $end);
            return $service;
        }
        return static::setRouter($instance);
    }

    /**
     * Set a router service.
     *
     * @param string $instance The router instance name
     * @param array<mixed>|null $config The router instance configs or null
     *
     * @return Router
     */
    protected static function setRouter(string $instance, array $config = null) : Router
    {
        $requireFiles = $config === null;
        $config ??= static::config()->get('router', $instance);
        $language = null;
        if (isset($config['language_instance'])) {
            $language = static::language($config['language_instance']);
        }
        $service = static::setService('router', new Router(
            static::response($config['response_instance'] ?? 'default'),
            $language
        ), $instance);
        if (isset($config['auto_options']) && $config['auto_options'] === true) {
            $service->setAutoOptions();
        }
        if (isset($config['auto_methods']) && $config['auto_methods'] === true) {
            $service->setAutoMethods();
        }
        if ( ! empty($config['placeholders'])) {
            $service->addPlaceholder($config['placeholders']);
        }
        if ($requireFiles && isset($config['files'])) {
            static::requireRouterFiles($config['files']);
        }
        return $service;
    }

    /**
     * Load files that set the routes.
     *
     * @param array<string> $files The path of the router files
     */
    protected static function requireRouterFiles(array $files) : void
    {
        foreach ($files as $file) {
            if ( ! \is_file($file)) {
                throw new LogicException('Invalid router file: ' . $file);
            }
            Isolation::require($file);
        }
    }

    /**
     * Get a request service.
     *
     * @param string $instance The request instance name
     *
     * @return Request
     */
    public static function request(string $instance = 'default') : Request
    {
        $service = static::getService('request', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setRequest($instance);
            $end = \microtime(true);
            $collector = new HTTPCollector($instance);
            $collector->setRequest($service);
            static::debugger()->addCollector($collector, 'HTTP');
            static::addDebugData('request', $instance, $start, $end);
            return $service;
        }
        return static::setRequest($instance);
    }

    /**
     * Overrides variables to be set in the $_SERVER super-global when the
     * request is made via the command line.
     *
     * @param array<string,mixed> $vars
     */
    protected static function setServerVars(array $vars = []) : void
    {
        $vars = \array_replace(static::$defaultServerVars, $vars);
        foreach ($vars as $key => $value) {
            $_SERVER[$key] ??= $value;
        }
    }

    /**
     * Set a request service.
     *
     * @param string $instance The request instance name
     *
     * @return Request
     */
    protected static function setRequest(string $instance) : Request
    {
        $config = static::config()->get('request', $instance);
        if (static::isCli()) {
            static::setServerVars($config['server_vars'] ?? []);
        }
        $service = new Request($config['allowed_hosts'] ?? []);
        if (isset($config['force_https']) && $config['force_https'] === true) {
            $service->forceHttps();
        }
        return static::setService('request', $service, $instance);
    }

    /**
     * Get a response service.
     *
     * @param string $instance The response instance name
     *
     * @return Response
     */
    public static function response(string $instance = 'default') : Response
    {
        $service = static::getService('response', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setResponse($instance);
            $end = \microtime(true);
            $collection = static::debugger()->getCollection('HTTP');
            foreach ($collection->getCollectors() as $collector) {
                if ($collector->getName() === $instance) {
                    $service->setDebugCollector($collector); // @phpstan-ignore-line
                    break;
                }
            }
            static::addDebugData('response', $instance, $start, $end);
            return $service;
        }
        return static::setResponse($instance);
    }

    /**
     * Set a response service.
     *
     * @param string $instance The response instance name
     *
     * @return Response
     */
    protected static function setResponse(string $instance) : Response
    {
        $config = static::config()->get('response', $instance);
        $service = new Response(static::request($config['request_instance'] ?? 'default'));
        if ( ! empty($config['headers'])) {
            $service->setHeaders($config['headers']);
        }
        if ( ! empty($config['auto_etag'])) {
            $service->setAutoEtag(
                $config['auto_etag']['active'] ?? true,
                $config['auto_etag']['hash_algo'] ?? null
            );
        }
        if (isset($config['auto_language']) && $config['auto_language'] === true) {
            $service->setContentLanguage(
                static::language($config['language_instance'] ?? 'default')->getCurrentLocale()
            );
        }
        if (isset($config['cache'])) {
            $config['cache'] === false
                ? $service->setNoCache()
                : $service->setCache($config['cache']['seconds'], $config['cache']['public'] ?? false);
        }
        return static::setService('response', $service, $instance);
    }

    /**
     * Get a session service.
     *
     * @param string $instance The session instance name
     *
     * @return Session
     */
    public static function session(string $instance = 'default') : Session
    {
        $service = static::getService('session', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setSession($instance);
            $end = \microtime(true);
            $collector = new SessionCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Session');
            static::addDebugData('session', $instance, $start, $end);
            return $service;
        }
        return static::setSession($instance);
    }

    /**
     * Set a session service.
     *
     * @param string $instance The session instance name
     *
     * @return Session
     */
    protected static function setSession(string $instance) : Session
    {
        $config = static::config()->get('session', $instance);
        if (isset($config['save_handler']['class'])) {
            $logger = null;
            if (isset($config['logger_instance'])) {
                $logger = static::logger($config['logger_instance']);
            }
            $saveHandler = new $config['save_handler']['class'](
                $config['save_handler']['config'] ?? [],
                $logger
            );
            if ($saveHandler instanceof DatabaseHandler
                && isset($config['save_handler']['database_instance'])
            ) {
                $saveHandler->setDatabase(
                    static::database($config['save_handler']['database_instance'])
                );
            }
        }
        // @phpstan-ignore-next-line
        $service = new Session($config['options'] ?? [], $saveHandler ?? null);
        if (isset($config['auto_start']) && $config['auto_start'] === true) {
            $service->start();
        }
        return static::setService('session', $service, $instance);
    }

    /**
     * Get a validation service.
     *
     * @param string $instance The validation instance name
     *
     * @return Validation
     */
    public static function validation(string $instance = 'default') : Validation
    {
        $service = static::getService('validation', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setValidation($instance);
            $end = \microtime(true);
            $collector = new ValidationCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Validation');
            static::addDebugData('validation', $instance, $start, $end);
            return $service;
        }
        return static::setValidation($instance);
    }

    /**
     * Set a validation service.
     *
     * @param string $instance The validation instance name
     *
     * @return Validation
     */
    protected static function setValidation(string $instance) : Validation
    {
        $config = static::config()->get('validation', $instance);
        $language = null;
        if (isset($config['language_instance'])) {
            $language = static::language($config['language_instance']);
        }
        return static::setService(
            'validation',
            new Validation(
                $config['validators'] ?? [
                    Validator::class,
                    FilesValidator::class,
                ],
                $language
            ),
            $instance
        );
    }

    /**
     * Get a view service.
     *
     * @param string $instance The view instance name
     *
     * @return View
     */
    public static function view(string $instance = 'default') : View
    {
        $service = static::getService('view', $instance);
        if ($service) {
            return $service; // @phpstan-ignore-line
        }
        if (static::isDebugging()) {
            $start = \microtime(true);
            $service = static::setView($instance);
            $end = \microtime(true);
            $collector = new ViewCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'View');
            static::addDebugData('view', $instance, $start, $end);
            return $service;
        }
        return static::setView($instance);
    }

    /**
     * Set a view service.
     *
     * @param string $instance The view instance name
     *
     * @return View
     */
    protected static function setView(string $instance) : View
    {
        $config = static::config()->get('view', $instance);
        $service = new View($config['base_dir'] ?? null, $config['extension'] ?? '.php');
        if (isset($config['layout_prefix'])) {
            $service->setLayoutPrefix($config['layout_prefix']);
        }
        if (isset($config['include_prefix'])) {
            $service->setIncludePrefix($config['include_prefix']);
        }
        if (isset($config['show_debug_comments']) && $config['show_debug_comments'] === false) {
            $service->disableDebugComments();
        }
        return static::setService('view', $service, $instance);
    }

    /**
     * Tell if it is a command-line request.
     *
     * @return bool
     */
    public static function isCli() : bool
    {
        if (static::$isCli === null) {
            static::$isCli = \PHP_SAPI === 'cli' || \defined('STDIN');
        }
        return static::$isCli;
    }

    /**
     * Set if it is a CLI request. Used for testing.
     *
     * @param bool $is
     */
    public static function setIsCli(bool $is) : void
    {
        static::$isCli = $is;
    }

    /**
     * Tell if the App is in debug mode.
     *
     * @return bool
     */
    public static function isDebugging() : bool
    {
        return isset(static::$debugCollector);
    }

    /**
     * Add services data to the debug collector.
     *
     * @param string $service Service name
     * @param string $instance Service instance name
     * @param float $start Microtime right before setting up the service
     * @param float $end Microtime right after setting up the service
     */
    protected static function addDebugData(
        string $service,
        string $instance,
        float $start,
        float $end
    ) : void {
        static::$debugCollector->addData([
            'service' => $service,
            'instance' => $instance,
            'start' => $start,
            'end' => $end,
        ]);
    }
}
