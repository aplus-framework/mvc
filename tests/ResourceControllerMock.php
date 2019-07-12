<?php namespace Tests\MVC;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\MVC\ResourceController;

class ResourceControllerMock extends ResourceController
{
	public function __construct()
	{
		$request = new class() extends Request {
			protected function filterInput(int $type) : array
			{
				if ($type === \INPUT_SERVER) {
					return [
						'HTTP_HOST' => 'localhost',
						'REQUEST_METHOD' => 'GET',
						'SERVER_PROTOCOL' => 'HTTP/1.1',
					];
				}
				return [];
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

	public function respondAccepted($data = null) : Response
	{
		return parent::respondAccepted($data);
	}

	public function respondBadRequest($data = null) : Response
	{
		return parent::respondBadRequest($data);
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
