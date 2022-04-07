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
use Framework\MVC\Debug\ViewCollector;
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
    protected bool $cancelNextBlock = false;
    protected ?string $extendsWithBlock;
    protected ViewCollector $debugCollector;
    protected string $currentView;
    protected string $currentLayout;

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
        $debug = isset($this->debugCollector);
        $contents = '';
        if ($debug) {
            $start = \microtime(true);
            $type = isset($this->currentLayout) ? 'Layout' : 'View';
            $contents .= '<!-- ' . $type . ' ' . $view . ' start -->';
        }
        $this->currentView = $view;
        $viewFile = $this->getFilepath($view);
        $variables['view'] = $this;
        \ob_start();
        Isolation::require($viewFile, $variables);
        if ($this->layout !== null) {
            $contents .= $this->renderLayout($this->layout, $variables);
            if ($debug) {
                $contents .= '<!-- ' . $type . ' ' . $view . ' end -->'; // @phpstan-ignore-line
                $this->setDebugData($view, $start, $viewFile, $type); // @phpstan-ignore-line
            }
            return $contents;
        }
        $buffer = \ob_get_clean();
        if ($buffer === false) {
            App::logger()->error(
                'View::render could not get ob contents of "' . $viewFile . '"'
            );
        }
        $contents .= $buffer;
        if ($debug) {
            $contents .= '<!-- ' . $type . ' ' . $view . ' end -->'; // @phpstan-ignore-line
            $this->setDebugData($view, $start, $viewFile, $type); // @phpstan-ignore-line
        }
        return $contents;
    }

    protected function setDebugData(string $file, float $start, string $filepath, string $type) : static
    {
        $end = \microtime(true);
        $this->debugCollector->addData([
            'start' => $start,
            'end' => $end,
            'file' => $file,
            'filepath' => $filepath,
            'type' => $type,
        ]);
        return $this;
    }

    /**
     * @param string $view
     * @param array<string,mixed> $variables
     *
     * @return string
     */
    protected function renderLayout(string $view, array $variables) : string
    {
        $this->currentLayout = $view;
        if ($this->layoutUsePrefix) {
            $view = $this->getLayoutPrefix() . $view;
        }
        $this->layout = null;
        $this->layoutUsePrefix = true;
        if (isset($this->extendsWithBlock)) {
            unset($this->extendsWithBlock);
            $this->endBlock();
        }
        $contents = $this->render($view, $variables);
        \ob_end_clean();
        return $contents;
    }

    public function block(string $name, bool $overwrite = true) : static
    {
        \ob_start();
        if ($overwrite === false && $this->hasBlock($name)) {
            $this->cancelNextBlock = true;
            return $this;
        }
        if (isset($this->debugCollector)) {
            echo '<!-- Block ' . $this->currentView . ':' . $name . ' start -->';
        }
        $this->openBlocks[] = $name;
        return $this;
    }

    public function endBlock() : static
    {
        if ($this->cancelNextBlock) {
            $this->cancelNextBlock = false;
            \ob_get_clean();
            return $this;
        }
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
        if (isset($this->debugCollector)) {
            $this->blocks[$endedBlock] .= '<!-- Block ' . $this->currentView
                . ':' . $endedBlock . ' end -->';
        }
        return $this;
    }

    public function renderBlock(string $name) : ?string
    {
        if ( ! $this->hasBlock($name)) {
            $trace = \debug_backtrace()[0];
            \trigger_error(
                'Trying to render block "' . $name . '" that is not set in '
                . $trace['file'] . ' on  line ' . $trace['line'], // @phpstan-ignore-line
                \E_USER_WARNING
            );
        }
        return $this->blocks[$name] ?? null;
    }

    public function hasBlock(string $name) : bool
    {
        return isset($this->blocks[$name]);
    }

    public function removeBlock(string $name) : static
    {
        if ( ! $this->hasBlock($name)) {
            $trace = \debug_backtrace()[0];
            \trigger_error(
                'Trying to remove block "' . $name . '" that is not set in '
                . $trace['file'] . ' on  line ' . $trace['line'], // @phpstan-ignore-line
                \E_USER_WARNING
            );
        }
        unset($this->blocks[$name]);
        return $this;
    }

    public function inBlock(string $name) : bool
    {
        return ! empty($this->openBlocks)
            && \in_array($name, $this->openBlocks, true);
    }

    public function currentBlock() : ?string
    {
        if ($this->openBlocks) {
            return $this->openBlocks[\array_key_last($this->openBlocks)];
        }
        return null;
    }

    public function extends(string $layout, string $openBlock = null) : static
    {
        $this->layout = $layout;
        $this->layoutUsePrefix = true;
        if ($openBlock !== null) {
            $this->extendsWithBlock = $openBlock;
            $this->block($openBlock);
        }
        return $this;
    }

    public function extendsWithoutPrefix(string $layout) : static
    {
        $this->layout = $layout;
        $this->layoutUsePrefix = false;
        return $this;
    }

    public function isExtending(string $layout) : bool
    {
        return $this->layout === $layout;
    }

    /**
     * @param string $view
     * @param array<string,mixed> $variables
     *
     * @return static
     */
    public function include(string $view, array $variables = []) : static
    {
        echo $this->render($this->getIncludePrefix() . $view, $variables);
        return $this;
    }

    /**
     * @param string $view
     * @param array<string,mixed> $variables
     *
     * @return static
     */
    public function includeWithoutPrefix(string $view, array $variables = []) : static
    {
        echo $this->render($view, $variables);
        return $this;
    }

    public function setDebugCollector(ViewCollector $debugCollector) : static
    {
        $this->debugCollector = $debugCollector;
        $this->debugCollector->setConfig([
            'baseDir' => $this->getBaseDir(),
            'extension' => $this->getExtension(),
            'layoutPrefix' => $this->getLayoutPrefix(),
            'includePrefix' => $this->getIncludePrefix(),
        ]);
        return $this;
    }
}
