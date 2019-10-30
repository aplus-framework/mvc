<?php namespace Tests\MVC;

use Framework\HTTP\Request;

class AppMock extends \Framework\MVC\App
{
	public static function getRequest() : Request
	{
		return static::getService('request')
			?? static::setService('request', new class() extends Request {
				protected function prepareStatusLine()
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

	public static function prepareRoutes(string $instance = 'default') : array
	{
		return parent::prepareRoutes($instance);
	}
}
