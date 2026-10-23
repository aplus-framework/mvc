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
    \Aplus\Config\Config::get(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Aplus\Config\Config::set(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Aplus\Config\Config::add(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Aplus\Config\Config::getInstances(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Aplus\Config\Config::load(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Aplus\Mvc\App::getService(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Aplus\Mvc\App::setService(),
    0,
    argumentsSet('service_names')
);
expectedArguments(
    \Aplus\Mvc\App::removeService(),
    0,
    argumentsSet('service_names')
);
override(\Aplus\Mvc\App::getService(), map([
    'antiCsrf' => \Aplus\Http\AntiCsrf::class,
    'autoloader' => \Aplus\Autoload\Autoloader::class,
    'cache' => \Aplus\Cache\Cache::class,
    'console' => \Aplus\Cli\Console::class,
    'database' => \Aplus\Database\Database::class,
    'debugger' => \Aplus\Debug\Debugger::class,
    'exceptionHandler' => \Aplus\Debug\ExceptionHandler::class,
    'language' => \Aplus\Language\Language::class,
    'locator' => \Aplus\Autoload\Locator::class,
    'logger' => \Aplus\Log\Logger::class,
    'mailer' => \Aplus\Email\Mailer::class,
    'migrator' => \Aplus\Database\Extra\Migrator::class,
    'request' => \Aplus\Http\Request::class,
    'response' => \Aplus\Http\Response::class,
    'router' => \Aplus\Routing\Router::class,
    'session' => \Aplus\Session\Session::class,
    'validation' => \Aplus\Validation\Validation::class,
    'view' => \Aplus\Mvc\View::class,
]));
expectedArguments(
    \Aplus\Validation\Validation::getMessage(),
    1,
    argumentsSet('rules')
);
expectedArguments(
    \Aplus\Validation\Validation::isRuleAvailable(),
    0,
    argumentsSet('rules')
);
expectedArguments(
    \Aplus\Validation\Validation::setMessage(),
    1,
    argumentsSet('rules')
);
expectedArguments(
    \Aplus\Validation\Validation::setRule(),
    1,
    argumentsSet('rules')
);
registerArgumentsSet(
    'order_by_directions',
    'asc',
    'desc',
);
expectedArguments(
    \Aplus\Mvc\Model::paginate(),
    4,
    argumentsSet('order_by_directions')
);
