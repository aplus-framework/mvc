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
        'validators' => [
            \Framework\MVC\Validator::class,
            \Framework\Validation\FilesValidator::class,
        ],
        'language_instance' => 'default',
    ],
];
