<?php declare(strict_types=1);
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC;

use Framework\Pagination\Pager;
use Framework\Routing\ResourceInterface;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use stdClass;

/**
 * Class ResourceController.
 *
 * By default, all class methods return the HTTP status 405 (Method Not Allowed),
 * because the server does recognize the REQUEST_METHOD, but intentionally does
 * not support it.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/501
 */
abstract class ResourceController extends Controller implements ResourceInterface
{
    use Controller\ResourceTrait;

    public function index(string ...$args) : mixed
    {
        $items = $this->indexPaginateData();
        foreach ($items as &$item) {
            $this->indexTransformData($item);
        }
        unset($item);
        $data = [
            'status' => $this->getStatus($this->response::CODE_OK),
            'data' => $items,
            'links' => $this->model->getPager(),
        ];
        $link = $this->model->getPager()->render('header');
        if ($link) {
            $this->response->setHeader($this->response::HEADER_LINK, $link);
        }
        return $this->respondOK($data);
    }

    protected function indexPaginateData() : array
    {
        $page = $this->request->getQuery('page') ?? 1;
        $page = Pager::sanitizePageNumber($page);
        return $this->model->paginate($page);
    }

    /**
     * @param array<string,scalar>|Entity|stdClass $item
     */
    protected function indexTransformData(array | Entity | stdClass &$item) : void
    {
    }

    public function create() : mixed
    {
        $input = $this->request->isJSON()
            ? $this->request->getJSON(true)
            : $this->request->getPOST();
        $data = $this->fieldsNotAllowedData($input);
        if ($data) {
            return $this->respondUnprocessableEntity($data);
        }
        $id = $this->model->create($input);
        if ($id === false) {
            $data = [
                'status' => $this->getStatus($this->response::CODE_BAD_REQUEST),
                'errors' => $this->model->getErrors(),
            ];
            return $this->respondBadRequest($data);
        }
        $item = $this->createFindData((string) $id);
        if ($item === null) {
            $data = [
                'status' => $this->getStatus($this->response::CODE_PROCESSING),
            ];
            return $this->respondProcessing($data);
        }
        $routeName = current_route()->getName();
        if (isset($routeName) && \str_ends_with($routeName, '.create')) {
            $routeName = \substr($routeName, 0, -6);
            $routeName .= 'show';
            $this->response->setHeader(
                $this->response::HEADER_LOCATION,
                route_url($routeName, [$id])
            );
        }
        $this->createTransformData($item);
        $data = [
            'status' => $this->getStatus($this->response::CODE_CREATED),
            'data' => $item,
        ];
        return $this->respondCreated($data);
    }

    protected function createFindData(string ...$args) : array | Entity | stdClass | null
    {
        return $this->model->find($args[0]);
    }

    /**
     * @param array<string,scalar>|Entity|stdClass $item
     */
    protected function createTransformData(array | Entity | stdClass &$item) : void
    {
    }

    public function show(string $id) : mixed
    {
        $item = $this->showFindData($id);
        if ($item === null) {
            return $this->respondNotFound([
                'status' => $this->getStatus($this->response::CODE_NOT_FOUND),
            ]);
        }
        $this->showTransformData($item);
        $data = [
            'status' => $this->getStatus($this->response::CODE_OK),
            'data' => $item,
        ];
        return $this->respondOK($data);
    }

    protected function showFindData(string ...$args) : array | Entity | stdClass | null
    {
        return $this->model->find($args[0]);
    }

    /**
     * @param array<string,scalar>|Entity|stdClass $item
     */
    protected function showTransformData(array | Entity | stdClass &$item) : void
    {
    }

    public function update(string $id) : mixed
    {
        $input = $this->request->isJSON()
            ? $this->request->getJSON(true)
            : $this->request->getParsedBody();
        $data = $this->fieldsNotAllowedData($input);
        if ($data) {
            return $this->respondUnprocessableEntity($data);
        }
        $entity = $this->model->find($id);
        if ($entity === null) {
            $data = [
                'status' => $this->getStatus($this->response::CODE_NOT_FOUND),
            ];
            return $this->respondNotFound($data);
        }
        $affectedRows = $this->model->update($id, $input);
        if ($affectedRows === false) {
            $data = [
                'status' => $this->getStatus($this->response::CODE_BAD_REQUEST),
                'errors' => $this->model->getErrors(),
            ];
            return $this->respondBadRequest($data);
        }
        $item = $this->updateFindData($id);
        if ($item === null) {
            $data = [
                'status' => $this->getStatus($this->response::CODE_PROCESSING),
            ];
            return $this->respondProcessing($data);
        }
        $this->updateTransformData($item);
        $data = [
            'status' => $this->getStatus($this->response::CODE_OK),
            'data' => $item,
        ];
        return $this->respondOK($data);
    }

    protected function updateFindData(string ...$args) : array | Entity | stdClass | null
    {
        return $this->model->find($args[0]);
    }

    /**
     * @param array<string,scalar>|Entity|stdClass $item
     */
    protected function updateTransformData(array | Entity | stdClass &$item) : void
    {
    }

    public function replace(string $id) : mixed
    {
        $this->response->setHeader(
            $this->response::HEADER_ALLOW,
            'DELETE, GET, HEAD, PATCH'
        );
        $data = [
            'status' => $this->getStatus($this->response::CODE_METHOD_NOT_ALLOWED),
        ];
        return $this->respondMethodNotAllowed($data);
    }

    public function delete(string $id) : mixed
    {
        $entity = $this->model->find($id);
        if ($entity === null) {
            $data = [
                'status' => $this->getStatus($this->response::CODE_NOT_FOUND),
            ];
            return $this->respondNotFound($data);
        }
        $this->model->delete($id);
        $data = [
            'status' => $this->getStatus($this->response::CODE_OK),
        ];
        return $this->respondOK($data);
    }

    /**
     * @param int $code
     *
     * @return array<string,int|string|null>
     */
    #[ArrayShape(['code' => 'int', 'reason' => 'string|null'])]
    #[Pure]
    protected function getStatus(
        int $code
    ) : array {
        return [
            'code' => $code,
            'reason' => $this->response::getResponseReason($code),
        ];
    }

    /**
     * @param array<string,mixed> $input
     *
     * @return array<string,array|int>
     */
    protected function fieldsNotAllowedData(array $input) : array
    {
        $allowedFields = $this->model->getAllowedFields();
        $diff = \array_diff_key($input, \array_flip($allowedFields));
        if (empty($diff)) {
            return [];
        }
        $errors = [];
        foreach (\array_keys($diff) as $field) {
            $errors[$field] = lang('errors.fieldNotAllowed', [$field]);
        }
        return [
            'status' => $this->getStatus($this->response::CODE_UNPROCESSABLE_ENTITY),
            'errors' => $errors,
        ];
    }
}
