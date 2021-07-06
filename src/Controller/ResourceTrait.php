<?php declare(strict_types=1);
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC\Controller;

use Framework\HTTP\Response;

/**
 * Trait ResourceTrait.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
 */
trait ResourceTrait
{
	/**
	 * Respond a custom HTTP status code and data.
	 *
	 * @param int $code
	 * @param mixed $data
	 *
	 * @return Response
	 */
	protected function respond(int $code, mixed $data = null) : Response
	{
		if ($data !== null) {
			$this->response->setJSON($data);
		}
		return $this->response->setStatusLine($code);
	}

	/**
	 * Responds an HTTP 200 (OK) status code and data.
	 *
	 * @param mixed $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/200
	 *
	 * @return Response
	 */
	protected function respondOK(mixed $data = null) : Response
	{
		return $this->respond(200, $data);
	}

	/**
	 * Responds an HTTP 201 (Created) status code and data.
	 *
	 * @param mixed $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/201
	 *
	 * @return Response
	 */
	protected function respondCreated(mixed $data = null) : Response
	{
		return $this->respond(201, $data);
	}

	/**
	 * Responds an HTTP 202 (Accepted) status code and data.
	 *
	 * @param mixed $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/202
	 *
	 * @return Response
	 */
	protected function respondAccepted(mixed $data = null) : Response
	{
		return $this->respond(202, $data);
	}

	/**
	 * Responds an HTTP 204 (No Content) status code and data.
	 *
	 * @param mixed $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/204
	 *
	 * @return Response
	 */
	protected function respondNoContent(mixed $data = null) : Response
	{
		return $this->respond(204, $data);
	}

	/**
	 * Responds an HTTP 304 (Not Modified) status code and data.
	 *
	 * @param mixed $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/304
	 *
	 * @return Response
	 */
	protected function respondNotModified(mixed $data = null) : Response
	{
		return $this->respond(304, $data);
	}

	/**
	 * Responds an HTTP 400 (Bad Request) status code and data.
	 *
	 * @param mixed $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400
	 *
	 * @return Response
	 */
	protected function respondBadRequest(mixed $data = null) : Response
	{
		return $this->respond(400, $data);
	}

	/**
	 * Responds an HTTP 401 (Unauthorized) status code and data.
	 *
	 * @param mixed $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/401
	 *
	 * @return Response
	 */
	protected function respondUnauthorized(mixed $data = null) : Response
	{
		return $this->respond(401, $data);
	}

	/**
	 * Responds an HTTP 403 (Forbidden) status code and data.
	 *
	 * @param mixed $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/403
	 *
	 * @return Response
	 */
	protected function respondForbidden(mixed $data = null) : Response
	{
		return $this->respond(403, $data);
	}

	/**
	 * Responds an HTTP 404 (Not Found) status code and data.
	 *
	 * @param mixed $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/404
	 *
	 * @return Response
	 */
	protected function respondNotFound(mixed $data = null) : Response
	{
		return $this->respond(404, $data);
	}

	/**
	 * Responds an HTTP 405 (Method Not Allowed) status code and data.
	 *
	 * @param mixed $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/405
	 *
	 * @return Response
	 */
	protected function respondMethodNotAllowed(mixed $data = null) : Response
	{
		return $this->respond(405, $data);
	}
}
