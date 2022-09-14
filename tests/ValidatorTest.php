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

use Framework\Config\Config;
use Framework\Database\Definition\Table\TableDefinition;
use PHPUnit\Framework\TestCase;
use Tests\MVC\AppMock as App;

/**
 * @runTestsInSeparateProcesses
 */
final class ValidatorTest extends TestCase
{
    protected function setUp() : void
    {
        (new App(new Config(__DIR__ . '/configs', [], '.config.php')));
        App::database()->dropTable()->table('Users')->ifExists()->run();
        App::database()->createTable()
            ->table('Users')
            ->definition(static function (TableDefinition $definition) : void {
                $definition->column('id')->int()->primaryKey();
                $definition->column('username')->varchar(255);
            })->run();
        App::database()->insert()->into('Users')->values(1, 'foo')->run();
        App::database()->insert()->into('Users')->values(2, 'bar')->run();
    }

    public function testUniqueNoDataValue() : void
    {
        $validation = App::validation();
        $validation->setRule('username', 'unique:Users');
        self::assertFalse($validation->validate([]));
        self::assertArrayHasKey('username', $validation->getErrors());
    }

    public function testNotUniqueNoDataValue() : void
    {
        $validation = App::validation();
        $validation->setRule('username', 'notUnique:Users');
        self::assertTrue($validation->validate([]));
        self::assertArrayNotHasKey('username', $validation->getErrors());
    }

    public function testUnique() : void
    {
        $validation = App::validation();
        $validation->setRule('username', 'unique:Users');
        $data = [
            'username' => 'foo',
        ];
        self::assertFalse($validation->validate($data));
        self::assertArrayHasKey('username', $validation->getErrors());
    }

    public function testNotUnique() : void
    {
        $validation = App::validation();
        $validation->setRule('username', 'notUnique:Users');
        $data = [
            'username' => 'foo',
        ];
        self::assertTrue($validation->validate($data));
        self::assertArrayNotHasKey('username', $validation->getErrors());
    }

    public function testUniqueWithTableColumn() : void
    {
        $validation = App::validation();
        $validation->setRule('username', 'unique:Users.username');
        $data = [
            'username' => 'foo',
        ];
        self::assertFalse($validation->validate($data));
        self::assertArrayHasKey('username', $validation->getErrors());
    }

    public function testNotUniqueWithTableColumn() : void
    {
        $validation = App::validation();
        $validation->setRule('username', 'notUnique:Users.username');
        $data = [
            'username' => 'foo',
        ];
        self::assertTrue($validation->validate($data));
        self::assertArrayNotHasKey('username', $validation->getErrors());
    }

    public function testUniqueIgnoring() : void
    {
        $validation = App::validation();
        $validation->setRule('username', 'unique:Users,id,1');
        $data = [
            'username' => 'foo',
        ];
        self::assertTrue($validation->validate($data));
    }

    public function testNotUniqueIgnoring() : void
    {
        $validation = App::validation();
        $validation->setRule('username', 'notUnique:Users,id,1');
        $data = [
            'username' => 'foo',
        ];
        self::assertFalse($validation->validate($data));
        self::assertArrayHasKey('username', $validation->getErrors());
    }

    public function testUniqueWithoutConnection() : void
    {
        $validation = App::validation();
        $validation->setRule('username', 'unique:Users,id,1,');
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The connection parameter must be set to be able to connect the database'
        );
        $data = [
            'username' => 'foo',
        ];
        $validation->validate($data);
    }

    public function testNotUniqueWithoutConnection() : void
    {
        $validation = App::validation();
        $validation->setRule('username', 'notUnique:Users,id,1,');
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The connection parameter must be set to be able to connect the database'
        );
        $data = [
            'username' => 'foo',
        ];
        $validation->validate($data);
    }
}
