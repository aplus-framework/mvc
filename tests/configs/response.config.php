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

use Framework\HTTP\CSP;

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
            CSP::defaultSrc => [
                'self',
            ],
            CSP::styleSrc => [
                'self',
                'cdn.foo.tld',
            ],
        ],
        'csp_report_only' => [
            CSP::defaultSrc => [
                'self',
            ],
        ],
        'request_instance' => 'default',
    ],
];
