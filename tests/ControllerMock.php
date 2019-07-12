<?php namespace Tests\MVC;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\MVC\Controller;

class ControllerMock extends Controller
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

	public function validate(array $rules, array $data) : array
	{
		return parent::validate($rules, $data);
	}
}
