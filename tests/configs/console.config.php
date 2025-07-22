<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [
    'default' => [
        'find_in_namespaces' => true,
        'directories' => [
            __DIR__,
            '/unknown',
        ],
        'commands' => [
            Tests\MVC\Commands\Hello::class,
        ],
        'language_instance' => 'default',
        'locator_instance' => 'default',
    ],
];
