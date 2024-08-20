<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\MVC\Debug;

use Framework\MVC\Debug\AppCollection;
use PHPUnit\Framework\TestCase;

final class AppCollectionTest extends TestCase
{
    protected AppCollection $collection;

    protected function setUp() : void
    {
        $this->collection = new AppCollection('App');
    }

    public function testIcon() : void
    {
        self::assertStringStartsWith('<svg ', $this->collection->getIcon());
    }
}
