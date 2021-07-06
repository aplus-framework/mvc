<?php
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Framework\Log\Logger;

return [
	'default' => [
		'directory' => sys_get_temp_dir(),
		'level' => Logger::DEBUG,
	],
];
