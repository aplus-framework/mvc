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
use Framework\MVC\Controller;
use Framework\MVC\Model;

class ControllerMock extends Controller
{
    protected string $modelClass = ModelMock::class;
    public Model $model;

    public function __construct()
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
        $request = new Request();
        parent::__construct($request, new Response($request));
    }

    public function validate(array $rules, array $data, array $labels = []) : array
    {
        return parent::validate($rules, $data, $labels);
    }
}
