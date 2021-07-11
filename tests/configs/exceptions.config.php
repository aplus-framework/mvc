<?php
/*
 * This file is part of The Framework MVC Library.
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
        'views_dir' => null,
        'log' => true,
    ],
];
