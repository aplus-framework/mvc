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
	 * Responds an HTTP 102 (Processing) status code and data.
	 *
	 * The 102 (Processing) status code is an interim response used to inform
	 * the client that the server has accepted the complete request, but has not
	 * yet completed it. This status code SHOULD only be sent when the server
	 * has a reasonable expectation that the request will take significant time
	 * to complete. As guidance, if a method is taking longer than 20 seconds
	 * (a reasonable, but arbitrary value) to process the server SHOULD return a
	 * 102 (Processing) response. The server MUST send a final response after
	 * the request has been completed.
	 *
	 * Methods can potentially take a long period of time to process, especially
	 * methods that support the Depth header. In such cases the client may
	 * time-out the connection while waiting for a response. To prevent this the
	 * server may return a 102 (Processing) status code to indicate to the
	 * client that the server is still processing the method.
	 *
	 * NOTE: We will use it in case, for some strange reason, a inserted/updated
	 * row could not be selected. Database for write/read out of sync in a
	 * master-slave config, maybe...
	 *
	 * @param mixed $data
	 *
	 * @see https://www.restapitutorial.com/httpstatuscodes.html
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/102
	 *
	 * @return Response
	 */
	protected function respondProcessing(mixed $data = null) : Response
	{
		return $this->respond(Response::CODE_PROCESSING, $data);
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
		return $this->respond(Response::CODE_OK, $data);
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
		return $this->respond(Response::CODE_CREATED, $data);
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
		return $this->respond(Response::CODE_ACCEPTED, $data);
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
		return $this->respond(Response::CODE_NO_CONTENT, $data);
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
		return $this->respond(Response::CODE_NOT_MODIFIED, $data);
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
		return $this->respond(Response::CODE_BAD_REQUEST, $data);
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
		return $this->respond(Response::CODE_UNAUTHORIZED, $data);
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
		return $this->respond(Response::CODE_FORBIDDEN, $data);
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
		return $this->respond(Response::CODE_NOT_FOUND, $data);
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
		return $this->respond(Response::CODE_METHOD_NOT_ALLOWED, $data);
	}
}
