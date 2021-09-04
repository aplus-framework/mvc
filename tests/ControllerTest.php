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
use Framework\MVC\Controller;
use Framework\MVC\Model;
use PHPUnit\Framework\TestCase;
use Tests\MVC\AppMock as App;

/**
 * Class ControllerTest.
 *
 * @runTestsInSeparateProcesses
 */
final class ControllerTest extends TestCase
{
    protected ControllerMock $controller;

    protected function setUp() : void
    {
        (new App(new Config(__DIR__ . '/configs', [], '.config.php')));
        $this->controller = new ControllerMock();
    }

    public function testConstruct() : void
    {
        self::assertInstanceOf(Controller::class, $this->controller);
    }

    public function testModelInstance() : void
    {
        self::assertInstanceOf(Model::class, $this->controller->model);
    }

    public function testRender() : void
    {
        self::assertSame(
            "<div>yyy</div>\n",
            $this->controller->render('foo', ['contents' => 'yyy'])
        );
    }

    public function testValidate() : void
    {
        $rules = [
            'foo' => 'minLength:5',
        ];
        self::assertArrayHasKey('foo', $this->controller->validate([], $rules));
        self::assertSame([
            'foo' => 'The foo field requires 5 or more characters in length.',
        ], $this->controller->validate(['foo' => '1234'], $rules));
        self::assertSame([], $this->controller->validate(['foo' => '12345'], $rules));
        self::assertSame([
            'foo' => 'The Foo field requires 5 or more characters in length.',
        ], $this->controller->validate([], $rules, ['foo' => 'Foo']));
        self::assertSame([
            'foo' => 'Field Bar is too short.',
        ], $this->controller->validate([], $rules, ['foo' => 'Bar'], [
            'foo' => [
                'minLength' => 'Field {field} is too short.',
            ],
        ]));
    }
}
