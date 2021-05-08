<?php

use Framework\MVC\App;
use Framework\Routing\Collection;

App::router()->serve('http://localhost', static function (Collection $routes) {
	$routes->get('/', static function () {
		echo __METHOD__;
	}, 'home');
});
