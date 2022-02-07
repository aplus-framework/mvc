<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\MVC;

use Framework\Config\Config;
use Framework\Language\Language;

class AppMock extends \Framework\MVC\App
{
    /**
     * @param array<string,mixed>|Config|string|null $config
     * @param bool $debug
     */
    public function __construct(
        Config | array | string | null $config = null,
        bool $debug = false
    ) {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost:8080';
        $_SERVER['REQUEST_URI'] = '/contact';
        parent::__construct($config, $debug);
    }

    public static function setConfigProperty(?Config $config) : void
    {
        static::$config = $config;
    }

    public static function negotiateLanguage(Language $language, string $requestInstance = 'default') : string
    {
        return parent::negotiateLanguage($language, $requestInstance);
    }
}
