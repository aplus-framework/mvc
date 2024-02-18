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
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->addAction($this->makeActionToggleViewsHints());
    }

    /**
     * This method contains snippets originally created in CodeIgniter 4 and is
     * MIT licensed.
     *
     * A big thank you to all the contributors, without them this wouldn't be
     * possible.
     *
     * @see https://github.com/codeigniter4/CodeIgniter4/issues/758
     *
     * @return string
     */
    protected function makeActionToggleViewsHints() : string
    {
        \ob_start();
        require __DIR__ . '/Views/toggle-views-hints.php';
        return (string) \ob_get_clean();
    }
}
