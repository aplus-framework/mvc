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
use Framework\MVC\Debug\ViewsCollector;
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
    protected string $layout;
    protected ?string $openBlock;
    /**
     * @var array<int,string>
     */
    protected array $openBlocks = [];
    /**
     * @var array<int,string>
     */
    protected array $layoutsOpen = [];
    /**
     * @var array<string,string>
     */
    protected array $blocks;
    protected string $currentView;
    protected ViewsCollector $debugCollector;
    protected string $layoutPrefix = '';
    protected string $includePrefix = '';
    protected bool $inInclude = false;
    protected bool $showDebugComments = true;
    /**
     * @var array<string>
     */
    protected array $viewsPaths = [];
    protected string $instanceName;
    protected bool $throwExceptionsInDestructor = true;

    public function __construct(?string $baseDir = null, string $extension = '.php')
    {
        if ($baseDir !== null) {
            $this->setBaseDir($baseDir);
        }
        $this->setExtension($extension);
    }

    public function __destruct()
    {
        if ($this->isThrowExceptionsInDestructor() && $this->openBlocks) {
            throw new LogicException(
                'Trying to destruct a View instance while the following blocks stayed open: '
                . \implode(', ', \array_map(static function ($name) {
                    return "'{$name}'";
                }, $this->openBlocks))
            );
        }
    }

    /**
     * Tells whether it is able to throw exceptions in the destructor.
     *
     * @since 4.2
     *
     * @return bool
     */
    public function isThrowExceptionsInDestructor() : bool
    {
        return $this->throwExceptionsInDestructor;
    }

    /**
     * Enables/disables exceptions in the destructor.
     *
     * @since 4.2
     *
     * @param bool $active True to throw exceptions, false otherwise
     *
     * @return static
     */
    public function setThrowExceptionsInDestructor(bool $active = true) : static
    {
        $this->throwExceptionsInDestructor = $active;
        return $this;
    }

    /**
     * Sets the base directory where the views files are located.
     *
     * @param string $baseDir
     *
     * @return static
     */
    public function setBaseDir(string $baseDir) : static
    {
        $real = \realpath($baseDir);
        if (!$real || !\is_dir($real)) {
            throw new InvalidArgumentException("View base dir is not a valid directory: {$baseDir} ");
        }
        $this->baseDir = \rtrim($real, '\/ ') . \DIRECTORY_SEPARATOR;
        return $this;
    }

    /**
     * Get the base directory.
     *
     * @return string|null
     */
    public function getBaseDir() : ?string
    {
        return $this->baseDir;
    }

    /**
     * Set the extension of views files.
     *
     * @param string $extension
     *
     * @return static
     */
    public function setExtension(string $extension) : static
    {
        $this->extension = '.' . \ltrim($extension, '.');
        return $this;
    }

    /**
     * Get the extension of view files.
     *
     * @return string
     */
    public function getExtension() : string
    {
        return $this->extension;
    }

    /**
     * Set the name of a directory for layouts within the base directory.
     *
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
     * Get the name of the layouts directory.
     *
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
            : \trim($prefix, '\/') . \DIRECTORY_SEPARATOR;
    }

    /**
     * Set the name of a directory for includes within the base directory.
     *
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
     * Get the name of the includes directory.
     *
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
        throw new InvalidArgumentException("Namespaced view path does not match a file: {$view}");
    }

    protected function getFilepath(string $view) : string
    {
        if (isset($view[0]) && $view[0] === '\\') {
            return $this->getNamespacedFilepath($view);
        }
        $view = $this->getBaseDir() . $view . $this->getExtension();
        $real = \realpath($view);
        if (!$real || !\is_file($real)) {
            throw new InvalidArgumentException("View path does not match a file: {$view}");
        }
        if ($this->getBaseDir() && !\str_starts_with($real, $this->getBaseDir())) {
            throw new InvalidArgumentException("View path out of base directory: {$real}");
        }
        return $real;
    }

    /**
     * Render a view file.
     *
     * @param string $view View path within the base directory
     * @param array<string,mixed> $data Data passed to the view. The array keys
     * will be variables
     *
     * @return string
     */
    public function render(string $view, array $data = []) : string
    {
        $debug = isset($this->debugCollector);
        if ($debug) {
            $start = \microtime(true);
        }
        $this->currentView = $view;
        $contents = $this->getContents($view, $data);
        if (isset($this->layout)) {
            $layout = $this->layout;
            unset($this->layout);
            $this->layoutsOpen[] = $layout;
            $contents = $this->render($layout, $data);
        }
        if ($debug) {
            $type = 'render';
            if ($this->layoutsOpen) {
                \array_shift($this->layoutsOpen);
                $type = 'layout';
            }
            $this->setDebugData($view, $start, $type);
            if ($this->isShowingDebugComments()) {
                $path = $this->getCommentPath($view);
                $contents = '<!-- DEBUG-VIEW START ' . $path . ' -->'
                    . \PHP_EOL . $contents . \PHP_EOL
                    . '<!-- DEBUG-VIEW ENDED ' . $path . ' -->';
            }
        }
        return $contents;
    }

    protected function setDebugData(string $file, float $start, string $type) : static
    {
        $end = \microtime(true);
        $this->debugCollector->addData([
            'start' => $start,
            'end' => $end,
            'file' => $file,
            'filepath' => $this->getFilepath($file),
            'type' => $type,
        ]);
        return $this;
    }

    /**
     * Extends a layout.
     *
     * @param string $layout The name of the file within the layouts directory
     * @param string|null $openBlock Optionally opens and closes this block automatically
     *
     * @return static
     */
    public function extends(string $layout, ?string $openBlock = null) : static
    {
        $this->layout = $this->getLayoutPrefix() . $layout;
        $this->openBlock = $openBlock;
        if ($openBlock !== null) {
            $this->block($openBlock);
        }
        return $this;
    }

    /**
     * Extends a layout without prefix.
     *
     * @param string $layout The name of the file within the base directory
     *
     * @return static
     */
    public function extendsWithoutPrefix(string $layout) : static
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Tells whether the current contents is inside a layout.
     *
     * @param string $layout
     *
     * @return bool
     */
    public function inLayout(string $layout) : bool
    {
        return isset($this->layout) && $this->layout === $layout;
    }

    /**
     * Open a block.
     *
     * @param string $name Block name
     *
     * @return static
     */
    public function block(string $name) : static
    {
        $this->openBlocks[] = $name;
        \ob_start();
        if (isset($this->debugCollector) && $this->isShowingDebugComments()) {
            if (isset($this->currentView)) {
                $name = $this->currentView . '::' . $name;
                $name = $this->getInstanceNameWithPath($name);
            }
            echo \PHP_EOL . '<!-- DEBUG-VIEW START ' . $name . ' -->' . \PHP_EOL;
        }
        return $this;
    }

    /**
     * Close an open block.
     *
     * @return static
     */
    public function endBlock() : static
    {
        if (empty($this->openBlocks)) {
            throw new LogicException('Trying to end a view block when none is open');
        }
        $name = \array_pop($this->openBlocks);
        if (isset($this->debugCollector) && $this->isShowingDebugComments()) {
            $block = $name;
            if (isset($this->currentView)) {
                $block = $this->currentView . '::' . $name;
                $block = $this->getInstanceNameWithPath($block);
            }
            echo \PHP_EOL . '<!-- DEBUG-VIEW ENDED ' . $block . ' -->' . \PHP_EOL;
        }
        $contents = \ob_get_clean();
        if (!isset($this->blocks[$name])) {
            $this->blocks[$name] = $contents; // @phpstan-ignore-line
        }
        return $this;
    }

    /**
     * Render a block.
     *
     * @param string $name Block name
     *
     * @return string|null
     */
    public function renderBlock(string $name) : ?string
    {
        return $this->blocks[$name] ?? null;
    }

    /**
     * Remove a block.
     *
     * @param string $name Block name
     *
     * @return static
     */
    public function removeBlock(string $name) : static
    {
        unset($this->blocks[$name]);
        return $this;
    }

    /**
     * Tells whether a given block is set.
     *
     * @param string $name Block name
     *
     * @return bool
     */
    public function hasBlock(string $name) : bool
    {
        return isset($this->blocks[$name]);
    }

    /**
     * Tells whether the current content is inside a block.
     *
     * @param string $name Block name
     *
     * @return bool
     */
    public function inBlock(string $name) : bool
    {
        return $this->currentBlock() === $name;
    }

    /**
     * Tells the name of the current block.
     *
     * @return string|null
     */
    public function currentBlock() : ?string
    {
        if ($this->openBlocks) {
            return $this->openBlocks[\array_key_last($this->openBlocks)];
        }
        return null;
    }

    /**
     * Returns the contents of an include.
     *
     * @param string $view The path of the file within the includes directory
     * @param array<string,mixed> $data Data passed to the view. The array keys
     * will be variables
     *
     * @return string
     */
    public function include(string $view, array $data = []) : string
    {
        $view = $this->getIncludePrefix() . $view;
        if (isset($this->debugCollector)) {
            return $this->getIncludeContentsWithDebug($view, $data);
        }
        return $this->getIncludeContents($view, $data);
    }

    protected function involveInclude(string $view, string $contents) : string
    {
        $path = $this->getCommentPath($view);
        return \PHP_EOL . '<!-- DEBUG-VIEW START ' . $path . ' -->'
            . \PHP_EOL . $contents . \PHP_EOL
            . '<!-- DEBUG-VIEW ENDED ' . $path . ' -->' . \PHP_EOL;
    }

    /**
     * Returns the contents of an include without prefix.
     *
     * @param string $view The path of the file within the base directory
     * @param array<string,mixed> $data Data passed to the view. The array keys
     * will be variables
     *
     * @return string
     */
    public function includeWithoutPrefix(string $view, array $data = []) : string
    {
        if (isset($this->debugCollector)) {
            return $this->getIncludeContentsWithDebug($view, $data);
        }
        return $this->getIncludeContents($view, $data);
    }

    /**
     * @param string $view
     * @param array<string,mixed> $data
     *
     * @return string
     */
    protected function getIncludeContentsWithDebug(string $view, array $data = []) : string
    {
        $start = \microtime(true);
        $this->inInclude = true;
        $contents = $this->getContents($view, $data);
        $this->inInclude = false;
        $this->setDebugData($view, $start, 'include');
        if (!$this->isShowingDebugComments()) {
            return $contents;
        }
        return $this->involveInclude($view, $contents);
    }

    /**
     * @param string $view
     * @param array<string,mixed> $data
     *
     * @return string
     */
    protected function getIncludeContents(string $view, array $data = []) : string
    {
        $this->inInclude = true;
        $contents = $this->getContents($view, $data);
        $this->inInclude = false;
        return $contents;
    }

    /**
     * @param string $view
     * @param array<string,mixed> $data
     *
     * @return string
     */
    protected function getContents(string $view, array $data) : string
    {
        $data['view'] = $this;
        \ob_start();
        Isolation::require($this->getFilepath($view), $data);
        if (isset($this->openBlock) && !$this->inInclude) {
            $this->openBlock = null;
            $this->endBlock();
        }
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    public function getInstanceName() : string
    {
        return $this->instanceName;
    }

    public function getInstanceNameWithPath(string $name) : string
    {
        return $this->getInstanceName() . ':' . $name;
    }

    public function setInstanceName(string $instanceName) : static
    {
        $this->instanceName = $instanceName;
        return $this;
    }

    protected function getCommentPath(string $name) : string
    {
        $count = null;
        foreach ($this->viewsPaths as $view) {
            if ($view === $name) {
                $count++;
            }
        }
        $this->viewsPaths[] = $name;
        if ($count) {
            $count = ':' . ($count + 1);
        }
        return $this->getInstanceNameWithPath($name) . $count;
    }

    public function setDebugCollector(ViewsCollector $debugCollector) : static
    {
        $this->debugCollector = $debugCollector;
        $this->debugCollector->setView($this);
        return $this;
    }

    /**
     * Tells if it is showing debug comments when in debug mode.
     *
     * @since 3.2
     *
     * @return bool
     */
    public function isShowingDebugComments() : bool
    {
        return $this->showDebugComments;
    }

    /**
     * Enable debug comments when in debug mode.
     *
     * @since 3.2
     *
     * @return static
     */
    public function enableDebugComments() : static
    {
        $this->showDebugComments = true;
        return $this;
    }

    /**
     * Disable debug comments when in debug mode.
     *
     * @since 3.2
     *
     * @return static
     */
    public function disableDebugComments() : static
    {
        $this->showDebugComments = false;
        return $this;
    }
}
