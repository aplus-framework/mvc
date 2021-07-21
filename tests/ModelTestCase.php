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
use Framework\MVC\App;
use PHPUnit\Framework\TestCase;

abstract class ModelTestCase extends TestCase
{
    protected function setUp() : void
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost:8080';
        $_SERVER['REQUEST_URI'] = '/contact';
        (new App(new Config(__DIR__ . '/configs', suffix: '.config.php')));
        App::database()->dropTable()->ifExists()->table('ModelMock')->run();
        App::database()->createTable()->table('ModelMock')
            ->definition(static function (TableDefinition $definition) : void {
                $definition->column('id')->int(11)->notNull()->autoIncrement()->primaryKey();
                $definition->column('data')->varchar(255);
                $definition->column('createdAt')->datetime();
                $definition->column('updatedAt')->datetime();
            })->run();
        App::database()->insert()->into('ModelMock')->set([
            'data' => 'foo',
            'createdAt' => \date('Y-m-d H:i:s'),
            'updatedAt' => \date('Y-m-d H:i:s'),
        ])->run();
        App::database()->insert()->into('ModelMock')->set([
            'data' => 'bar',
            'createdAt' => \date('Y-m-d H:i:s'),
            'updatedAt' => \date('Y-m-d H:i:s'),
        ])->run();
    }
}
