<?php namespace Tests\MVC;

use Framework\Date\Date;
use Framework\MVC\Entity;

/**
 * Class EntityMock.
 *
 * @property int    $id
 * @property string $data
 * @property Date   $datetime
 * @property Date   $createdAt
 * @property Date   $updatedAt
 * @property string $settings
 */
class EntityMock extends Entity
{
	protected $id;
	protected $data;
	protected $datetime;
	protected $createdAt;
	protected $updatedAt;
	protected $settings;

	public function setId($id)
	{
		$this->id = (int) $id;
	}

	public function getData() : string
	{
		return (string) $this->data;
	}

	public function setDatetime($datetime)
	{
		$this->datetime = $this->fromDateTime($datetime);
	}

	public function setSettings($settings)
	{
		$this->settings = $this->fromJSON($settings);
	}

	public function getDataAsScalar() : string
	{
		return (string) $this->data;
	}
}
