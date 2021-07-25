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

/**
 * Class Controller.
 */
abstract class Controller extends RouteActions
{
    /**
     * The matched route Request.
     *
     * @var Request
     */
    protected Request $request;
    /**
     * The matched route Response.
     *
     * @var Response
     */
    protected Response $response;
    /**
     * A Full Qualified Class Name of a model.
     *
     * If this property is set, the $model property will be set with a new
     * instance of this FQCN in the Controller constructor.
     *
     * @var string
     */
    protected string $modelClass;
    /**
     * The instance of the $modelClass FQCN.
     *
     * Tip: Append the $modelClass type to the declaration of this property to
     * enable an improved code-completion in your code editor.
     *
     * @var ModelInterface
     */
    protected ModelInterface $model;

    /**
     * Controller constructor.
     *
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        if (isset($this->modelClass)) {
            $this->model = new $this->modelClass();
        }
    }

    /**
     * Validate data.
     *
     * @param array<string,array|string> $rules An associative array with fields
     * as keys and values as rules
     * @param array<string,mixed> $data The data to be validated
     * @param array<string,string> $labels An associative array with fields as
     * keys and labels as values
     *
     * @return array<string,string> An empty array if validation pass or an
     * associative array with field names as keys and error messages as values
     */
    protected function validate(array $rules, array $data, array $labels = []) : array
    {
        return App::validation()->setRules($rules)->setLabels($labels)->validate($data)
            ? []
            : App::validation()->getErrors();
    }
}
