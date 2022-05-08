<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @var Framework\MVC\View $view
 */
$view->extends('default');
$view->block('contents');
echo 'CONTENTS' . \PHP_EOL;
echo $view->include('footer');
$view->endBlock();
