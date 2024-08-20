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
use ReflectionClass;
use ReflectionNamedType;

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
     * Set true to load models in properties.
     *
     * @see Controller::prepareModels()
     *
     * @var bool
     */
    protected bool $loadModels = true;

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
        if ($this->loadModels) {
            $this->prepareModels();
        }
    }

    /**
     * Initialize models in properties.
     *
     * @since 4
     *
     * @return static
     */
    protected function prepareModels() : static
    {
        $reflection = new ReflectionClass($this);
        foreach ($reflection->getProperties() as $property) {
            $type = $property->getType();
            if (!$type instanceof ReflectionNamedType) {
                continue;
            }
            $class = $type->getName();
            if (!\is_subclass_of($class, Model::class)) {
                continue;
            }
            $name = $property->name;
            if (isset($this->{$name})) {
                continue;
            }
            $this->{$name} = Model::get($class);
        }
        return $this;
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
