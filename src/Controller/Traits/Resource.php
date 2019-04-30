<?php namespace Framework\MVC\Controller\Traits;

use Framework\HTTP\Response;

trait Resource
{
	protected function respond(int $code, $data = null) : Response
	{
		if ($data !== null) {
			$this->response->setJSON($data);
		}
		return $this->response->setStatusCode($code);
	}

	protected function respondOK($data = null) : Response
	{
		return $this->respond(200, $data);
	}

	protected function respondCreated($data = null) : Response
	{
		return $this->respond(201, $data);
	}

	protected function respondAccepted($data = null) : Response
	{
		return $this->respond(202, $data);
	}

	protected function respondNoContent($data = null) : Response
	{
		return $this->respond(204, $data);
	}

	protected function respondNotModified($data = null) : Response
	{
		return $this->respond(304, $data);
	}

	protected function respondBadRequest($data = null) : Response
	{
		return $this->respond(400, $data);
	}

	protected function respondForbidden($data = null) : Response
	{
		return $this->respond(403, $data);
	}

	protected function respondNotFound($data = null) : Response
	{
		return $this->respond(404, $data);
	}
}
