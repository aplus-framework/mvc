<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Framework\Language\FallbackLevel;

return [
    'default' => [
        'default' => 'en',
        'supported' => [
            'en',
            'es',
            'pt-br',
        ],
        'fallback_level' => 2, //FallbackLevel::default,
        'directories' => null,
        'negotiate' => true,
        'find_in_namespaces' => true,
    ],
];
