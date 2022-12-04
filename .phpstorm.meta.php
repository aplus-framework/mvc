<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPSTORM_META;

registerArgumentsSet(
    'service_names',
    'antiCsrf',
    'autoloader',
    'cache',
    'console',
    'database',
    'debugger',
    'exceptionHandler',
    'language',
    'locator',
    'logger',
    'mailer',
    'migrator',
    'request',
    'response',
    'router',
    'session',
    'validation',
    'view',
);
registerArgumentsSet(
    'rules',
    'exist',
    'notUnique',
    'unique',
);
expectedArguments(
    \Framework\Config\Config::get(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Framework\Config\Config::set(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Framework\Config\Config::add(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Framework\Config\Config::getInstances(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Framework\Config\Config::load(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Framework\MVC\App::getService(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Framework\MVC\App::setService(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Framework\MVC\App::removeService(),
    0,
    argumentsSet('service_names')
);
override(\Framework\MVC\App::getService(), map([
    'antiCsrf' => \Framework\HTTP\AntiCSRF::class,
    'autoloader' => \Framework\Autoload\Autoloader::class,
    'cache' => \Framework\Cache\Cache::class,
    'console' => \Framework\CLI\Console::class,
    'database' => \Framework\Database\Database::class,
    'debugger' => \Framework\Debug\Debugger::class,
    'exceptionHandler' => \Framework\Debug\ExceptionHandler::class,
    'language' => \Framework\Language\Language::class,
    'locator' => \Framework\Autoload\Locator::class,
    'logger' => \Framework\Log\Logger::class,
    'mailer' => \Framework\Email\Mailer::class,
    'migrator' => \Framework\Database\Extra\Migrator::class,
    'request' => \Framework\HTTP\Request::class,
    'response' => \Framework\HTTP\Response::class,
    'router' => \Framework\Routing\Router::class,
    'session' => \Framework\Session\Session::class,
    'validation' => \Framework\Validation\Validation::class,
    'view' => \Framework\MVC\View::class,
]));
expectedArguments(
    \Framework\Validation\Validation::getMessage(),
    1,
    argumentsSet('rules')
);
expectedArguments(
    \Framework\Validation\Validation::isRuleAvailable(),
    0,
    argumentsSet('rules')
);
expectedArguments(
    \Framework\Validation\Validation::setMessage(),
    1,
    argumentsSet('rules')
);
expectedArguments(
    \Framework\Validation\Validation::setRule(),
    1,
    argumentsSet('rules')
);
