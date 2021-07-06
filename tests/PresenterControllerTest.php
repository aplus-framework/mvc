<?php
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\MVC;

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
