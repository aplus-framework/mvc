<?php
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Mvc;

use Aplus\Date\Date;
use Aplus\Mvc\Entity;
use Error;
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
            'createdAt' => '2026-09-04 20:52:00',
        ]);
        $this->time = \time();
    }

    public function testPopulate() : void
    {
        self::assertIsInt($this->entity->id); // @phpstan-ignore-line
        self::assertIsString($this->entity->name); // @phpstan-ignore-line
        self::assertInstanceOf(Date::class, $this->entity->birthday);
        self::assertSame('1990-12-24', $this->entity->birthday->format('Y-m-d'));
        self::assertIsArray($this->entity->configs); // @phpstan-ignore-line
        self::assertSame(['color' => 'red', 'foo' => 'bar'], $this->entity->configs);
        self::assertSame('2026-09-04 20:52:00', $this->entity->createdAt->format('Y-m-d H:i:s'));
    }

    public function testInit() : void
    {
        self::assertSame($this->time, $this->entity->currentTime);
        $this->expectException(Error::class);
        $this->expectExceptionMessage(
            'Cannot modify private(set) property Tests\Mvc\EntityMock::$currentTime from scope Tests\Mvc\EntityTest'
        );
        $this->entity->currentTime = $this->time;  // @phpstan-ignore-line
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
            '{"id":2,"name":"John Doe","birthday":"1990-12-24","configs":{"color":"red","foo":"bar"},"url":"https://foo.com/users/2","createdAt":"2026-09-04T20:52:00+00:00"}',
            \json_encode($this->entity, \JSON_UNESCAPED_SLASHES)
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
