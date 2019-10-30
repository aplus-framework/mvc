<?php

use Framework\Routing\Collection;

/**
 * @var \Framework\Routing\Router $router
 */
$router->serve('http://localhost', function (Collection $routes) {
	$routes->get('/', function () {
		echo __METHOD__;
	});
});
