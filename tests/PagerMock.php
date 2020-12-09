<?php namespace Tests\MVC;

use Framework\Pagination\Pager;

class PagerMock extends Pager
{
	protected function prepareURL() : void
	{
		$scheme = 'http://';
		$host = 'localhost:8080';
		$path = '/';
		$this->setURL($scheme . $host . $path);
	}
}
