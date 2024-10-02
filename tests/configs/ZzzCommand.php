<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Framework\CLI\Command;

/**
 * NOTE: Ignore Composer warning. This file serves as an example to load classes
 * that cannot be loaded with autoload. And it increases coverage.
 *
 * @see Framework\MVC\App::addCommand()
 */
class ZzzCommand extends Command
{
    public function run() : void
    {
    }
}
