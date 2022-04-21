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

use BadMethodCallException;
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
     * @var array<string,array<string,mixed>>
     */
    protected static array $services = [];
    protected static bool $isRunning = false;
    protected static ?Config $config;
    protected static ?bool $isCli = null;
    protected static AppCollector $debugCollector;

    /**
     * Initialize the App.
     *
     * @param array<string,mixed>|Config|string|null $config
     * @param bool $debug
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
    }

    /**
     * @param string $method
     * @param array<int,mixed> $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments) : mixed
    {
        if (\method_exists($this, $method)) {
            return $this->{$method}(...$arguments);
        }
        $class = static::class;
        throw new BadMethodCallException("Call to undefined method {$class}::{$method}()");
    }

    protected function debugStart() : void
    {
        static::$debugCollector = new AppCollector();
        static::$debugCollector->setStartTime()->setStartMemory();
        static::$debugCollector->setApp($this);
        static::debugger()->addCollector(static::$debugCollector, 'App');
    }

    /**
     * @param array<string,mixed> $options
     *
     * @return Router|null
     */
    protected function prepareToRun(array $options = []) : ?Router
    {
        if (static::$isRunning) {
            throw new LogicException('App is already running');
        }
        static::$isRunning = true;
        if ( ! isset($options['autoloader'])) {
            $configs = static::config()->getInstances('autoloader');
            $options['autoloader'] = isset($configs['default']) ? 'default' : false;
        }
        if ($options['autoloader'] !== false) {
            static::autoloader($options['autoloader']);
        }
        if ( ! isset($options['exceptionHandler'])) {
            $options['exceptionHandler'] = 'default';
            $configs = static::config()->getInstances('exceptionHandler');
            if ( ! isset($configs['default']) && isset(static::$debugCollector)) {
                static::config()->set('exceptionHandler', [
                    'environment' => ExceptionHandler::DEVELOPMENT,
                ]);
            }
        }
        if ($options['exceptionHandler'] !== false) {
            static::exceptionHandler($options['exceptionHandler']);
        }
        $options['router'] ??= 'default';
        if ($options['router'] !== false) {
            return static::router($options['router']);
        }
        return null;
    }

    /**
     * @param array<string,mixed> $options
     */
    public function runHttp(array $options = []) : void
    {
        $router = $this->prepareToRun($options);
        if ($router === null) {
            return;
        }
        $response = $router->getResponse();
        $router->match()
            ->run($response->getRequest(), $response)
            ->send();
        if (isset(static::$debugCollector)) {
            $this->debugEnd($router);
        }
    }

    protected function debugEnd(Router $router) : void
    {
        static::$debugCollector->setEndTime()->setEndMemory();
        $response = $router->getResponse();
        if ( ! $response->hasDownload()
            && ! $response->getRequest()->isAjax()
            && \str_contains($response->getHeader('Content-Type'), 'text/html')
        ) {
            echo static::debugger()->renderDebugbar();
        }
    }

    /**
     * @param array<string,mixed> $options
     */
    public function runCli(array $options = []) : void
    {
        $this->setRequiredCliVars();
        $this->prepareToRun($options);
        $options['console'] ??= 'default';
        if ($options['console'] !== false) {
            static::console($options['console'])->run();
        }
    }

    /**
     * Set default super-global vars required by some services.
     *
     * For example: To load Routes with Router, a Request instance is required
     * and it requires some $_SERVER vars to be initialized.
     *
     * @return void
     */
    protected function setRequiredCliVars() : void
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
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
     * @param mixed $service
     * @param string $instance
     *
     * @return mixed
     */
    public static function setService(
        string $name,
        mixed $service,
        string $instance = 'default'
    ) : mixed {
        return static::$services[$name][$instance] = $service;
    }

    /**
     * Remove a service.
     *
     * @param string $name
     * @param string|null $instance Instance name or null to remove all
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
     * Get the Autoloader service.
     *
     * @param string $instance
     *
     * @return Autoloader
     */
    public static function autoloader(string $instance = 'default') : Autoloader
    {
        $service = static::getService('autoloader', $instance);
        if ($service) {
            return $service;
        }
        if (isset(static::$debugCollector)) {
            $start = \microtime(true);
            $service = static::setAutoloader($instance);
            $end = \microtime(true);
            $service->setDebugCollector(name: $instance);
            static::debugger()->addCollector($service->getDebugCollector(), 'Autoload');
            static::$debugCollector->addData([
                'service' => 'autoloader',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setAutoloader($instance);
    }

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
        if (isset(static::$debugCollector)) {
            $start = \microtime(true);
            $service = static::setCache($instance);
            $end = \microtime(true);
            $collector = new CacheCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Cache');
            static::$debugCollector->addData([
                'service' => 'cache',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setCache($instance);
    }

    protected static function setCache(string $instance) : Cache
    {
        $config = static::config()->get('cache', $instance);
        $logger = null;
        if (isset($config['logger_instance'])) {
            $logger = static::logger($config['logger_instance']);
        }
        $service = new $config['class'](
            $config['configs'] ?? [],
            $config['prefix'] ?? null,
            $config['serializer'] ?? Serializer::PHP,
            $logger
        );
        return static::setService('cache', $service, $instance);
    }

    /**
     * Get the Console service.
     *
     * @param string $instance
     *
     * @throws ReflectionException
     *
     * @return Console
     */
    public static function console(string $instance = 'default') : Console
    {
        $service = static::getService('console', $instance);
        if ($service) {
            return $service;
        }
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
                foreach ($locator->listFiles($dir) as $file) {
                    static::addCommand($file, $service, $locator);
                }
            }
        }
        return static::setService('console', $service, $instance);
    }

    /**
     * @param string $file
     * @param Console $console
     * @param Locator $locator
     *
     * @throws ReflectionException
     *
     * @return bool
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

    public static function debugger(string $instance = 'default') : Debugger
    {
        $service = static::getService('debugger', $instance);
        if ($service) {
            return $service;
        }
        return static::setService('debugger', new Debugger(), $instance);
    }

    public static function exceptionHandler(string $instance = 'default') : ExceptionHandler
    {
        $service = static::getService('exceptionHandler', $instance);
        if ($service) {
            return $service;
        }
        if (isset(static::$debugCollector)) {
            $start = \microtime(true);
            $service = static::setExceptionHandler($instance);
            $end = \microtime(true);
            static::$debugCollector->addData([
                'service' => 'exceptionHandler',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setExceptionHandler($instance);
    }

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
     * Get the CSRF service.
     *
     * @param string $instance
     *
     * @return AntiCSRF
     */
    public static function antiCsrf(string $instance = 'default') : AntiCSRF
    {
        $service = static::getService('antiCsrf', $instance);
        if ($service) {
            return $service;
        }
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
     * Get a Database service.
     *
     * @param string $instance
     *
     * @return Database
     */
    public static function database(string $instance = 'default') : Database
    {
        $service = static::getService('database', $instance);
        if ($service) {
            return $service;
        }
        if (isset(static::$debugCollector)) {
            $start = \microtime(true);
            $service = static::setDatabase($instance);
            $end = \microtime(true);
            $collector = new DatabaseCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Database');
            static::$debugCollector->addData([
                'service' => 'database',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setDatabase($instance);
    }

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
        if (isset(static::$debugCollector)) {
            $start = \microtime(true);
            $service = static::setMailer($instance);
            $end = \microtime(true);
            $collector = new EmailCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Email');
            static::$debugCollector->addData([
                'service' => 'mailer',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setMailer($instance);
    }

    protected static function setMailer(string $instance) : Mailer
    {
        $config = static::config()->get('mailer', $instance);
        $class = $config['class'] ?? SMTPMailer::class;
        return static::setService(
            'mailer',
            new $class($config['config']),
            $instance
        );
    }

    /**
     * Get the Language service.
     *
     * @param string $instance
     *
     * @return Language
     */
    public static function language(string $instance = 'default') : Language
    {
        $service = static::getService('language', $instance);
        if ($service) {
            return $service;
        }
        if (isset(static::$debugCollector)) {
            $start = \microtime(true);
            $service = static::setLanguage($instance);
            $end = \microtime(true);
            $collector = new LanguageCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Language');
            static::$debugCollector->addData([
                'service' => 'language',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setLanguage($instance);
    }

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
     * Get the Locator service.
     *
     * @param string $instance
     *
     * @return Locator
     */
    public static function locator(string $instance = 'default') : Locator
    {
        $service = static::getService('locator', $instance);
        if ($service) {
            return $service;
        }
        if (isset(static::$debugCollector)) {
            $start = \microtime(true);
            $service = static::setLocator($instance);
            $end = \microtime(true);
            static::$debugCollector->addData([
                'service' => 'locator',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setLocator($instance);
    }

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
     * Get the Logger service.
     *
     * @param string $instance
     *
     * @return Logger
     */
    public static function logger(string $instance = 'default') : Logger
    {
        $service = static::getService('logger', $instance);
        if ($service) {
            return $service;
        }
        if (isset(static::$debugCollector)) {
            $start = \microtime(true);
            $service = static::setLogger($instance);
            $end = \microtime(true);
            $collector = new LogCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Log');
            static::$debugCollector->addData([
                'service' => 'logger',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setLogger($instance);
    }

    protected static function setLogger(string $instance) : Logger
    {
        $config = static::config()->get('logger', $instance);
        $class = $config['class'] ?? MultiFileLogger::class;
        return static::setService(
            'logger',
            new $class(
                $config['destination'],
                $config['level'] ?? LogLevel::DEBUG,
                $config['config'] ?? [],
            ),
            $instance
        );
    }

    /**
     * Get the Router service.
     *
     * @param string $instance
     *
     * @return Router
     */
    public static function router(string $instance = 'default') : Router
    {
        $service = static::getService('router', $instance);
        if ($service) {
            return $service;
        }
        if (isset(static::$debugCollector)) {
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
            static::$debugCollector->addData([
                'service' => 'router',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setRouter($instance);
    }

    /**
     * @param string $instance
     * @param array<mixed>|null $config
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
     * @param array<string> $files
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
     * Get the Request service.
     *
     * @param string $instance
     *
     * @return Request
     */
    public static function request(string $instance = 'default') : Request
    {
        $service = static::getService('request', $instance);
        if ($service) {
            return $service;
        }
        if (isset(static::$debugCollector)) {
            $start = \microtime(true);
            $service = static::setRequest($instance);
            $end = \microtime(true);
            $collector = new HTTPCollector($instance);
            $collector->setRequest($service);
            static::debugger()->addCollector($collector, 'HTTP');
            static::$debugCollector->addData([
                'service' => 'request',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setRequest($instance);
    }

    protected static function setRequest(string $instance) : Request
    {
        $config = static::config()->get('request', $instance);
        $service = new Request($config['allowed_hosts'] ?? null);
        if (isset($config['force_https']) && $config['force_https'] === true) {
            $service->forceHttps();
        }
        return static::setService('request', $service, $instance);
    }

    /**
     * Get the Response service.
     *
     * @param string $instance
     *
     * @return Response
     */
    public static function response(string $instance = 'default') : Response
    {
        $service = static::getService('response', $instance);
        if ($service) {
            return $service;
        }
        if (isset(static::$debugCollector)) {
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
            static::$debugCollector->addData([
                'service' => 'response',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setResponse($instance);
    }

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
     * Get the Session service.
     *
     * @param string $instance
     *
     * @return Session
     */
    public static function session(string $instance = 'default') : Session
    {
        $service = static::getService('session', $instance);
        if ($service) {
            return $service;
        }
        if (isset(static::$debugCollector)) {
            $start = \microtime(true);
            $service = static::setSession($instance);
            $end = \microtime(true);
            $collector = new SessionCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Session');
            static::$debugCollector->addData([
                'service' => 'session',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setSession($instance);
    }

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
        }
        // @phpstan-ignore-next-line
        $service = new Session($config['options'] ?? [], $saveHandler ?? null);
        if (isset($config['auto_start']) && $config['auto_start'] === true) {
            $service->start();
        }
        return static::setService('session', $service, $instance);
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
        if (isset(static::$debugCollector)) {
            $start = \microtime(true);
            $service = static::setValidation($instance);
            $end = \microtime(true);
            $collector = new ValidationCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'Validation');
            static::$debugCollector->addData([
                'service' => 'validation',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setValidation($instance);
    }

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
        if (isset(static::$debugCollector)) {
            $start = \microtime(true);
            $service = static::setView($instance);
            $end = \microtime(true);
            $collector = new ViewCollector($instance);
            $service->setDebugCollector($collector);
            static::debugger()->addCollector($collector, 'View');
            static::$debugCollector->addData([
                'service' => 'view',
                'instance' => $instance,
                'start' => $start,
                'end' => $end,
            ]);
            return $service;
        }
        return static::setView($instance);
    }

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
        return static::setService('view', $service, $instance);
    }

    /**
     * Tell if is a command-line request.
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
     * Set if is a CLI request. Used for tests.
     *
     * @param bool $is
     */
    public static function setIsCli(bool $is) : void
    {
        static::$isCli = $is;
    }

    public static function isDebugging() : bool
    {
        return isset(static::$debugCollector);
    }
}
