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
 *
 * @package mvc
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
     * @var class-string<ModelInterface>
     */
    protected string $modelClass;
    /**
     * The instance of the $modelClass FQCN.
     *
     * Tip: Append the $modelClass type to the declaration of this property to
     * enable an improved code-completion in your code editor.
     * ...Or update the PHPDoc var!
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
     * Render a view.
     *
     * @param string $view The view file
     * @param array<string,mixed> $variables The variables passed to the view
     * @param string $instance The View service instance name
     *
     * @return string The rendered view contents
     */
    protected function render(
        string $view,
        array $variables = [],
        string $instance = 'default'
    ) : string {
        return App::view($instance)->render($view, $variables);
    }

    /**
     * Validate data.
     *
     * @param array<string,mixed> $data The data to be validated
     * @param array<string,array<string>|string> $rules An associative array with field
     * as keys and values as rules
     * @param array<string,string> $labels An associative array with fields as
     * keys and label as values
     * @param array<string,array<string,string>> $messages A multi-dimensional
     * array with field names as keys and values as arrays where the keys are
     * rule names and values are the custom error message strings
     * @param string $instance The Validation service instance name
     *
     * @return array<string,string> An empty array if validation pass or an
     * associative array with field names as keys and error messages as values
     */
    protected function validate(
        array $data,
        array $rules,
        array $labels = [],
        array $messages = [],
        string $instance = 'default'
    ) : array {
        $validation = App::validation($instance);
        return $validation->setRules($rules)->setLabels($labels)
            ->setMessages($messages)->validate($data)
            ? []
            : $validation->getErrors();
    }
}
