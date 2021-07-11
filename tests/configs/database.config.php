<?php
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [
    'default' => [
        'host' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT'),
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
        'schema' => getenv('DB_SCHEMA'),
    ],
];
