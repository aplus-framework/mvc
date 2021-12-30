<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @see App::response()
 */
return [
    'default' => [
        'headers' => [
            'X-Version' => '3.6.9',
        ],
        'auto_etag' => true,
        'auto_language' => true,
        'cache' => [
            'seconds' => 60,
            'public' => true,
        ],
        'request_instance' => 'default',
    ],
];
