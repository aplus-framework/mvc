<?php
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC;

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
