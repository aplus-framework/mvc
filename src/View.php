<?php
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
	protected ?string $path;
	/**
	 * @var array|mixed[]
	 */
	protected array $data;
	protected ?string $basePath = null;
	protected string $extension;
	/**
	 * @var array|string[]
	 */
	protected array $sections = [];
	protected ?string $currentSection = null;
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
		$this->path = $this->makePath($view);
		$this->data = $data;
		unset($view, $data);
		\ob_start();
		\extract($this->data, \EXTR_SKIP);
		require $this->path;
		if ($this->layout !== null) {
			return $this->renderLayout($this->layout);
		}
		$this->path = null;
		$this->data = [];
		return \ob_get_clean();
	}

	protected function renderLayout(string $layout) : string
	{
		$this->layout = null;
		$contents = $this->render($layout, $this->data);
		\ob_end_clean();
		return $contents;
	}

	public function startSection(string $name) : void
	{
		$this->currentSection = $name;
		\ob_start();
	}

	public function endSection() : void
	{
		$this->sections[$this->currentSection] = \ob_get_clean();
		$this->currentSection = null;
	}

	public function renderSection(string $name) : string
	{
		return $this->sections[$name] ?? '';
	}

	public function extends(string $layout) : void
	{
		$this->layout = $layout;
	}

	public function escape(?string $text, string $encoding = 'UTF-8') : string
	{
		$text = (string) $text;
		return empty($text)
			? $text
			: \htmlspecialchars($text, \ENT_QUOTES | \ENT_HTML5, $encoding);
	}
}
