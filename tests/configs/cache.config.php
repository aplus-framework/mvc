<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Framework\Cache\FilesCache;
use Framework\Cache\Serializer;

return [
    'default' => [
        'class' => FilesCache::class,
        'configs' => [
            'directory' => getenv('GITHUB_ACTION') ? getenv('RUNNER_TEMP') : sys_get_temp_dir(),
        ],
        'prefix' => null,
        'serializer' => 'php', //Serializer::PHP,
        'logger_instance' => 'default',
    ],
];
