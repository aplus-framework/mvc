<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Framework\Debug\ExceptionHandler;

return [
    'default' => [
        'environment' => ExceptionHandler::PRODUCTION,
        'development_view' => __FILE__,
        'production_view' => __FILE__,
        'initialize' => true,
        'logger_instance' => 'default',
        'language_instance' => 'default',
    ],
];
