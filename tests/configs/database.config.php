<?php
return [
	'default' => [
		'host' => getenv('DB_HOST'),
		'port' => getenv('DB_PORT'),
		'username' => getenv('DB_USERNAME'),
		'password' => getenv('DB_PASSWORD'),
		'schema' => getenv('DB_SCHEMA'),
	],
];
