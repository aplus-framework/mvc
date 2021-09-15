<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\MVC;

use Framework\MVC\Entity;
use PHPUnit\Framework\TestCase;

/**
 * Class EntityTest.
 *
 * @runTestsInSeparateProcesses
 */
final class EntityTest extends TestCase
{
    protected EntityMock $entity;

    protected function setUp() : void
    {
        $this->entity = new EntityMock([
            'id' => '10',
        ]);
    }

    public function testConstruct() : void
    {
        self::assertInstanceOf(Entity::class, $this->entity);
    }

    public function testMagicSetAndGet() : void
    {
        self::assertSame(10, $this->entity->id);
        $this->entity->id = '20';
        self::assertSame(20, $this->entity->id);
        self::assertSame('', $this->entity->data);
        $this->entity->data = 25;
        self::assertSame('25', $this->entity->data);
    }

    public function testTryGetUndefinedProperty() : void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Property not defined: foo');
        $this->entity->foo; // @phpstan-ignore-line
    }

    public function testTrySetUndefinedProperty() : void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Property not defined: foo');
        $this->entity->foo = 'bar'; // @phpstan-ignore-line
    }

    public function testIssetAndUnset() : void
    {
        self::assertTrue(isset($this->entity->id));
        unset($this->entity->id);
        self::assertFalse(isset($this->entity->id));
    }
}
