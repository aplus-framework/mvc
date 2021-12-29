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
use Framework\CLI\Command;
use Framework\CLI\Console;
use Framework\Config\Config;
use Framework\Database\Database;
use Framework\Debug\ExceptionHandler;
use Framework\Email\Mailer;
use Framework\Helpers\Isolation;
use Framework\HTTP\AntiCSRF;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Language\Language;
use Framework\Log\Logger;
use Framework\Routing\Router;
use Framework\Session\Session;
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
     * @var array<string,array>
     */
    protected static array $services = [];
    protected static bool $isRunning = false;
    protected static ?Config $config;
    protected static ?bool $isCli = null;

    /**
     * Initialize the App.
     *
     * @param Config|null $config
     */
    public function __construct(Config $config = null)
    {
        if (isset(static::$config)) {
            throw new LogicException('App already initialized');
        }
        static::$config = $config ?? new Config();
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
        $options['autoloader'] ??= 'default';
        if ($options['autoloader'] !== false) {
            static::autoloader($options['autoloader']);
        }
        $options['exceptions'] ??= 'default';
        if ($options['exceptions'] !== false) {
            static::exceptions($options['exceptions']);
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
        $service = new Autoloader();
        $config = static::config()->get('autoloader', $instance);
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
        $config = static::config()->get('cache', $instance);
        $logger = null;
        if (isset($config['logger_instance'])) {
            $logger = static::logger($config['logger_instance']);
        }
        $service = new $config['class'](
            $config['configs'] ?? [],
            $config['prefix'] ?? null,
            $config['serializer'] ?? Cache::SERIALIZER_PHP,
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
        $service = new Console(
            static::language($config['language_instance'] ?? 'default')
        );
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
            $console->addCommand($className);
            return true;
        }
        return false;
    }

    public static function exceptions(string $instance = 'default') : ExceptionHandler
    {
        $service = static::getService('exceptions', $instance);
        if ($service) {
            return $service;
        }
        $config = static::config()->get('exceptions');
        $environment = $config['environment'] ?? ExceptionHandler::PRODUCTION;
        $logger = null;
        if (isset($config['logger_instance'])) {
            $logger = static::logger($config['logger_instance']);
        }
        $service = new ExceptionHandler(
            $environment,
            $logger,
            static::language($config['language_instance'] ?? 'default')
        );
        if (isset($config['views_dir'])) {
            $service->setViewsDir($config['views_dir']);
        }
        $config['initialize'] ??= true;
        if ($config['initialize'] === true) {
            $service->initialize($config['handle_errors'] ?? true);
        }
        return static::setService('exceptions', $service, $instance);
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
        $service = static::getService('anti-csrf', $instance);
        if ($service) {
            return $service;
        }
        $config = static::config()->get('anti-csrf', $instance);
        static::session($config['session_instance'] ?? 'default');
        $service = new AntiCSRF(static::request($config['request_instance'] ?? 'default'));
        if (isset($config['token_name'])) {
            $service->setTokenName($config['token_name']);
        }
        if (isset($config['enabled']) && $config['enabled'] === false) {
            $service->disable();
        }
        return static::setService('anti-csrf', $service, $instance);
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
        $config = static::config()->get('database', $instance);
        $logger = null;
        if (isset($config['logger_instance'])) {
            $logger = static::logger($config['logger_instance']);
        }
        return static::setService(
            'database',
            new Database($config, logger: $logger), // @phpstan-ignore-line
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
        return static::setService(
            'mailer',
            new $config['class']($config),
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
                ->getNamespaces() as $directory) {
                $directory .= 'Languages';
                if (\is_dir($directory)) {
                    $config['directories'][] = $directory;
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
        $config = static::config()->get('logger', $instance);
        return static::setService(
            'logger',
            new Logger($config['directory'], $config['level'] ?? Logger::DEBUG),
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
        $config = static::config()->get('router', $instance);
        $router = static::setService('router', new Router(
            static::response($config['response_instance'] ?? 'default'),
            static::language($config['language_instance'] ?? 'default')
        ), $instance);
        if (isset($config['files'])) {
            foreach ($config['files'] as $file) {
                if ( ! \is_file($file)) {
                    throw new LogicException('Invalid router file: ' . $file);
                }
                Isolation::require($file);
            }
        }
        return $router;
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
        $config = static::config()->get('request', $instance);
        return static::setService(
            'request',
            new Request($config['allowed_hosts'] ?? null),
            $instance
        );
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
        $config = static::config()->get('response', $instance);
        return static::setService(
            'response',
            new Response(static::request($config['request_instance'] ?? 'default')),
            $instance
        );
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
        $config = static::config()->get('validation', $instance);
        return static::setService(
            'validation',
            new Validation(
                $config['validators'] ?? null,
                static::language($config['language_instance'] ?? 'default')
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
        $service = new View();
        $config = static::config()->get('view', $instance);
        if (isset($config['base_dir'])) {
            $service->setBaseDir($config['base_dir']);
        }
        if (isset($config['extension'])) {
            $service->setExtension($config['extension']);
        }
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
}
