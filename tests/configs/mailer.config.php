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
 * @see App::mailer()
 */

use Framework\Email\Mailers\SMTPMailer;

return [
    'default' => [
        'class' => SMTPMailer::class,
        'config' => [
            'server' => 'localhost',
            'port' => 587,
            'tls' => true,
            'username' => null,
            'password' => null,
            'charset' => 'utf-8',
            'crlf' => "\r\n",
            'keep_alive' => false,
        ],
    ],
];
