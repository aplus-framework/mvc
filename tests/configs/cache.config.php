<?php

use Framework\Cache\Cache;
use Framework\Cache\Files;

return [
	'default' => [
		'class' => Files::class,
		'configs' => [
			'directory' => sys_get_temp_dir(),
		],
		'prefix' => null,
		'serializer' => Cache::SERIALIZER_PHP,
	],
];
