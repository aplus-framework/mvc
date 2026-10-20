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

use Aplus\Http\Csp;

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
        'csp' => [
            Csp::defaultSrc => [
                'self',
            ],
            Csp::styleSrc => [
                'self',
                'cdn.foo.tld',
            ],
        ],
        'csp_report_only' => [
            Csp::defaultSrc => [
                'self',
            ],
        ],
        'json_flags' => \JSON_THROW_ON_ERROR,
        'replace_headers' => false,
        'request_instance' => 'default',
    ],
];
