<?php namespace Framework\MVC\Controller;

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
	 * @param int        $code
	 * @param mixed|null $data
	 *
	 * @return \Framework\HTTP\Response
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
	 * @param mixed|null $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/200
	 *
	 * @return \Framework\HTTP\Response
	 */
	protected function respondOK(mixed $data = null) : Response
	{
		return $this->respond(200, $data);
	}

	/**
	 * Responds an HTTP 201 (Created) status code and data.
	 *
	 * @param mixed|null $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/201
	 *
	 * @return \Framework\HTTP\Response
	 */
	protected function respondCreated(mixed $data = null) : Response
	{
		return $this->respond(201, $data);
	}

	/**
	 * Responds an HTTP 202 (Accepted) status code and data.
	 *
	 * @param mixed|null $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/202
	 *
	 * @return \Framework\HTTP\Response
	 */
	protected function respondAccepted(mixed $data = null) : Response
	{
		return $this->respond(202, $data);
	}

	/**
	 * Responds an HTTP 204 (No Content) status code and data.
	 *
	 * @param mixed|null $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/204
	 *
	 * @return \Framework\HTTP\Response
	 */
	protected function respondNoContent(mixed $data = null) : Response
	{
		return $this->respond(204, $data);
	}

	/**
	 * Responds an HTTP 304 (Not Modified) status code and data.
	 *
	 * @param mixed|null $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/304
	 *
	 * @return \Framework\HTTP\Response
	 */
	protected function respondNotModified(mixed $data = null) : Response
	{
		return $this->respond(304, $data);
	}

	/**
	 * Responds an HTTP 400 (Bad Request) status code and data.
	 *
	 * @param mixed|null $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400
	 *
	 * @return \Framework\HTTP\Response
	 */
	protected function respondBadRequest(mixed $data = null) : Response
	{
		return $this->respond(400, $data);
	}

	/**
	 * Responds an HTTP 401 (Unauthorized) status code and data.
	 *
	 * @param mixed|null $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/401
	 *
	 * @return \Framework\HTTP\Response
	 */
	protected function respondUnauthorized(mixed $data = null) : Response
	{
		return $this->respond(401, $data);
	}

	/**
	 * Responds an HTTP 403 (Forbidden) status code and data.
	 *
	 * @param mixed|null $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/403
	 *
	 * @return \Framework\HTTP\Response
	 */
	protected function respondForbidden(mixed $data = null) : Response
	{
		return $this->respond(403, $data);
	}

	/**
	 * Responds an HTTP 404 (Not Found) status code and data.
	 *
	 * @param mixed|null $data
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/404
	 *
	 * @return \Framework\HTTP\Response
	 */
	protected function respondNotFound(mixed $data = null) : Response
	{
		return $this->respond(404, $data);
	}
}
