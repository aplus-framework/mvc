<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Framework\MVC\App;
use Framework\Routing\RouteCollection;

App::router()->serve('http://localhost:8080', static function (RouteCollection $routes) : void {
    $routes->get('/', static function () : void {
        echo __METHOD__;
    }, 'home');
    $routes->get('/contact', static function () : void {
        echo __METHOD__;
    }, 'contact');
    $routes->get('/users', static function () : void {
        echo __METHOD__;
    }, 'users');
    $routes->get('/users/{num}', static function () : void {
        echo __METHOD__;
    }, 'users.show');
});

App::router()
    ->serve('http://{subdomain}.domain.tld', static function (RouteCollection $routes) : void {
        $routes->get('/posts/{title}', static function () : void {
            echo __METHOD__;
        }, 'sub.posts');
    });
