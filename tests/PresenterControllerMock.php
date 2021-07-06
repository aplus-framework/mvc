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

	public function index() : void
	{
	}

	public function new() : void
	{
	}

	public function create() : void
	{
	}

	public function show(int $id)
	{
		return $id;
	}

	public function edit(int $id)
	{
		return $id;
	}

	public function update(int $id)
	{
		return $id;
	}

	public function remove(int $id)
	{
		return $id;
	}

	public function delete(int $id)
	{
		return $id;
	}
}
