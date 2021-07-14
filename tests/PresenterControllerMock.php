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

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\MVC\PresenterController;

class PresenterControllerMock extends PresenterController
{
    public function __construct()
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
        $request = new Request();
        parent::__construct($request, new Response($request));
    }

    public function index() : mixed
    {
        return null;
    }

    public function new() : mixed
    {
        return null;
    }

    public function create() : mixed
    {
        return null;
    }

    public function show(string $id) : mixed
    {
        return $id;
    }

    public function edit(string $id) : mixed
    {
        return $id;
    }

    public function update(string $id) : mixed
    {
        return $id;
    }

    public function remove(string $id) : mixed
    {
        return $id;
    }

    public function delete(string $id) : mixed
    {
        return $id;
    }
}
