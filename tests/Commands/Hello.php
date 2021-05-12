<?php namespace Tests\MVC\Commands;

use Framework\CLI\Command;

class Hello extends Command
{
	protected string $name = 'hello';
	protected string $description = 'Say hello.';

	public function run() : void
	{
	}
}
