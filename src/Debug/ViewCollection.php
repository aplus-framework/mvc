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
 * Class ViewCollection.
 *
 * @package mvc
 */
class ViewCollection extends Collection
{
    protected string $iconPath = __DIR__ . '/icons/views.svg';

    protected function prepare() : void
    {
        parent::prepare();
        $this->addAction($this->makeActionToggleViewsHints());
    }

    protected function makeActionToggleViewsHints() : string
    {
        return (string) \file_get_contents(__DIR__ . '/Views/toggle-views-hints.php');
    }
}
