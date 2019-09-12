<?php namespace Tests\MVC;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\MVC\Controller;

class ControllerMock extends Controller
{
	public function __construct()
	{
		$request = new class() extends Request {
			protected function filterInput(
				int $type,
				string $variable = null,
				int $filter = null,
				$options = null
			) {
				$data = [];
				if ($type === \INPUT_SERVER) {
					$data = [
						'HTTP_HOST' => 'localhost',
						'REQUEST_METHOD' => 'GET',
						'SERVER_PROTOCOL' => 'HTTP/1.1',
					];
				}
				$variable = $variable === null
					? $data
					: \ArraySimple::value($variable, $data);
				return $filter
					? \filter_var($variable, $filter, $options)
					: $variable;
			}
		};
		parent::__construct($request, new Response($request));
	}

	public function validate(array $rules, array $data) : array
	{
		return parent::validate($rules, $data);
	}
}
