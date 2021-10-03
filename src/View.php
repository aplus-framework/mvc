<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC;

use Framework\Helpers\Isolation;
use InvalidArgumentException;
use LogicException;

/**
 * Class View.
 *
 * @package mvc
 */
class View
{
    protected ?string $baseDir = null;
    protected string $extension;
    protected string $layoutPrefix = '';
    protected string $includePrefix = '';
    /**
     * The blocks with names as keys and output buffer contents as values.
     *
     * @var array<string,string>
     */
    protected array $blocks = [];
    /**
     * @var array<int,string>
     */
    protected array $openBlocks = [];
    protected ?string $layout = null;
    protected bool $layoutUsePrefix = true;

    public function __construct(string $baseDir = null, string $extension = '.php')
    {
        if ($baseDir !== null) {
            $this->setBaseDir($baseDir);
        }
        $this->setExtension($extension);
    }

    public function __destruct()
    {
        if ($this->openBlocks) {
            throw new LogicException(
                'Trying to destruct a View instance while the following blocks stayed open: '
                . \implode(', ', \array_map(static function ($name) {
                    return "'{$name}'";
                }, $this->openBlocks))
            );
        }
    }

    public function setBaseDir(string $baseDir) : static
    {
        $real = \realpath($baseDir);
        if ( ! $real || ! \is_dir($real)) {
            throw new InvalidArgumentException("View base dir is not a valid directory: {$baseDir} ");
        }
        $this->baseDir = \rtrim($real, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR;
        return $this;
    }

    public function getBaseDir() : ?string
    {
        return $this->baseDir;
    }

    public function setExtension(string $extension) : static
    {
        $this->extension = '.' . \ltrim($extension, '.');
        return $this;
    }

    public function getExtension() : string
    {
        return $this->extension;
    }

    /**
     * @param string $prefix
     *
     * @return static
     */
    public function setLayoutPrefix(string $prefix) : static
    {
        $this->layoutPrefix = $this->makeDirectoryPrefix($prefix);
        return $this;
    }

    /**
     * @return string
     */
    public function getLayoutPrefix() : string
    {
        return $this->layoutPrefix;
    }

    protected function makeDirectoryPrefix(string $prefix) : string
    {
        return $prefix === ''
            ? ''
            : \trim($prefix, '\\/') . \DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $prefix
     *
     * @return static
     */
    public function setIncludePrefix(string $prefix) : static
    {
        $this->includePrefix = $this->makeDirectoryPrefix($prefix);
        return $this;
    }

    /**
     * @return string
     */
    public function getIncludePrefix() : string
    {
        return $this->includePrefix;
    }

    protected function getNamespacedFilepath(string $view) : string
    {
        $path = App::locator()->getNamespacedFilepath($view, $this->getExtension());
        if ($path) {
            return $path;
        }
        throw new InvalidArgumentException("Namespaced view path does not match a file: {$view} ");
    }

    protected function getFilepath(string $view) : string
    {
        if (isset($view[0]) && $view[0] === '\\') {
            return $this->getNamespacedFilepath($view);
        }
        $view = $this->getBaseDir() . $view . $this->getExtension();
        $real = \realpath($view);
        if ( ! $real || ! \is_file($real)) {
            throw new InvalidArgumentException("View path does not match a file: {$view} ");
        }
        if ($this->getBaseDir() && ! \str_starts_with($real, $this->getBaseDir())) {
            throw new InvalidArgumentException("View path out of base directory: {$real} ");
        }
        return $real;
    }

    /**
     * @param string $view
     * @param array<string,mixed> $variables
     *
     * @return string
     */
    public function render(string $view, array $variables = []) : string
    {
        $view = $this->getFilepath($view);
        $variables['view'] = $this;
        \ob_start();
        Isolation::require($view, $variables);
        if ($this->layout !== null) {
            return $this->renderLayout($this->layout, $variables);
        }
        $contents = \ob_get_clean();
        if ($contents === false) {
            App::logger()->error(
                'View::render could not get ob contents of "' . $view . '"'
            );
        }
        return (string) $contents;
    }

    /**
     * @param string $view
     * @param array<string,mixed> $variables
     *
     * @return string
     */
    protected function renderLayout(string $view, array $variables) : string
    {
        if ($this->layoutUsePrefix) {
            $view = $this->getLayoutPrefix() . $view;
        }
        $this->layout = null;
        $this->layoutUsePrefix = true;
        $contents = $this->render($view, $variables);
        \ob_end_clean();
        return $contents;
    }

    public function block(string $name) : void
    {
        $this->openBlocks[] = $name;
        \ob_start();
    }

    public function endBlock() : void
    {
        if (empty($this->openBlocks)) {
            throw new LogicException('Trying to end a view block when none is open');
        }
        $endedBlock = \array_pop($this->openBlocks);
        $contents = \ob_get_clean();
        if ($contents === false) {
            App::logger()->error(
                'View::endBlock could not get ob contents of "' . $endedBlock . '"'
            );
        }
        $this->blocks[$endedBlock] = (string) $contents;
    }

    public function renderBlock(string $name) : string
    {
        return $this->blocks[$name] ?? '';
    }

    public function hasBlock(string $name) : bool
    {
        return isset($this->blocks[$name]);
    }

    public function removeBlock(string $name) : void
    {
        if ( ! $this->hasBlock($name)) {
            \trigger_error(
                'Trying to remove a block that is not set: ' . $name,
                \E_USER_WARNING
            );
        }
        unset($this->blocks[$name]);
    }

    public function inBlock(string $name) : bool
    {
        return ! empty($this->openBlocks)
            && \in_array($name, $this->openBlocks, true);
    }

    public function extends(string $layout) : void
    {
        $this->layout = $layout;
        $this->layoutUsePrefix = true;
    }

    public function extendsWithoutPrefix(string $layout) : void
    {
        $this->layout = $layout;
        $this->layoutUsePrefix = false;
    }

    public function isExtending(string $layout) : bool
    {
        return $this->layout === $layout;
    }

    /**
     * @param string $view
     * @param array<string,mixed> $variables
     */
    public function include(string $view, array $variables = []) : void
    {
        echo $this->render($this->getIncludePrefix() . $view, $variables);
    }

    /**
     * @param string $view
     * @param array<string,mixed> $variables
     */
    public function includeWithoutPrefix(string $view, array $variables = []) : void
    {
        echo $this->render($view, $variables);
    }
}
