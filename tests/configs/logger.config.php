<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Framework\Log\Loggers\MultiFileLogger;
use Framework\Log\LogLevel;

return [
    'default' => [
        'class' => MultiFileLogger::class,
        'destination' => sys_get_temp_dir(),
        'level' => 0, //LogLevel::DEBUG,
    ],
];
