<?php namespace Tests\MVC;

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

	public function index()
	{
	}

	public function new()
	{
	}

	public function create()
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
