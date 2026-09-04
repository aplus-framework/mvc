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

use Error;
use Framework\Date\Date;
use Framework\MVC\Entity;
use PHPUnit\Framework\TestCase;

/**
 * Class EntityTest.
 */
final class EntityTest extends TestCase
{
    protected EntityMock $entity;
    protected int $time;

    protected function setUp() : void
    {
        $this->entity = new EntityMock([
            'id' => '2',
            'name' => 'John  Doe   ',
            'birthday' => '1990-12-24',
            'configs' => '{"color":"red", "foo":"bar"}',
        ]);
        $this->time = \time();
    }

    public function testPopulate() : void
    {
        self::assertIsInt($this->entity->id);
        self::assertIsString($this->entity->name);
        self::assertInstanceOf(Date::class, $this->entity->birthday);
        self::assertSame('1990-12-24', $this->entity->birthday->format('Y-m-d'));
        self::assertIsArray($this->entity->configs);
        self::assertSame(['color' => 'red', 'foo' => 'bar'], $this->entity->configs);
    }

    public function testInit() : void
    {
        self::assertSame($this->time, $this->entity->currentTime);
        $this->expectException(Error::class);
        $this->expectExceptionMessage(
            'Cannot modify private(set) property Tests\MVC\EntityMock::$currentTime from scope Tests\MVC\EntityTest'
        );
        $this->entity->currentTime = $this->time;
    }

    public function testToModel() : void
    {
        self::assertSame([
            'id' => 2,
            'name' => 'John Doe',
            'birthday' => '1990-12-24',
            'configs' => '{"color":"red","foo":"bar"}',
        ], $this->entity->toModel());
    }

    public function testToJson() : void
    {
        self::assertSame(
            '{"id":2,"name":"John Doe","birthday":"1990-12-24T00:00:00+00:00","configs":{"color":"red","foo":"bar"}}',
            \json_encode($this->entity)
        );
    }

    public function testOriginals() : void
    {
        $entity = new class([]) extends Entity
        {};
        self::assertSame([], $entity->toModel());
        self::assertSame([], $entity->toJson());
    }
}
