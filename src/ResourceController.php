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

	public function index() : mixed
	{
		$page = $this->request->getQuery('page') ?? 1;
		$page = Pager::sanitizePageNumber($page);
		$entities = $this->model->paginate($page);
		$data = [
			'status' => $this->getStatus($this->response::CODE_OK),
			'data' => $entities,
			'links' => $this->model->getPager(),
		];
		$link = $this->model->getPager()->render('header');
		if ($link) {
			$this->response->setHeader($this->response::HEADER_LINK, $link);
		}
		return $this->respondOK($data);
	}

	public function create() : mixed
	{
		$input = $this->request->isJSON()
			? $this->request->getJSON()
			: $this->request->getPOST();
		$id = $this->model->create($input);
		if ($id === false) {
			$data = [
				'status' => $this->getStatus($this->response::CODE_BAD_REQUEST),
				'errors' => $this->model->getErrors(),
			];
			return $this->respondBadRequest($data);
		}
		$routeName = current_route()->getName();
		if (isset($routeName) && \str_ends_with($routeName, '.create')) {
			$routeName = \substr($routeName, -6);
			$routeName .= 'show';
			$this->response->setHeader(
				$this->response::HEADER_LOCATION,
				route_url($routeName, [$id])
			);
		}
		$entity = $this->model->find($id);
		$data = [
			'status' => $this->getStatus($this->response::CODE_CREATED),
			'data' => $entity,
		];
		return $this->respondCreated($data);
	}

	public function show(string $id) : mixed
	{
		$entity = $this->model->find($id);
		if ($entity === null) {
			return $this->respondNotFound([
				'status' => $this->getStatus($this->response::CODE_NOT_FOUND),
			]);
		}
		$data = [
			'status' => $this->getStatus($this->response::CODE_OK),
			'data' => $entity,
		];
		return $this->respondOK($data);
	}

	public function update(string $id) : mixed
	{
		$entity = $this->model->find($id);
		if ($entity === null) {
			$data = [
				'status' => $this->getStatus($this->response::CODE_NOT_FOUND),
			];
			return $this->respondNotFound($data);
		}
		$input = $this->request->isJSON()
			? $this->request->getJSON()
			: $this->request->getParsedBody();
		$affectedRows = $this->model->update($id, $input);
		if ($affectedRows === false) {
			$data = [
				'status' => $this->getStatus($this->response::CODE_BAD_REQUEST),
				'errors' => $this->model->getErrors(),
			];
			return $this->respondBadRequest($data);
		}
		$data = [
			'status' => $this->getStatus($this->response::CODE_OK),
			'data' => $this->model->find($id),
		];
		return $this->respondOK($data);
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
}
