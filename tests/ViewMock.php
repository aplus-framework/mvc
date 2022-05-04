<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\MVC;

use Framework\MVC\View;

class ViewMock extends View
{
    public function getNamespacedFilepath(string $view) : string
    {
        return parent::getNamespacedFilepath($view);
    }

    public function getFilepath(string $view) : string
    {
        return parent::getFilepath($view);
    }
}
