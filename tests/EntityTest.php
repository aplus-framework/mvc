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

use Framework\Date\Date;
use Framework\MVC\Entity;
use PHPUnit\Framework\TestCase;

/**
 * Class EntityTest.
 *
 * @runTestsInSeparateProcesses
 */
final class EntityTest extends TestCase
{
    /**
     * @var EntityMock
     */
    protected $entity;

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

    public function testFromDateTime() : void
    {
        self::assertNull($this->entity->datetime);
        $datetime = new Date();
        $this->entity->datetime = $datetime;
        self::assertSame($datetime, $this->entity->datetime);
        $this->entity->datetime = '2018-12-24 10:00:00';
        self::assertSame('2018-12-24 10:00:00', $this->entity->datetime->format('Y-m-d H:i:s'));
        $this->entity->datetime = null;
        self::assertNull($this->entity->datetime);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value type must be string or Framework\Date\Date');
        $this->entity->datetime = [];
    }

    public function testFromJSON() : void
    {
        self::assertNull($this->entity->settings);
        $settings = new class() extends \stdClass {
            public $foo = 'foo';
        };
        $this->entity->settings = $settings;
        self::assertSame('foo', $this->entity->settings->foo);
        $settings = [
            'foo' => 'bar',
        ];
        $this->entity->settings = $settings;
        self::assertSame('bar', $this->entity->settings->foo);
        $this->entity->settings = '{"foo":"baz"}';
        self::assertSame('baz', $this->entity->settings->foo);
        $this->entity->settings = null;
        self::assertNull($this->entity->settings);
    }

    public function testToArray() : void
    {
        $datetime = new Date();
        $this->entity->datetime = $datetime;
        $settings = new class() extends \stdClass {
            public $foo = 'foo';
        };
        $this->entity->settings = $settings;
        self::assertSame([
            'id' => 10,
            'data' => '',
            'datetime' => $datetime->format(Date::ATOM),
            'createdAt' => null,
            'updatedAt' => null,
            'settings' => \json_encode($settings),
        ], $this->entity->toArray());
    }

    public function testUnknowTypeToScalar() : void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Property was not converted to scalar: createdAt');
        $this->entity->createdAt = new \DateTimeZone('UTC');
        $this->entity->toArray();
    }

    public function testTryGetUndefinedProperty() : void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Property not defined: foo');
        $this->entity->foo;
    }

    public function testTrySetUndefinedProperty() : void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Property not defined: foo');
        $this->entity->foo = 'bar';
    }

    public function testIssetAndUnset() : void
    {
        self::assertTrue(isset($this->entity->id));
        unset($this->entity->id);
        self::assertFalse(isset($this->entity->id));
    }

    public function testToString() : void
    {
        self::assertSame(\json_encode($this->entity->toArray()), (string) $this->entity);
    }

    public function testJsonSerialize() : void
    {
        self::assertSame(\json_encode($this->entity->toArray()), \json_encode($this->entity));
    }
}
