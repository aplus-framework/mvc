<?php namespace Framework\MVC;

use Framework\Date\Date;

abstract class Entity implements \JsonSerializable
{
	public function __construct(array $properties)
	{
		$this->populate($properties);
	}

	public function __isset($property)
	{
		return isset($this->{$property});
	}

	public function __unset($property)
	{
		if (\property_exists($this, $property)) {
			$this->{$property} = null;
		}
	}

	public function __set($property, $value)
	{
		$method = $this->renderMethodName('set', $property);
		if (\method_exists($this, $method)) {
			$this->{$method}($value);
			return;
		}
		if (\property_exists($this, $property)) {
			$this->{$property} = $value;
			return;
		}
		throw new \OutOfBoundsException("Property not defined: {$property}");
	}

	public function __get($property)
	{
		$method = $this->renderMethodName('get', $property);
		if (\method_exists($this, $method)) {
			return $this->{$method}();
		}
		if (\property_exists($this, $property)) {
			return $this->{$property};
		}
		throw new \OutOfBoundsException("Property not defined: {$property}");
	}

	public function __toString()
	{
		return $this->toScalarJSON($this->toArray());
	}

	protected function renderMethodName(string $type, string $property) : string
	{
		static $properties;
		if (isset($properties[$property])) {
			return "{$type}{$properties[$property]}";
		}
		$name = \ucwords($property, '_');
		$name = \strtr($name, ['_' => '']);
		$properties[$property] = $name;
		return "{$type}{$name}";
	}

	protected function populate(array $properties) : void
	{
		foreach ($properties as $property => $value) {
			$method = $this->renderMethodName('set', $property);
			if (\method_exists($this, $method)) {
				$this->{$method}($value);
				continue;
			}
			$this->{$property} = $value;
		}
	}

	/**
	 * Used in setters where the scalar conversion results in a JSON string.
	 *
	 * @param array|\stdClass|null $value
	 *
	 * @see toScalar
	 *
	 * @return \stdClass|null
	 */
	protected function fromJSON($value) : ?\stdClass
	{
		if ($value === null) {
			return null;
		}
		if ($value instanceof \stdClass || \is_array($value)) {
			return (object) $value;
		}
		return \json_decode($value, false, 512, $this->jsonOptions());
	}

	protected function jsonOptions() : int
	{
		return \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
			| \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR;
	}

	/**
	 * @param array|object $value
	 *
	 * @return string
	 */
	protected function toScalarJSON($value) : string
	{
		return \json_encode($value, $this->jsonOptions(), 512);
	}

	/**
	 * Used in setters where the scalar conversion results in a datetime string.
	 *
	 * @param Date|string|null $value
	 *
	 * @see toScalar
	 *
	 * @return Date|null
	 */
	protected function fromDateTime($value) : ?Date
	{
		if ($value === null) {
			return null;
		}
		if ($value instanceof Date) {
			return $value;
		}
		if ( ! \is_string($value)) {
			throw new \InvalidArgumentException('Value type must be string or Framework\Date\Date');
		}
		return new Date($value, $this->timezone());
	}

	protected function timezone() : \DateTimeZone
	{
		static $timezone;
		return $timezone ?: $timezone = new \DateTimeZone('UTC');
	}

	/**
	 * Converts the value to the string format Y-m-d H:i:s as UTC or custom timezone.
	 *
	 * @param Date $value
	 *
	 * @see timezone
	 *
	 * @return string
	 */
	protected function toScalarDateTime(Date $value) : string
	{
		$value = clone $value;
		return $value->setTimezone($this->timezone())->format(Date::ATOM);
	}

	/**
	 * Converts a property value into scalar type or null.
	 *
	 * If a settter/getter ending with AsScalar exists, (i.e. getConfigAsScalar), it will run to
	 * render the proper value.
	 *
	 * stdClass or array types are converted to a JSON string.
	 *
	 * DateTime instances are converted to a string in the format Y-m-d H:i:s
	 *
	 * @param string $property
	 *
	 * @return bool|float|int|string|null
	 */
	protected function toScalar(string $property)
	{
		if (\is_scalar($this->{$property})) {
			return $this->{$property};
		}
		$method = $this->renderMethodName('get', $property) . 'AsScalar';
		if (\method_exists($this, $method)) {
			return $this->{$method}();
		}
		if ($this->{$property} === null) {
			return null; // Not scalar, but Database::quote handles it!
		}
		if ($this->{$property} instanceof \stdClass || \is_array($this->{$property})) {
			return $this->toScalarJSON($this->{$property});
		}
		if ($this->{$property} instanceof Date) {
			return $this->toScalarDateTime($this->{$property});
		}
		throw new \RuntimeException(
			"Property was not converted to scalar: {$property}"
		);
	}

	/**
	 * Converts the Entity properties values to scalar and returns an associative array.
	 *
	 * @return array
	 */
	public function toArray() : array
	{
		$data = [];
		foreach (\array_keys(\get_object_vars($this)) as $property) {
			$data[$property] = $this->toScalar($property);
		}
		return $data;
	}

	public function jsonSerialize()
	{
		// TODO: whitelist properties and filter it!!!
		//https://www.electrictoolbox.com/php-reflection-public-properties-not-static/
		//\get_class_vars(__CLASS__);
		return $this->toArray();
	}
}
