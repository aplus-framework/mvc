<?php
/**
 * Configs.
 *
 * Lis of files containing configs
 *
 * @see App::prepareConfigs
 */
$config['configs']['default'] = [
];
/**
 * Routes.
 *
 * List of files containing routes
 *
 * @see App::prepareRoutes
 */
$config['routes']['default'] = [
	__DIR__ . '/routes.php',
];
/**
 * Autoloader.
 *
 * @see App::autoloader
 */
$config['autoloader']['default'] = [
	'classes' => [],
	'namespaces' => [],
];
/**
 * Database.
 *
 * @see App::database
 * @see \Framework\Database\Database::makeConfig
 */
$config['database']['default'] = [
	'host' => getenv('DB_HOST'),
	'port' => getenv('DB_PORT'),
	'username' => getenv('DB_USERNAME'),
	'password' => getenv('DB_PASSWORD'),
	'schema' => getenv('DB_SCHEMA'),
];
/**
 * Cache.
 *
 * @see  App::cache
 *
 * @todo Update configs[directory]
 */
$config['cache']['files'] = [
	'driver' => 'Files',
	'configs' => [
		'directory' => '/tmp',
		'length' => 4096,
	],
	'prefix' => null,
	'serializer' => 'php',
];
$config['cache']['default'] = $config['cache']['files'];
/**
 * Console.
 *
 * @see App::console
 */
$config['console']['default'] = [
	'enabled' => true,
	'defaults' => true,
];
/**
 * Exceptions.
 *
 * @see App::run
 */
$config['exceptions']['default'] = [
	'clearBuffer' => true,
	'viewsDir' => null,
];
/**
 * Language.
 *
 * @see App::language
 */
$config['language']['default'] = [
	'default' => 'en',
	'supported' => [
		'en',
		'es',
		'pt-br',
	],
	'fallback_level' => 2,
	'directories' => null,
	'negotiate' => true,
];
/**
 * Logger.
 *
 * @see  App::logger
 *
 * @todo Update directory
 */
$config['logger']['default'] = [
	'directory' => '/tmp',
	'level' => \Framework\Log\Logger::DEBUG,
];
/**
 * Mailer.
 *
 * @see App::mailer
 */
$config['mailer']['default'] = [
	'server' => 'localhost',
	'port' => 587,
	'tls' => true,
	'username' => null,
	'password' => null,
	'charset' => 'utf-8',
	'crlf' => "\r\n",
	'keep_alive' => false,
];
/**
 * Session.
 *
 * @see App::session
 */
$config['session']['default'] = [
	'options' => [],
	'handler' => null,
];
/**
 * Validation.
 *
 * @see App::validation
 */
$config['validation']['default'] = [
	'validators' => [
		\Framework\MVC\Validator::class,
	],
];
/**
 * View.
 *
 * @see  App::view
 *
 * @todo Update base_path
 */
$config['view']['default'] = [
	'base_path' => '/tmp',
	'extension' => '.php',
];
