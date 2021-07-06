<?php
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\MVC\Commands;

use Framework\CLI\Command;

class Hello extends Command
{
	protected string $name = 'hello';
	protected string $description = 'Say hello.';

	public function run() : void
	{
	}
}
