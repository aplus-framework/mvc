<?php

use Framework\Log\Logger;

return [
	'default' => [
		'directory' => sys_get_temp_dir(),
		'level' => Logger::DEBUG,
	],
];
