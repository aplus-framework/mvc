<?php namespace Tests\MVC;

use PHPUnit\Framework\TestCase;

final class PresenterControllerTest extends TestCase
{
	public function testMethods() : void
	{
		$controller = new PresenterControllerMock();
		self::assertNull($controller->index());
		self::assertNull($controller->new());
		self::assertNull($controller->create());
		self::assertSame(5, $controller->show(5));
		self::assertSame(5, $controller->edit(5));
		self::assertSame(5, $controller->update(5));
		self::assertSame(5, $controller->remove(5));
		self::assertSame(5, $controller->delete(5));
	}
}
