<?php

use Framework\Debug\ExceptionHandler;

return [
	'default' => [
		'environment' => ExceptionHandler::ENV_PROD,
		'views_dir' => null,
		'log' => true,
	],
];
