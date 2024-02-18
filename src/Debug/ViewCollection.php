<?php declare(strict_types=1);
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
        require __DIR__ . '/toggle-views-hints.php';
        return (string) \ob_get_clean();
    }
}
