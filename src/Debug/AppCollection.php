<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC\Debug;

use Framework\Debug\Collection;

/**
 * Class AppCollection.
 *
 * @package mvc
 */
class AppCollection extends Collection
{
    protected string $iconPath = __DIR__ . '/icons/app.svg';
}
