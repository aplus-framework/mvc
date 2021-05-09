<?php namespace Tests\MVC;

use Framework\HTTP\Request;
use Framework\MVC\Config;

class AppMock extends \Framework\MVC\App
{
	public static bool $notIsCLI = false;

	public static function request() : Request
	{
		return static::getService('request')
			?? static::setService('request', new class() extends Request {
				protected function prepareStatusLine() : void
				{
					$this->setProtocol('HTTP/1.1');
					$this->setMethod('GET');
					$url = $this->isSecure() ? 'https' : 'http';
					$url .= '://' . 'localhost';
					//$url .= ':' . $this->getPort();
					$url .= '/';
					$this->setURL($url);
					$this->setHost($this->getURL()->getHost());
				}
			});
	}

	public static function prepareRoutes(string $instance = 'default') : void
	{
		parent::prepareRoutes($instance);
	}

	public static function loadHelpers() : void
	{
		parent::loadHelpers();
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
