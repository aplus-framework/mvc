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

use DateTime;
use Framework\Date\Date;
use Framework\HTTP\URL;
use PHPUnit\Framework\TestCase;
use stdClass;

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
            'array' => [],
            'bool' => true,
            'float' => 1.5,
            'int' => 3,
            'string' => 'foo',
            'stdClass' => new stdClass(),
            'date' => new Date('2021-09-15 15:47:08'),
            'url' => new URL('https://foo.com'),
            'mixed' => new DateTime(),
        ]);
    }

    public function testTypes() : void
    {
        self::assertSame('array', \get_debug_type($this->entity->array));
        self::assertSame('bool', \get_debug_type($this->entity->bool));
        self::assertSame('float', \get_debug_type($this->entity->float));
        self::assertSame('int', \get_debug_type($this->entity->int));
        self::assertSame('string', \get_debug_type($this->entity->string));
        self::assertSame(stdClass::class, \get_debug_type($this->entity->stdClass));
        self::assertSame(Date::class, \get_debug_type($this->entity->date));
        self::assertSame(URL::class, \get_debug_type($this->entity->url));
    }

    public function testPopulateWithSetter() : void
    {
        $entity = new EntityMock([
            'id' => 1,
        ]);
        self::assertSame(1001, $entity->id); // @phpstan-ignore-line
    }

    public function testPopulateWithPropertyNotDefined() : void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Property not defined: foo');
        new EntityMock([
            'foo' => 'Foo',
        ]);
    }

    public function testTypeHintArrayString() : void
    {
        $entity = new EntityMock([
            'array' => '{"foo":1}',
        ]);
        self::assertSame(['foo' => 1], $entity->array);
        $entity = new EntityMock([
            'array' => '{}',
        ]);
        self::assertIsArray($entity->array);
        $entity = new EntityMock([
            'array' => '[]',
        ]);
        self::assertIsArray($entity->array);
    }

    public function testTypeHintStdClassString() : void
    {
        $entity = new EntityMock([
            'stdClass' => '{"foo":1}',
        ]);
        $class = new stdClass();
        $class->foo = 1;
        self::assertEquals($class, $entity->stdClass);
        $entity = new EntityMock([
            'stdClass' => '{}',
        ]);
        self::assertInstanceOf(stdClass::class, $entity->stdClass);
        $entity = new EntityMock([
            'stdClass' => '[]',
        ]);
        self::assertInstanceOf(stdClass::class, $entity->stdClass);
    }

    public function testNewEntityWithUndefinedProperty() : void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Property not defined: foo');
        new EntityMock(['foo' => 0]);
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

    public function testMagicSetAndGet() : void
    {
        $this->entity->id = 1; // @phpstan-ignore-line
        self::assertSame(1001, $this->entity->id); // @phpstan-ignore-line
        self::assertSame(1001, $this->entity->getId());
        $this->entity->setId(3);
        self::assertSame(1003, $this->entity->id); // @phpstan-ignore-line
        self::assertSame(1003, $this->entity->getId());
    }

    public function testIssetAndUnset() : void
    {
        self::assertTrue(isset($this->entity->int));
        unset($this->entity->int);
        self::assertFalse(isset($this->entity->int));
    }

    public function testJsonEncode() : void
    {
        self::assertSame(
            '{}',
            \json_encode($this->entity)
        );
        $this->entity->setJsonVars(['array', 'int', 'url', 'stdClass']);
        self::assertSame(
            '{"array":[],"int":3,"url":"https:\/\/foo.com\/","stdClass":{}}',
            \json_encode($this->entity)
        );
    }

    public function testToModel() : void
    {
        $this->entity->mixed = null;
        self::assertSame([
            'array' => '[]',
            'bool' => true,
            'float' => 1.5,
            'int' => 3,
            'string' => 'foo',
            'stdClass' => '[]',
            'date' => '2021-09-15 15:47:08',
            'url' => 'https://foo.com/',
            'mixed' => null,
            'id' => null,
            'data' => null,
            'createdAt' => null,
            'updatedAt' => null,
        ], $this->entity->toModel());
    }

    public function testJsonVars() : void
    {
        $vars = ['id', 'data'];
        self::assertEmpty($this->entity->getJsonVars());
        $this->entity->setJsonVars($vars);
        self::assertSame($vars, $this->entity->getJsonVars());
    }
}
