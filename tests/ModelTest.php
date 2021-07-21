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
use Framework\Validation\Validation;

/**
 * Class ModelTest.
 *
 * @runTestsInSeparateProcesses
 */
final class ModelTest extends ModelTestCase
{
    protected ?ModelMock $model;

    protected function setUp() : void
    {
        parent::setUp();
        $this->model = new ModelMock();
    }

    public function testFind() : void
    {
        self::assertIsObject($this->model->find(1));
        $this->model->returnType = 'array';
        self::assertIsArray($this->model->find(1));
        $this->model->returnType = EntityMock::class;
        self::assertInstanceOf(EntityMock::class, $this->model->find(1));
        self::assertNull($this->model->find(100));
    }

    public function testAllowedFieldsNotDefined() : void
    {
        $this->model->allowedFields = [];
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Allowed fields not defined for database writes');
        $this->model->create(['data' => 'Value']);
    }

    public function testProtectedPrimaryKeyCanNotBeSet() : void
    {
        $this->model->allowedFields = ['id', 'data'];
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Protected Primary Key field can not be SET');
        $this->model->create(['id' => 1, 'data' => 'x']);
    }

    public function testEmptyPrimaryKey() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Primary Key can not be empty');
        $this->model->update(0, ['data' => 'x']);
    }

    public function testCreate() : void
    {
        $insert_id = $this->model->create(new EntityMock(['data' => 'Value']));
        self::assertSame(3, $insert_id);
        $insert_id = $this->model->create(['data' => 'Value']);
        self::assertSame(4, $insert_id);
        $insert_id = $this->model->create(['data' => 'Value']);
        self::assertSame(5, $insert_id);
    }

    public function testCreateExceptionDefaultValue() : void
    {
        $this->expectException(\mysqli_sql_exception::class);
        $this->expectExceptionMessage("Field 'data' doesn't have a default value");
        $this->model->create([]);
    }

    public function testCreateExceptionUnknownColumn() : void
    {
        $this->model->allowedFields[] = 'not-exists';
        $this->expectException(\mysqli_sql_exception::class);
        $this->expectExceptionMessage("Unknown column 'not-exists' in 'field list'");
        $this->model->create(['not-exists' => 'Value']);
    }

    public function testUpdate() : void
    {
        $affected_rows = $this->model->update(1, new EntityMock(['data' => 'x']));
        self::assertSame(1, $affected_rows);
        $affected_rows = $this->model->update(1, ['data' => 'x']);
        self::assertSame(0, $affected_rows); // same data
        \sleep(1); // change updatedAt value
        $affected_rows = $this->model->update(1, ['data' => 'x']);
        self::assertSame(1, $affected_rows);
        $affected_rows = $this->model->update(25, ['data' => 'foo']);
        self::assertSame(0, $affected_rows);
    }

    public function testUpdateExceptionUnknownColumn() : void
    {
        $this->model->allowedFields[] = 'not-exists';
        $this->expectException(\mysqli_sql_exception::class);
        $this->expectExceptionMessage("Unknown column 'not-exists' in 'field list'");
        $this->model->update(1, ['not-exists' => 'Value']);
    }

    public function testReplace() : void
    {
        $affected_rows = $this->model->replace(1, new EntityMock(['data' => 'xii']));
        self::assertSame(2, $affected_rows); // Deleted and inserted
        $affected_rows = $this->model->replace(1, ['data' => 'bar']);
        self::assertSame(2, $affected_rows); // Deleted and inserted
        $affected_rows = $this->model->replace(25, ['data' => 'baz']);
        self::assertSame(1, $affected_rows); // Inserted
    }

    public function testReplaceExceptionUnknownColumn() : void
    {
        $this->model->allowedFields[] = 'not-exists';
        $this->expectException(\mysqli_sql_exception::class);
        $this->expectExceptionMessage("Unknown column 'not-exists' in 'field list'");
        $this->model->replace(1, ['not-exists' => 'Value']);
    }

    public function testSave() : void
    {
        $this->model->allowedFields = ['data'];
        $affected_rows = $this->model->save(['id' => 1, 'data' => 'x']);
        self::assertSame(1, $affected_rows);
        $insert_id = $this->model->save(['data' => 'x']);
        self::assertSame(3, $insert_id);
        $affected_rows = $this->model->save(new EntityMock(['id' => 3, 'data' => 'x']));
        self::assertSame(0, $affected_rows); // same data exists
        $affected_rows = $this->model->save(new EntityMock(['id' => 25, 'data' => 'foo']));
        self::assertSame(0, $affected_rows);
    }

    public function testDelete() : void
    {
        self::assertSame(1, $this->model->delete(1));
        self::assertSame(0, $this->model->delete(1));
        self::assertSame(0, $this->model->delete(25));
    }

    public function testCount() : void
    {
        self::assertSame(2, $this->model->count());
    }

    public function testPaginatedItems() : void
    {
        $this->model->returnType = 'array';
        self::assertSame([
            [
                'id' => 1,
                'data' => 'foo',
                'createdAt' => \date('Y-m-d H:i:s'),
                'updatedAt' => \date('Y-m-d H:i:s'),
            ],
        ], $this->model->paginate(-1, 1));
        self::assertSame([
            [
                'id' => 1,
                'data' => 'foo',
                'createdAt' => \date('Y-m-d H:i:s'),
                'updatedAt' => \date('Y-m-d H:i:s'),
            ],
        ], $this->model->paginate(1, 1));
        self::assertSame([
            [
                'id' => 2,
                'data' => 'bar',
                'createdAt' => \date('Y-m-d H:i:s'),
                'updatedAt' => \date('Y-m-d H:i:s'),
            ],
        ], $this->model->paginate(2, 1));
        self::assertSame([], $this->model->paginate(3, 1));
    }

    public function testPaginatedUrl() : void
    {
        $this->model->paginate(5);
        self::assertSame(
            'http://localhost:8080/contact?page=5',
            $this->model->getPager()->getCurrentPageURL()
        );
        $this->model->paginate(10, 25);
        self::assertSame(
            'http://localhost:8080/contact?page=10',
            $this->model->getPager()->getCurrentPageURL()
        );
    }

    public function testMakePageLimitAndOffset() : void
    {
        self::assertSame([10, null], $this->model->makePageLimitAndOffset(0));
        self::assertSame([10, null], $this->model->makePageLimitAndOffset(1));
        self::assertSame([10, 10], $this->model->makePageLimitAndOffset(2));
        self::assertSame([20, null], $this->model->makePageLimitAndOffset(1, 20));
        self::assertSame([20, 20], $this->model->makePageLimitAndOffset(2, 20));
        // @phpstan-ignore-next-line
        self::assertSame([20, 40], $this->model->makePageLimitAndOffset('-3', '-20'));
    }

    public function testValidation() : void
    {
        $this->model->validationRules = ['data' => 'minLength:200'];
        $row = $this->model->create(['data' => 'Value']);
        self::assertFalse($row);
        self::assertArrayHasKey('data', $this->model->getErrors());
        $row = $this->model->update(1, ['data' => 'Value']);
        self::assertFalse($row);
        self::assertArrayHasKey('data', $this->model->getErrors());
    }

    public function testValidationUnset() : void
    {
        $id = 'Model:' . \spl_object_hash($this->model);
        self::assertNull(App::getService('validation', $id));
        $this->model->getValidation();
        self::assertInstanceOf(Validation::class, App::getService('validation', $id));
        $this->model = null;
        self::assertNull(App::getService('validation', $id));
    }
}
