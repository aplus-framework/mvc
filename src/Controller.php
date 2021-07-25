<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\RouteActions;

abstract class Controller extends RouteActions
{
    protected Request $request;
    protected Response $response;
    protected string $modelClass;
    protected ModelInterface | Model $model;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        if (isset($this->modelClass)) {
            $this->model = new $this->modelClass();
        }
    }

    protected function validate(array $rules, array $data, array $labels = []) : array
    {
        return App::validation()->setRules($rules)->setLabels($labels)->validate($data)
            ? []
            : App::validation()->getErrors();
    }
}
