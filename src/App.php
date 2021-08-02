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
use Framework\CLI\Command;
use Framework\CLI\Console;
use Framework\Config\Config;
use Framework\Database\Database;
use Framework\Debug\ExceptionHandler;
use Framework\Email\Mailer;
use Framework\Email\SMTP;
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

/**
 * Class App.
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
        $config = static::config()->get('exceptions');
        $environment = $config['environment'] ?? ExceptionHandler::PRODUCTION;
        $logger = null;
        if (isset($config['log']) && $config['log'] === true) {
            $logger = static::logger();
        }
        $exceptions = new ExceptionHandler(
            $environment,
            $logger,
            static::language()
        );
        if (isset($config['views_dir'])) {
            $exceptions->setViewsDir($config['views_dir']);
        }
        $exceptions->initialize();
    }

    public function run() : void
    {
        if (static::$isRunning) {
            throw new LogicException('App is already running');
        }
        static::$isRunning = true;
        static::autoloader();
        $this->prepareExceptionHandler();
        $this->prepareRoutes();
        if (static::isCli()) {
            if ( ! empty(static::config()->get('console')['enabled'])) {
                static::console()->run();
            }
            return;
        }
        static::router()
            ->match()
            ->run(static::request(), static::response())
            ->send();
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
            Isolation::require($file); // @phpstan-ignore-line
        }
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
     * @param string $instance
     *
     * @throws \ReflectionException
     *
     * @return Console
     */
    public static function console(string $instance = 'default') : Console
    {
        $service = static::getService('console', $instance);
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
        return static::setService('console', $service, $instance);
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
        $service->setTokenName($config['token_name']);
        $config['enabled'] ? $service->enable() : $service->disable();
        return static::setService('csrf', $service, $instance);
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
                new Database(static::config()->get('database', $instance)), // @phpstan-ignore-line
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
        if (isset($config['negotiate'])
            && $config['negotiate'] === true
            && ! static::isCli()
        ) {
            $service->setCurrentLocale(
                static::request($config['request_instance'] ?? 'default')
                    ->negotiateLanguage(
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
            foreach (static::autoloader($config['autoloader_instance'] ?? 'default')
                ->getNamespaces() as $directory) {
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
        return static::setService('language', $service, $instance);
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
            new Logger($config['directory'], $config['level']),
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
        return static::setService(
            'router',
            new Router(
                static::response($config['response_instance'] ?? 'default'),
                static::language($config['language_instance'] ?? 'default')
            ),
            $instance
        );
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
            $saveHandler = new $config['save_handler']['class'](
                $config['save_handler']['config'] ?? [],
                static::logger($config['logger_instance'] ?? 'default')
            );
        }
        $service = new Session($config['options'] ?? [], $saveHandler ?? null);
        $service->start();
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
        $service->setBaseDir($config['base_dir']);
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
