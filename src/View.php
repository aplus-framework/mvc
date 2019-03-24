<?php namespace Framework\MVC;

class View
{
	protected $path;
	protected $data;
	protected $basePath;
	protected $extension;

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
			throw new \InvalidArgumentException("View base path is not a directory: {$base_path} ");
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

	protected function makePath(string $view) : string
	{
		$view = $this->getBasePath() . $view . $this->getExtension();
		$real = \realpath($view);
		if ( ! $real || ! \is_file($real)) {
			throw new \InvalidArgumentException("View path does not match a file: {$view} ");
		}
		if ($this->getBasePath() && \strpos($real, $this->getBasePath()) !== 0) {
			throw new \InvalidArgumentException("View path out of base path directory: {$real} ");
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
		$this->path = null;
		$this->data = null;
		return \ob_get_clean();
	}
}
