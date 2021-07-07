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
	protected ?string $basePath = null;
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
	protected ?string $currentBlock = null;
	protected ?string $layout = null;

	public function __construct(string $base_path = null, string $extension = '.php')
	{
		if ($base_path !== null) {
			$this->setBasePath($base_path);
		}
		$this->setExtension($extension);
	}

	public function setBasePath(string $base_path)
	{
		$real = \realpath($base_path);
		if ( ! $real || ! \is_dir($real)) {
			throw new InvalidArgumentException("View base path is not a valid directory: {$base_path} ");
		}
		$this->basePath = \rtrim($real, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR;
		return $this;
	}

	public function getBasePath() : ?string
	{
		return $this->basePath;
	}

	public function setExtension(string $extension)
	{
		$this->extension = '.' . \ltrim($extension, '.');
		return $this;
	}

	public function getExtension() : string
	{
		return $this->extension;
	}

	private function getNamespacedFilepath(string $view) : string
	{
		$path = App::locator()->getNamespacedFilepath($view, $this->getExtension());
		if ($path) {
			return $path;
		}
		throw new InvalidArgumentException("Namespaced view path does not match a file: {$view} ");
	}

	protected function makePath(string $view) : string
	{
		if (isset($view[0]) && $view[0] === '\\') {
			return $this->getNamespacedFilepath($view);
		}
		$view = $this->getBasePath() . $view . $this->getExtension();
		$real = \realpath($view);
		if ( ! $real || ! \is_file($real)) {
			throw new InvalidArgumentException("View path does not match a file: {$view} ");
		}
		if ($this->getBasePath() && ! \str_starts_with($real, $this->getBasePath())) {
			throw new InvalidArgumentException("View path out of base path directory: {$real} ");
		}
		return $real;
	}

	public function render(string $view, array $data = []) : string
	{
		$view = $this->makePath($view);
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

	public function extends(string $layout) : void
	{
		$this->layout = $layout;
	}
}
