<?php namespace Tests\MVC;

use PHPUnit\Framework\TestCase;

class PresenterControllerTest extends TestCase
{
	public function testMethods()
	{
		$controller = new PresenterControllerMock();
		$this->assertNull($controller->index());
		$this->assertNull($controller->new());
		$this->assertNull($controller->create());
		$this->assertEquals(5, $controller->show(5));
		$this->assertEquals(5, $controller->edit(5));
		$this->assertEquals(5, $controller->update(5));
		$this->assertEquals(5, $controller->remove(5));
		$this->assertEquals(5, $controller->delete(5));
	}
}
