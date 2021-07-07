<?php declare(strict_types=1);
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC;

use InvalidArgumentException;

class View
{
	protected ?string $baseDir = null;
	protected string $extension;
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

	public function __construct(string $baseDir = null, string $extension = '.php')
	{
		if ($baseDir !== null) {
			$this->setBaseDir($baseDir);
		}
		$this->setExtension($extension);
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

	public function render(string $view, array $data = []) : string
	{
		$view = $this->getFilepath($view);
		$data['view'] = $this;
		\ob_start();
		require_isolated($view, $data);
		if ($this->layout !== null) {
			return $this->renderLayout($this->layout, $data);
		}
		return \ob_get_clean();
	}

	protected function renderLayout(string $view, array $data) : string
	{
		$this->layout = null;
		$contents = $this->render($view, $data);
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
			throw new \LogicException('Trying to end a view block when none is open');
		}
		$endedBlock = \array_pop($this->openBlocks);
		$this->blocks[$endedBlock] = \ob_get_clean();
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
	}

	public function isExtending(string $layout) : bool
	{
		return $this->layout === $layout;
	}

	public function include(string $view, array $data = []) : void
	{
		echo $this->render($view, $data);
	}
}
