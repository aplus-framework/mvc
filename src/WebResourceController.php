<?php namespace Framework\MVC;

use Framework\HTTP\Response;

abstract class WebResourceController extends ResourceController
{
	/**
	 * Handles a GET request for /new.
	 *
	 * @return Response|string|null
	 */
	abstract protected function new();

	/**
	 * Handles a GET request for /$id/edit.
	 *
	 * @var int|string $id
	 *
	 * @return Response|string|null
	 */
	abstract protected function edit(int $id);
}
