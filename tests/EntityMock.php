<?php namespace Tests\MVC;

use Framework\MVC\Entity;

class EntityMock extends Entity
{
	protected $id;
	protected $datetime;

	public function setId($id)
	{
		$this->id = (int) $id;
	}

	public function setDatetime($datetime)
	{
		$this->datetime = $this->fromDateTime($datetime);
	}
}
