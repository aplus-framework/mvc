<?php namespace Tests\MVC;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\MVC\ResourceController;

class ResourceControllerMock extends ResourceController
{
	public function __construct()
	{
		$request = new class() extends Request {
			public function __construct(string $host = null)
			{
				$this->input['SERVER']['HTTP_HOST'] = 'localhost';
				parent::__construct($host);
			}
		};
		parent::__construct($request, new Response($request));
	}

	public function index()
	{
	}

	public function create()
	{
	}

	public function show(int $id)
	{
		return $id;
	}

	public function update(int $id)
	{
		return $id;
	}

	public function delete(int $id)
	{
		return $id;
	}

	public function replace($id)
	{
		return $id;
	}
}
