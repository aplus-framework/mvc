<?php namespace Tests\MVC;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\MVC\PresenterController;

class PresenterControllerMock extends PresenterController
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
