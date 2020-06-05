<?php namespace Framework\MVC;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Theme\Theme;

abstract class Controller
{
	protected Request $request;
	protected Response $response;
	protected Theme $theme;

	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;
		$this->theme = new Theme();
	}

	public function init()
	{
	}

	protected function validate(array $rules, array $data) : array
	{
		return App::validation()->setRules($rules)->validate($data)
			? []
			: App::validation()->getErrors();
	}

	protected function renderPage(string $view, array $data = []) : string
	{
		if ($view[0] !== '\\') {
			$view = 'pages/' . $view;
		}
		App::autoloader()->setNamespace('Framework\MVC', __DIR__);
		return App::view()->render('\Framework\MVC\View/layout', [
			'content' => App::view()->render($view, $data),
			'data' => $data,
			'theme' => $this->theme,
		]);
	}
}
