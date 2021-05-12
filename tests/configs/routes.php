<?php

use Framework\MVC\App;
use Framework\Routing\Collection;

App::router()->serve('http://localhost:8080', static function (Collection $routes) {
	$routes->get('/', static function () {
		echo __METHOD__;
	}, 'home');
	$routes->get('/contact', static function () {
		echo __METHOD__;
	}, 'contact');
	$routes->get('/users', static function () {
		echo __METHOD__;
	}, 'users');
	$routes->get('/users/{num}', static function () {
		echo __METHOD__;
	}, 'users.show');
});

App::router()->serve('http://{subdomain}.domain.tld', static function (Collection $routes) {
	$routes->get('/posts/{title}', static function () {
		echo __METHOD__;
	}, 'sub.posts');
});
