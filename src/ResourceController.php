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

use Framework\Routing\ResourceInterface;

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
		return $this->respondMethodNotAllowed();
	}

	public function create() : mixed
	{
		return $this->respondMethodNotAllowed();
	}

	public function show(string $id) : mixed
	{
		return $this->respondMethodNotAllowed();
	}

	public function update(string $id) : mixed
	{
		return $this->respondMethodNotAllowed();
	}

	public function replace(string $id) : mixed
	{
		return $this->respondMethodNotAllowed();
	}

	public function delete(string $id) : mixed
	{
		return $this->respondMethodNotAllowed();
	}
}
