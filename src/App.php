<?php namespace Framework\MVC;

use Framework\Autoload\Autoloader;
use Framework\Autoload\Locator;
use Framework\Cache\Cache;
use Framework\Database\Database;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\Router;

class App
{
	protected $configs = [];
	protected $services = [];

	public function __construct(array $configs)
	{
		$this->configs = $configs;
	}

	public function getConfigs() : array
	{
		return $this->configs;
	}

	public function getConfig(string $name, string $instance = 'default') : ?array
	{
		return $this->configs[$name][$instance] ?? null;
	}

	public function setConfig(string $name, array $config, string $instance = 'default') : array
	{
		return $this->configs[$name][$instance] = $config;
	}

	public function addConfig(string $name, array $config, string $instance = 'default') : array
	{
		if (isset($this->configs[$name][$instance])) {
			return $this->configs[$name][$instance] = \array_merge_recursive(
				$this->configs[$name][$instance],
				$config
			);
		}
		return $this->configs[$name][$instance] = $config;
	}

	public function getService(string $name, string $instance = 'default')
	{
		return $this->services[$name][$instance] ?? null;
	}

	public function setService(string $name, $service, string $instance = 'default')
	{
		return $this->services[$name][$instance] = $service;
	}

	public function getAutoloader() : Autoloader
	{
		$service = $this->getService('autoloader');
		if ($service) {
			return $service;
		}
		$service = new Autoloader();
		$config = $this->getConfig('autoloader');
		if (isset($config['namespaces'])) {
			$service->setNamespaces($config['namespaces']);
		}
		if (isset($config['classes'])) {
			$service->setClasses($config['classes']);
		}
		return $this->setService('autoloader', $service);
	}

	public function getCache(string $instance = 'default') : Cache
	{
		$service = $this->getService('cache', $instance);
		if ($service) {
			return $service;
		}
		$config = $this->getConfig('cache', $instance);
		if (\strpos($config['driver'], '\\') === false) {
			$config['driver'] = \ucfirst($config['driver']);
			$config['driver'] = "Framework\\Cache\\{$config['driver']}";
		}
		$service = new $config['driver'](
			$config['configs'],
			$config['prefix'],
			$config['serializer']
		);
		return $this->setService('cache', $service, $instance);
	}

	public function getDatabase(string $instance = 'default') : Database
	{
		return $this->getService('database', $instance)
			?? $this->setService(
				'database',
				new Database($this->getConfig('database', $instance)),
				$instance
			);
	}

	public function getLocator() : Locator
	{
		return $this->getService('locator')
			?? $this->setService(
				'locator',
				new Locator($this->getAutoloader())
			);
	}

	public function getRouter() : Router
	{
		return $this->getService('router')
			?? $this->setService('router', new Router());
	}

	public function getRequest() : Request
	{
		return $this->getService('request')
			?? $this->setService('request', new Request());
	}

	public function getResponse() : Response
	{
		return $this->getService('response')
			?? $this->setService('response', new Response($this->getRequest()));
	}

	public function getView(string $instance = 'default') : View
	{
		$service = $this->getService('view', $instance);
		if ($service) {
			return $service;
		}
		$service = new View();
		$config = $this->getConfig('view', $instance);
		if (isset($config['base_path'])) {
			$service->setBasePath($config['base_path']);
		}
		if (isset($config['extension'])) {
			$service->setExtension($config['extension']);
		}
		return $this->setService('view', $service, $instance);
	}

	public function run() : void
	{
		\ob_start();
		$this->prepareConfigs();
		$this->getAutoloader();
		$this->prepareRoutes();
		$response = $this->getRouter()->match(
			$this->getRequest()->getMethod(),
			$this->getRequest()->getURL()
		)->run($this->getRequest(), $this->getResponse());
		$response = $this->makeResponseBodyPart($response);
		$this->getResponse()->appendBody($response)->send();
	}

	protected function prepareConfigs(string $instance = 'default') : array
	{
		$files = $this->getConfig('configs', $instance);
		if ( ! $files) {
			return [];
		}
		$files = \array_unique($files);
		foreach ($files as $file) {
			$this->mergeFileConfigs($file);
		}
		return $files;
	}

	protected function mergeFileConfigs(string $file) : array
	{
		if ( ! \is_file($file)) {
			throw new \RuntimeException(
				"Invalid config file path: {$file}"
			);
		}
		unset($config);
		require $file;
		if ( ! isset($config) || ! \is_array($config)) {
			throw new \LogicException(
				"Configs file must have a config array variable: {$file}"
			);
		}
		foreach ($config as $service => $instances) {
			if ( ! \is_array($instances)) {
				throw new \LogicException(
					"Config service name '{$service}' must be an array on file '{$file}'"
				);
			}
			foreach ($instances as $instance => $config) {
				if ( ! \is_array($config)) {
					throw new \LogicException(
						"Config instance name '{$instance}' of service name '{$service}' must be an array on file '{$file}'"
					);
				}
				$this->addConfig($service, $config, $instance);
			}
		}
		return $this->getConfigs();
	}

	protected function prepareRoutes(string $instance = 'default') : array
	{
		$files = $this->getConfig('routes', $instance);
		if ( ! $files) {
			return [];
		}
		$files = \array_unique($files);
		$router = $this->getRouter();
		foreach ($files as $file) {
			require $file;
		}
		return $files;
	}

	/**
	 * @param mixed $response Scalar or null data returned in a matched route
	 *
	 * @see \Framework\Routing\Route::run()
	 *
	 * @return string
	 */
	protected function makeResponseBodyPart($response) : string
	{
		if ($response === null || $response instanceof Response) {
			return '';
		}
		if (\is_scalar($response)) {
			return $response;
		}
		if (\is_object($response) && \method_exists($response, '__toString')) {
			return $response;
		}
		$type = \gettype($response);
		if ($type === 'object') {
			$type = \get_class($response);
		}
		$action = $this->getRouter()->getMatchedRoute()->getAction();
		if ($action instanceof \Closure) {
			$action = '{closure}';
		}
		throw new \LogicException("Invalid return type '{$type}' on matched route '{$action}'");
	}
}
