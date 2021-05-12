<?php namespace Tests\MVC;

use Framework\MVC\Config;

class AppMock extends \Framework\MVC\App
{
	public static bool $notIsCLI = false;

	public static function init(Config $config) : void
	{
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_HOST'] = 'localhost:8080';
		$_SERVER['REQUEST_URI'] = '/contact';
		parent::init($config);
	}

	public static function prepareRoutes(string $instance = 'default') : void
	{
		parent::prepareRoutes($instance);
	}

	protected static function isCLI() : bool
	{
		return ! static::$notIsCLI && parent::isCLI();
	}

	public static function makeResponseBodyPart($response) : string
	{
		return parent::makeResponseBodyPart($response);
	}

	public static function setConfigProperty(?Config $config) : void
	{
		static::$config = $config;
	}
}
