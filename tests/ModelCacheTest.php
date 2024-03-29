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

use Framework\MVC\App;

/**
 * Class ModelCacheTest.
 *
 * @runTestsInSeparateProcesses
 */
final class ModelCacheTest extends ModelTestCase
{
    protected ?ModelCacheMock $model;

    protected function setUp() : void
    {
        parent::setUp();
        $this->model = new ModelCacheMock();
    }

    protected function tearDown() : void
    {
        App::cache()->flush();
    }

    public function testRead() : void
    {
        self::assertIsObject($this->model->read(1));
        self::assertIsObject($this->model->read(1));
    }

    public function testReadNotFound() : void
    {
        self::assertNull($this->model->read(3));
        self::assertNull($this->model->read(3));
    }

    public function testCreateBy() : void
    {
        self::assertNull($this->model->readBy('data', 'bazz'));
        self::assertSame('bazz', $this->model->createBy('data', [
            'data' => 'bazz',
        ]));
        self::assertIsObject($this->model->readBy('data', 'bazz'));
    }

    public function testCreate() : void
    {
        self::assertNull($this->model->read(3));
        self::assertSame(3, $this->model->create([
            'data' => 'foo',
        ]));
        self::assertIsObject($this->model->read(3));
    }

    public function testCreateValidationFail() : void
    {
        self::assertNull($this->model->read(3));
        self::assertFalse($this->model->create([
            'data' => 'x',
        ]));
        self::assertNull($this->model->read(3));
    }

    public function testUpdate() : void
    {
        self::assertSame('foo', $this->model->read(1)->data); // @phpstan-ignore-line
        self::assertSame(1, $this->model->update(1, [
            'data' => 'bar',
        ]));
        self::assertSame('bar', $this->model->read(1)->data); // @phpstan-ignore-line
    }

    public function testUpdateValidationFail() : void
    {
        self::assertSame('foo', $this->model->read(1)->data); // @phpstan-ignore-line
        self::assertFalse($this->model->update(1, [
            'data' => 'x',
        ]));
        self::assertSame('foo', $this->model->read(1)->data); // @phpstan-ignore-line
    }

    public function testReplace() : void
    {
        self::assertSame('foo', $this->model->read(1)->data); // @phpstan-ignore-line
        self::assertSame(2, $this->model->replace(1, [ // Deleted and inserted
            'data' => 'baz',
        ]));
        self::assertSame('baz', $this->model->read(1)->data); // @phpstan-ignore-line
    }

    public function testReplaceValidationFail() : void
    {
        self::assertSame('foo', $this->model->read(1)->data); // @phpstan-ignore-line
        self::assertFalse($this->model->replace(1, [
            'data' => 'x',
        ]));
        self::assertSame('foo', $this->model->read(1)->data); // @phpstan-ignore-line
    }

    public function testDelete() : void
    {
        self::assertIsObject($this->model->read(1));
        self::assertSame(1, $this->model->delete(1));
        self::assertNull($this->model->read(1));
    }

    public function testUpdateCacheRow() : void
    {
        self::assertIsObject($this->model->read(1));
        $this->model->delete(1);
        $this->model->updateCachedRow('id', 1);
        self::assertNull($this->model->read(1));
    }
}
