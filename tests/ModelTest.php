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
use Framework\MVC\Model;
use Tests\MVC\Models\FooBarModel;

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

    public function testConvertCase() : void
    {
        self::assertSame('fooBarBaz', $this->model->convertCase('fooBarBaz', 'camel'));
        self::assertSame('FooBarBaz', $this->model->convertCase('fooBarBaz', 'pascal'));
        self::assertSame('foo_bar_baz', $this->model->convertCase('fooBarBaz', 'snake'));
        self::assertSame('fooBarBaz', $this->model->convertCase('FooBarBaz', 'camel'));
        self::assertSame('FooBarBaz', $this->model->convertCase('FooBarBaz', 'pascal'));
        self::assertSame('foo_bar_baz', $this->model->convertCase('FooBarBaz', 'snake'));
        self::assertSame('fooBarBaz', $this->model->convertCase('foo_bar_baz', 'camel'));
        self::assertSame('FooBarBaz', $this->model->convertCase('foo_bar_baz', 'pascal'));
        self::assertSame('foo_bar_baz', $this->model->convertCase('foo_bar_baz', 'snake'));
        self::assertSame('curlFile', $this->model->convertCase('CurlFile', 'camel'));
        self::assertSame('CurlFile', $this->model->convertCase('CurlFile', 'pascal'));
        self::assertSame('curl_file', $this->model->convertCase('CurlFile', 'snake'));
        self::assertSame('curlfile', $this->model->convertCase('CURLFile', 'camel'));
        self::assertSame('Curlfile', $this->model->convertCase('CURLFile', 'pascal'));
        self::assertSame('curlfile', $this->model->convertCase('CURLFile', 'snake'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid case: foo');
        $this->model->convertCase('fooBar', 'foo');
    }

    public function testFindBy() : void
    {
        self::assertIsObject($this->model->findBy('id', 1));
        self::assertNull($this->model->findBy('id', 1000));
        self::assertIsObject($this->model->findBy('data', 'foo'));
    }

    public function testFindByWithCall() : void
    {
        self::assertIsObject($this->model->findById(1));
        self::assertNull($this->model->findById(1000));
        self::assertIsObject($this->model->findByData('foo')); // @phpstan-ignore-line
    }

    public function testReadBy() : void
    {
        self::assertIsObject($this->model->readBy('id', 1));
        self::assertNull($this->model->readBy('id', 1000));
        self::assertIsObject($this->model->readBy('data', 'foo'));
    }

    public function testReadByWithCall() : void
    {
        self::assertIsObject($this->model->readById(1));
        self::assertNull($this->model->readById(1000));
        self::assertIsObject($this->model->readByData('foo')); // @phpstan-ignore-line
    }

    public function testCallMethodNotAllowed() : void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method not allowed: ' . $this->model::class . '::getPrimaryKey'
        );
        $this->model->getPrimaryKey(); // @phpstan-ignore-line
    }

    public function testCallMethodNotFound() : void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method not found: ' . $this->model::class . '::foo'
        );
        $this->model->foo(); // @phpstan-ignore-line
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

    public function testRead() : void
    {
        self::assertIsObject($this->model->read(1));
        $this->model->returnType = 'array';
        self::assertIsArray($this->model->read(1));
        $this->model->returnType = EntityMock::class;
        self::assertInstanceOf(EntityMock::class, $this->model->read(1));
        self::assertNull($this->model->read(100));
    }

    public function testFindAll() : void
    {
        $data = $this->model->findAll();
        self::assertSame(1, $data[0]->id); // @phpstan-ignore-line
        self::assertSame(2, $data[1]->id); // @phpstan-ignore-line
        $data = $this->model->findAll(2, 1);
        self::assertSame(2, $data[0]->id); // @phpstan-ignore-line
        $this->model->returnType = 'array';
        $data = $this->model->findAll();
        self::assertSame(1, $data[0]['id']); // @phpstan-ignore-line
        self::assertSame(2, $data[1]['id']); // @phpstan-ignore-line
        self::assertEmpty($this->model->findAll(1, 100));
    }

    public function testList() : void
    {
        $data = $this->model->list();
        self::assertSame(1, $data[0]->id); // @phpstan-ignore-line
        self::assertSame(2, $data[1]->id); // @phpstan-ignore-line
        $data = $this->model->list(2, 1);
        self::assertSame(2, $data[0]->id); // @phpstan-ignore-line
        $this->model->returnType = 'array';
        $data = $this->model->list();
        self::assertSame(1, $data[0]['id']); // @phpstan-ignore-line
        self::assertSame(2, $data[1]['id']); // @phpstan-ignore-line
        self::assertEmpty($this->model->list(1, 100));
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

    public function testCheckMysqliException() : void
    {
        $this->model->checkMysqliException(
            new \mysqli_sql_exception("Duplicate entry '3' for key 'PRIMARY'")
        );
        self::assertSame(
            'The id field has already been registered.',
            $this->model->getErrors()['id']
        );
        $this->model->checkMysqliException(
            new \mysqli_sql_exception("Duplicate entry '3' for key 'id'")
        );
        self::assertSame(
            'The id field has already been registered.',
            $this->model->getErrors()['id']
        );
        $this->model->checkMysqliException(
            new \mysqli_sql_exception("Duplicate entry 'foo@bar' for key 'email'")
        );
        self::assertSame(
            'The email field has already been registered.',
            $this->model->getErrors()['email']
        );
        $this->expectException(\mysqli_sql_exception::class);
        $this->expectExceptionMessage('Foo bar');
        $this->model->checkMysqliException(
            new \mysqli_sql_exception('Foo bar')
        );
    }

    public function testCreateBy() : void
    {
        self::assertSame(
            'Value',
            $this->model->createBy('data', new EntityMock(['data' => 'Value']))
        );
        self::assertSame(
            'Other',
            $this->model->createBy('data', ['data' => 'Other'])
        );
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Value of column data is not set');
        $this->model->createBy('data', ['foo' => 'Other']);
    }

    public function testCreateByWithCheckMysqliException() : void
    {
        $this->model->allowedFields = ['id', 'data'];
        $this->model->protectPrimaryKey = false;
        $id = $this->model->createBy('id', ['id' => 3, 'data' => 'bar']);
        self::assertSame(3, $id);
        self::assertFalse($this->model->createBy('id', ['id' => 3, 'data' => 'bar']));
        self::assertArrayHasKey('id', $this->model->getErrors());
    }

    public function testCreateByWithCall() : void
    {
        self::assertSame(
            'Value',
            $this->model->createByData(new EntityMock(['data' => 'Value'])) // @phpstan-ignore-line
        );
        self::assertSame(
            'Other',
            $this->model->createByData(['data' => 'Other']) // @phpstan-ignore-line
        );
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Value of column data is not set');
        $this->model->createByData(['foo' => 'Other']); // @phpstan-ignore-line
    }

    public function testCreateByValidationFail() : void
    {
        $this->model->validationRules = ['data' => 'required'];
        self::assertFalse($this->model->createBy('data', ['data' => '']));
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
        $this->expectExceptionMessage("Unknown column 'not-exists' in 'INSERT INTO'");
        $this->model->create(['not-exists' => 'Value']);
    }

    public function testCreateWithCheckMysqliException() : void
    {
        $this->model->allowedFields = ['id', 'data'];
        $this->model->protectPrimaryKey = false;
        $id = $this->model->create(['id' => 3, 'data' => 'bar']);
        self::assertSame(3, $id);
        self::assertFalse($this->model->create(['id' => 3, 'data' => 'bar']));
        self::assertArrayHasKey('id', $this->model->getErrors());
    }

    public function testCreateExceptionDuplicateEntryWithReportOff() : void
    {
        App::removeService('database');
        $config = App::config()->get('database');
        $config['config']['report'] = \MYSQLI_REPORT_OFF;
        App::config()->set('database', $config);
        $this->model->allowedFields = ['id', 'data'];
        $this->model->protectPrimaryKey = false;
        $id = $this->model->create(['id' => 3, 'data' => 'foo']);
        self::assertSame(3, $id);
        $id = $this->model->create(['id' => 3, 'data' => 'foo']);
        self::assertFalse($id);
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

    public function testUpdateWithCheckMysqliException() : void
    {
        $this->model->allowedFields = ['id', 'data'];
        $this->model->protectPrimaryKey = false;
        $affectedRows = $this->model->update(1, ['id' => 1, 'data' => 'bar']);
        self::assertSame(1, $affectedRows);
        self::assertFalse($this->model->update(1, ['id' => 2, 'data' => 'bar']));
        self::assertArrayHasKey('id', $this->model->getErrors());
    }

    public function testUpdateBy() : void
    {
        $affected_rows = $this->model->updateBy('id', 1, new EntityMock(['data' => 'x']));
        self::assertSame(1, $affected_rows);
        $affected_rows = $this->model->updateBy('id', 1, ['data' => 'x']);
        self::assertSame(0, $affected_rows); // same data
        \sleep(1); // change updatedAt value
        $affected_rows = $this->model->updateBy('id', 1, ['data' => 'x']);
        self::assertSame(1, $affected_rows);
        $affected_rows = $this->model->updateBy('id', 25, ['data' => 'foo']);
        self::assertSame(0, $affected_rows);
    }

    public function testUpdateByWithCheckMysqliException() : void
    {
        $this->model->allowedFields = ['id', 'data'];
        $this->model->protectPrimaryKey = false;
        $affectedRows = $this->model->updateBy('id', 1, ['id' => 1, 'data' => 'bar']);
        self::assertSame(1, $affectedRows);
        self::assertFalse($this->model->updateBy('id', 1, ['id' => 2, 'data' => 'bar']));
        self::assertArrayHasKey('id', $this->model->getErrors());
    }

    public function testUpdateByWithCall() : void
    {
        $affected_rows = $this->model->updateById(1, new EntityMock(['data' => 'x']));
        self::assertSame(1, $affected_rows);
        $affected_rows = $this->model->updateById(1, ['data' => 'x']);
        self::assertSame(0, $affected_rows); // same data
        \sleep(1); // change updatedAt value
        $affected_rows = $this->model->updateById(1, ['data' => 'x']);
        self::assertSame(1, $affected_rows);
        $affected_rows = $this->model->updateById(25, ['data' => 'foo']);
        self::assertSame(0, $affected_rows);
    }

    public function testUpdateExceptionUnknownColumn() : void
    {
        $this->model->allowedFields[] = 'not-exists';
        $this->expectException(\mysqli_sql_exception::class);
        $this->expectExceptionMessage("Unknown column 'not-exists' in 'SET'");
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
        $this->expectExceptionMessage("Unknown column 'not-exists' in 'INSERT INTO'");
        $this->model->replace(1, ['not-exists' => 'Value']);
    }

    public function testReplaceBy() : void
    {
        $affected_rows = $this->model->replaceBy('id', 1, new EntityMock(['data' => 'xii']));
        self::assertSame(2, $affected_rows); // Deleted and inserted
        $affected_rows = $this->model->replaceBy('id', 1, ['data' => 'bar']);
        self::assertSame(2, $affected_rows); // Deleted and inserted
        $affected_rows = $this->model->replaceBy('id', 25, ['data' => 'baz']);
        self::assertSame(1, $affected_rows); // Inserted
    }

    public function testReplaceByWithCall() : void
    {
        $affected_rows = $this->model->replaceById(1, new EntityMock(['data' => 'xii']));
        self::assertSame(2, $affected_rows); // Deleted and inserted
        $affected_rows = $this->model->replaceById(1, ['data' => 'bar']);
        self::assertSame(2, $affected_rows); // Deleted and inserted
        $affected_rows = $this->model->replaceById(25, ['data' => 'baz']);
        self::assertSame(1, $affected_rows); // Inserted
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

    public function testDeleteBy() : void
    {
        self::assertSame(1, $this->model->deleteBy('id', 1));
        self::assertSame(0, $this->model->deleteBy('id', 1000));
        self::assertSame(0, $this->model->deleteBy('data', 'foo'));
        self::assertSame(1, $this->model->deleteBy('data', 'bar'));
    }

    public function testDeleteByWithCall() : void
    {
        self::assertSame(1, $this->model->deleteById(1));
        self::assertSame(0, $this->model->deleteById(1000));
        self::assertSame(0, $this->model->deleteByData('foo')); // @phpstan-ignore-line
        self::assertSame(1, $this->model->deleteByData('bar')); // @phpstan-ignore-line
    }

    public function testCount() : void
    {
        self::assertSame(2, $this->model->count());
        self::assertSame(1, $this->model->count([
            ['id', '=', 1],
        ]));
        self::assertSame(2, $this->model->count([
            ['id', 'is not null'],
            ['id', '<', 3],
        ]));
    }

    public function testPaginatedItems() : void
    {
        $date = \date('Y-m-d H:i:s');
        $this->model->returnType = 'array';
        self::assertSame([
            [
                'id' => 1,
                'data' => 'foo',
                'createdAt' => $date,
                'updatedAt' => $date,
            ],
        ], $this->model->paginate(-1, 1));
        self::assertSame([
            [
                'id' => 1,
                'data' => 'foo',
                'createdAt' => $date,
                'updatedAt' => $date,
            ],
        ], $this->model->paginate(1, 1));
        self::assertSame([
            [
                'id' => 2,
                'data' => 'bar',
                'createdAt' => $date,
                'updatedAt' => $date,
            ],
        ], $this->model->paginate(2, 1));
        self::assertSame([], $this->model->paginate(3, 1));
        self::assertSame([
            [
                'id' => 2,
                'data' => 'bar',
                'createdAt' => $date,
                'updatedAt' => $date,
            ],
        ], $this->model->paginate(1, 10, [
            ['id', '=', 2],
            ['id', 'between', 0, 3],
        ]));
        self::assertSame([
            [
                'id' => 1,
                'data' => 'foo',
                'createdAt' => $date,
                'updatedAt' => $date,
            ],
            [
                'id' => 2,
                'data' => 'bar',
                'createdAt' => $date,
                'updatedAt' => $date,
            ],
        ], $this->model->paginate(1, 10, [
            ['id', '>', 0],
            ['data', 'is not null'],
        ], 'id'));
        self::assertSame([
            [
                'id' => 2,
                'data' => 'bar',
                'createdAt' => $date,
                'updatedAt' => $date,
            ],
            [
                'id' => 1,
                'data' => 'foo',
                'createdAt' => $date,
                'updatedAt' => $date,
            ],
        ], $this->model->paginate(1, 10, [
            ['id', '>', 0],
            ['data', 'is not null'],
        ], 'id', 'desc'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ORDER BY direction: Foo');
        $this->model->paginate(1, 10, [], 'id', 'Foo');
    }

    public function testPaginatedUrl() : void
    {
        $this->model->paginate(5);
        self::assertSame(
            'http://localhost:8080/contact?page=5',
            $this->model->getPager()->getCurrentPageUrl()
        );
        $this->model->paginate(10, 25);
        self::assertSame(
            'http://localhost:8080/contact?page=10',
            $this->model->getPager()->getCurrentPageUrl()
        );
    }

    public function testPagerUrl() : void
    {
        $this->model->paginate(5);
        self::assertSame(
            'http://localhost:8080/contact?page=5',
            $this->model->getPager()->getCurrentPageUrl()
        );
        $this->model->pagerUrl = 'http://domain.tld/posts';
        $this->model->paginate(5);
        self::assertSame(
            'http://domain.tld/posts?page=5',
            $this->model->getPager()->getCurrentPageUrl()
        );
    }

    public function testPagerView() : void
    {
        $this->model->paginate(1);
        self::assertSame(
            'pagination',
            $this->model->getPager()->getDefaultView()
        );
        $this->model->pagerView = 'bootstrap';
        $this->model->paginate(1);
        self::assertSame(
            'bootstrap',
            $this->model->getPager()->getDefaultView()
        );
    }

    public function testPagerQuery() : void
    {
        $this->model->pagerQuery = 'p';
        $this->model->paginate(5);
        self::assertSame(
            'http://localhost:8080/contact?p=5',
            $this->model->getPager()->getCurrentPageUrl()
        );
    }

    public function testPagerAllowedQueries() : void
    {
        $_SERVER['REQUEST_URI'] = '/products?page=5&foo=bar&order=asc&bla=bla&per_page=10';
        $this->model->paginate(5);
        self::assertSame(
            'http://localhost:8080/products?page=5&foo=bar&order=asc&bla=bla&per_page=10',
            $this->model->getPager()->getCurrentPageUrl()
        );
        $this->model->pagerAllowedQueries = ['order', 'per_page'];
        $this->model->paginate(5);
        self::assertSame(
            'http://localhost:8080/products?page=5&order=asc&per_page=10',
            $this->model->getPager()->getCurrentPageUrl()
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
        self::assertSame(
            [\PHP_INT_MAX, null],
            $this->model->makePageLimitAndOffset(0, \PHP_INT_MAX)
        );
        self::assertSame(
            [\PHP_INT_MAX, null],
            $this->model->makePageLimitAndOffset(0, \PHP_INT_MIN)
        );
        self::assertSame(
            [\PHP_INT_MAX, null],
            $this->model->makePageLimitAndOffset(1, \PHP_INT_MAX)
        );
        self::assertSame(
            [\PHP_INT_MAX, null],
            $this->model->makePageLimitAndOffset(1, \PHP_INT_MIN)
        );
        self::assertSame(
            [\PHP_INT_MAX, \PHP_INT_MAX],
            $this->model->makePageLimitAndOffset(2, \PHP_INT_MAX)
        );
        self::assertSame(
            [\PHP_INT_MAX, \PHP_INT_MAX],
            $this->model->makePageLimitAndOffset(2, \PHP_INT_MIN)
        );
        self::assertSame(
            [\PHP_INT_MAX, \PHP_INT_MAX],
            $this->model->makePageLimitAndOffset(3, \PHP_INT_MAX)
        );
        self::assertSame(
            [\PHP_INT_MAX, \PHP_INT_MAX],
            $this->model->makePageLimitAndOffset(3, \PHP_INT_MIN)
        );
        self::assertSame(
            [\PHP_INT_MAX, \PHP_INT_MAX],
            $this->model->makePageLimitAndOffset(\PHP_INT_MAX, \PHP_INT_MAX)
        );
        self::assertSame(
            [\PHP_INT_MAX, \PHP_INT_MAX],
            $this->model->makePageLimitAndOffset(\PHP_INT_MIN, \PHP_INT_MIN)
        );
    }

    public function testValidation() : void
    {
        $message = 'Mui curto';
        $this->model->validationRules = ['data' => 'minLength:200'];
        $this->model->validationMessages = ['data' => ['minLength' => $message]];
        $row = $this->model->create(['data' => 'Value']);
        self::assertFalse($row);
        self::assertArrayHasKey('data', $this->model->getErrors());
        self::assertSame($message, $this->model->getErrors()['data']);
        $row = $this->model->update(1, ['data' => 'Value']);
        self::assertFalse($row);
        self::assertArrayHasKey('data', $this->model->getErrors());
    }

    public function testValidationWhenAppIsDebugging() : void
    {
        AppMock::setConfigProperty(null);
        new AppMock([], true);
        $model = new ModelMock();
        self::assertNull(AppMock::debugger()->getCollection('Validation'));
        $model->getValidation();
        self::assertNotNull(AppMock::debugger()->getCollection('Validation'));
    }

    public function testCreateWithValidationRulesNotSet() : void
    {
        unset($this->model->validationRules);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Validation rules are not set');
        $this->model->create(['data' => 'foo']);
    }

    public function testGetTable() : void
    {
        $model = new FooBarModel();
        self::assertSame('FooBar', $model->getTable());
        self::assertSame('FooBar', $model->getTable());
    }

    public function testGet() : void
    {
        $model = Model::get(ModelMock::class);
        self::assertSame($model, Model::get(ModelMock::class));
    }
}
