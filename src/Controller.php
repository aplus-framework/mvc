<?php namespace Framework\MVC;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\RouteAction;

abstract class Controller extends RouteAction
{
	/**
	 * @var Request
	 */
	protected $request;
	/**
	 * @var Response
	 */
	protected $response;

	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;
	}

	protected function validate(array $rules, array $data) : array
	{
		return App::getValidation()->setRules($rules)->validate($data)
			? []
			: App::getValidation()->getErrors();
	}
}
