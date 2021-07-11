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

use Framework\HTTP\Response;
use Framework\MVC\ResourceController;
use PHPUnit\Framework\TestCase;

/**
 * Class ResourceControllerTest.
 *
 * @runTestsInSeparateProcesses
 */
final class ResourceControllerTest extends TestCase
{
    protected ResourceControllerMock $resourceController;

    protected function setUp() : void
    {
        $this->resourceController = new ResourceControllerMock();
    }

    public function testConstruct() : void
    {
        self::assertInstanceOf(ResourceController::class, $this->resourceController);
    }

    public function testArgumentsCast() : void
    {
        self::assertIsInt($this->resourceController->show(25));
        self::assertIsInt($this->resourceController->show('25'));
        self::assertIsInt($this->resourceController->replace(25));
        self::assertIsString($this->resourceController->replace('25'));
    }

    public function testResourceMethods() : void
    {
        self::assertInstanceOf(Response::class, $this->resourceController->respondOK([]));
        self::assertInstanceOf(Response::class, $this->resourceController->respondOK());
        self::assertInstanceOf(Response::class, $this->resourceController->respondNotModified());
        self::assertInstanceOf(Response::class, $this->resourceController->respondNotFound());
        self::assertInstanceOf(Response::class, $this->resourceController->respondNoContent());
        self::assertInstanceOf(Response::class, $this->resourceController->respondForbidden());
        self::assertInstanceOf(Response::class, $this->resourceController->respondCreated());
        self::assertInstanceOf(Response::class, $this->resourceController->respondBadRequest());
        self::assertInstanceOf(Response::class, $this->resourceController->respondUnauthorized());
        self::assertInstanceOf(Response::class, $this->resourceController->respondAccepted());
    }
}
