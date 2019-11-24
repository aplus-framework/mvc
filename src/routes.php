<?php

use Framework\Routing\Collection;

App::router()->serve('http://localhost', function (Collection $routes) {
	$routes->get('/', function () {
		echo __METHOD__;
	});
});
