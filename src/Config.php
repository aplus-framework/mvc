<?php namespace Framework\MVC;

use LogicException;

class Config
{
	protected array $configs = [];
	protected string $configsDir;

	public function __construct(string $directory)
	{
		$this->setDir($directory);
	}

	public function set(
		string $name,
		array $config,
		string $instance = 'default'
	) : array {
		return $this->configs[$name][$instance] = $config;
	}

	public function get(string $name, string $instance = 'default') : ?array
	{
		if (empty($this->configs[$name])) {
			$this->load($name);
		}
		return $this->configs[$name][$instance] ?? null;
	}

	public function add(string $name, array $config, string $instance = 'default') : array
	{
		if (isset($this->configs[$name][$instance])) {
			return $this->configs[$name][$instance] = \array_replace_recursive(
				$this->configs[$name][$instance],
				$config
			);
		}
		return $this->configs[$name][$instance] = $config;
	}

	public function setMany(array $configs) : void
	{
		foreach ($configs as $name => $values) {
			foreach ($values as $instance => $config) {
				$this->set($name, $config, $instance);
			}
		}
	}

	public function getAll() : array
	{
		return $this->configs;
	}

	protected function setDir(string $directory) : void
	{
		$dir = \realpath($directory);
		if ($dir === false || ! \is_dir($dir)) {
			throw new LogicException('Config directory not found: ' . $directory);
		}
		$this->configsDir = $dir . \DIRECTORY_SEPARATOR;
	}

	public function load(string $name) : void
	{
		$filename = $this->configsDir . $name . '.config.php';
		$filename = \realpath($filename);
		if ($filename === false || ! \is_file($filename)) {
			throw new LogicException('Config file not found: ' . $name);
		}
		$configs = require $filename;
		$this->setMany([$name => $configs]);
	}
}
