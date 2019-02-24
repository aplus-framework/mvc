<?php namespace Tests\MVC;

use Framework\MVC\App;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
	/**
	 * @var \Framework\MVC\App
	 */
	protected $app;

	public function setup()
	{
		$this->app = new App();
	}

	public function testSample()
	{
		$this->assertEquals(
			'Framework\MVC\App::test',
			$this->app->test()
		);
	}
}
