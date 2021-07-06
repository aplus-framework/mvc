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

/**
 * Class ResourceController.
 */
abstract class ResourceController extends Controller
{
	use Controller\ResourceTrait;

	/**
	 * Handles a GET request for /.
	 *
	 * @return Response|string|null
	 */
	abstract protected function index();

	/**
	 * Handles a POST request for /.
	 *
	 * @return Response|string|null
	 */
	abstract protected function create();

	/**
	 * Handles a GET request for /$id.
	 *
	 * @see https://wiki.php.net/rfc/parameter_type_casting_hints
	 * @see https://wiki.php.net/rfc/parameter-no-type-variance
	 *
	 * @var int|string $id To use $id as string, just remove the int type hint
	 *
	 * @return Response|string|null
	 */
	abstract protected function show(int $id);

	/**
	 * Handles a PATCH request for /$id
	 * and/or a POST request for /$id/update on Web Resource.
	 *
	 * @var int|string $id
	 *
	 * @return Response|string|null
	 */
	abstract protected function update(int $id);

	/**
	 * Handles a PUT request for /$id.
	 *
	 * @var int|string $id
	 *
	 * @return Response|string|null
	 */
	abstract protected function replace(int $id);

	/**
	 * Handles a DELETE request for /$id
	 * and/or a POST request for /$id/delete on Web Resource.
	 *
	 * @var int|string $id
	 *
	 * @return Response|string|null
	 */
	abstract protected function delete(int $id);
}
