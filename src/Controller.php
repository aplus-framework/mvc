<?php namespace Framework\MVC;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\RouteAction;

abstract class Controller extends RouteAction
{
	protected Request $request;
	protected Response $response;
	protected string $modelClass;
	protected Model $model;

	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;
		if (isset($this->modelClass)) {
			$this->model = new $this->modelClass();
		}
	}

	protected function validate(array $rules, array $data) : array
	{
		return App::validation()->setRules($rules)->validate($data)
			? []
			: App::validation()->getErrors();
	}
}
