<?php namespace Tests\MVC;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\MVC\Controller;

class ControllerMock extends Controller
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

	public function validate(array $rules, array $data) : array
	{
		return parent::validate($rules, $data);
	}
}
