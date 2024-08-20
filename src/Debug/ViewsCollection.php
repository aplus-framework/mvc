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
use Framework\Helpers\Isolation;

/**
 * Class ViewsCollection.
 *
 * @package mvc
 */
class ViewsCollection extends Collection
{
    protected string $iconPath = __DIR__ . '/icons/views.svg';

    protected function prepare() : void
    {
        parent::prepare();
        $this->addAction($this->makeActionToggleViewsHints());
    }

    protected function makeActionToggleViewsHints() : string
    {
        \ob_start();
        Isolation::require(__DIR__ . '/Views/toggle-views-hints.php');
        return \ob_get_clean(); // @phpstan-ignore-line
    }
}
