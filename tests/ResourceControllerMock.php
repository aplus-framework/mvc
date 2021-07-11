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

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\MVC\ResourceController;

class ResourceControllerMock extends ResourceController
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

    public function create() : mixed
    {
        return null;
    }

    public function show(string $id) : mixed
    {
        return $id;
    }

    public function update(string $id) : mixed
    {
        return $id;
    }

    public function delete(string $id) : mixed
    {
        return $id;
    }

    public function replace($id) : mixed
    {
        return $id;
    }

    public function respondAccepted($data = null) : Response
    {
        return parent::respondAccepted($data);
    }

    public function respondBadRequest($data = null) : Response
    {
        return parent::respondBadRequest($data);
    }

    public function respondUnauthorized($data = null) : Response
    {
        return parent::respondUnauthorized($data);
    }

    public function respondCreated($data = null) : Response
    {
        return parent::respondCreated($data);
    }

    public function respondForbidden($data = null) : Response
    {
        return parent::respondForbidden($data);
    }

    public function respondNoContent($data = null) : Response
    {
        return parent::respondNoContent($data);
    }

    public function respondNotFound($data = null) : Response
    {
        return parent::respondNotFound($data);
    }

    public function respondNotModified($data = null) : Response
    {
        return parent::respondNotModified($data);
    }

    public function respondOK($data = null) : Response
    {
        return parent::respondOK($data);
    }
}
