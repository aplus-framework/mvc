<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC;

use DateTimeZone;
use Framework\Date\Date;
use Framework\HTTP\URL;
use InvalidArgumentException;
use JsonException;
use OutOfBoundsException;
use ReflectionProperty;
use RuntimeException;
use stdClass;

/**
 * Class Entity.
 *
 * @package mvc
 */
abstract class Entity implements \JsonSerializable //, \Stringable
{
    protected static array $jsonVars = [];

    public function __construct(array $properties)
    {
        $this->populate($properties);
    }

    public function __isset($property)
    {
        return isset($this->{$property});
    }

    public function __unset($property) : void
    {
        if (\property_exists($this, $property)) {
            $this->{$property} = null;
        }
    }

    /**
     * @param string $property
     * @param mixed $value
     *
     * @throws \OutOfBoundsException if property not defined
     */
    public function __set(string $property, $value) : void
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
        throw new OutOfBoundsException("Property not defined: {$property}");
    }

    /**
     * @param string $property
     *
     * @throws \OutOfBoundsException if property not defined
     *
     * @return mixed
     */
    public function __get(string $property)
    {
        $method = $this->renderMethodName('get', $property);
        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }
        if (\property_exists($this, $property)) {
            return $this->{$property};
        }
        throw new OutOfBoundsException("Property not defined: {$property}");
    }

    /*public function __toString() : string
    {
        return $this->toScalarJSON($this->toArray());
    }*/

    /**
     * @param string $type get or set
     * @param string $property Property name
     *
     * @return string
     */
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
            $this->setProperty($property, $value);
        }
    }

    protected function setProperty(string $name, mixed $value) : void
    {
        if ( ! \property_exists($this, $name)) {
            $this->{$name} = $value;
            return;
        }
        if ($value !== null) {
            $rp = new ReflectionProperty($this, $name);
            $propertyType = $rp->getType()?->getName();
            if ($propertyType !== null) {
                $value = $this->typeHint($propertyType, $value);
            }
        }
        $this->{$name} = $value;
    }

    protected function typeHint(string $propertyType, mixed $value) : mixed
    {
        $valueType = \get_debug_type($value);
        $newValue = $this->typeHintCustom($propertyType, $valueType, $value);
        if ($newValue === null) {
            $newValue = $this->typeHintNative($propertyType, $valueType, $value);
        }
        if ($newValue === null) {
            $newValue = $this->typeHintAplus($propertyType, $valueType, $value);
        }
        return $newValue ?? $value;
    }

    protected function typeHintCustom(string $propertyType, string $valueType, mixed $value) : mixed
    {
        return null;
    }

    protected function typeHintNative(string $propertyType, string $valueType, mixed $value) : mixed
    {
        if ($propertyType === 'array') {
            return $valueType === 'string'
                ? \json_decode($value, true, flags: \JSON_THROW_ON_ERROR)
                : (array) $value;
        }
        if ($propertyType === 'bool') {
            return (bool) $value;
        }
        if ($propertyType === 'float') {
            return (float) $value;
        }
        if ($propertyType === 'int') {
            return (int) $value;
        }
        if ($propertyType === 'string') {
            return (string) $value;
        }
        if ($propertyType === stdClass::class) {
            return $valueType === 'string'
                ? \json_decode($value, flags: \JSON_THROW_ON_ERROR)
                : (object) $value;
        }
        return null;
    }

    protected function typeHintAplus(string $propertyType, string $valueType, mixed $value) : mixed
    {
        if ($propertyType === Date::class) {
            return new Date((string) $value);
        }
        if ($propertyType === URL::class) {
            return new URL((string) $value);
        }
        return null;
    }

    /**
     * Used in setters where the scalar conversion results in a JSON string.
     *
     * @param array|stdClass|null $value
     *
     * @see toScalar
     *
     * @return stdClass|null
     */
    protected function fromJson($value) : ?stdClass
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof stdClass || \is_array($value)) {
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
    protected function toScalarJson($value) : string
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
     * @throws \InvalidArgumentException if value is not a string or Date
     * @throws \Exception                Emits Exception in case of an error
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
            throw new InvalidArgumentException('Value type must be string or Framework\Date\Date');
        }
        return new Date($value, $this->timezone());
    }

    protected function timezone() : DateTimeZone
    {
        static $timezone;
        return $timezone ?: $timezone = new DateTimeZone('UTC');
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
        // ATOM constant is present on DateTimeInterface. All right.
        return $value->setTimezone($this->timezone())->format(Date::ATOM);
    }

    /**
     * Converts a property value into scalar type or null.
     *
     * If a setter/getter ending with AsScalar exists, (i.e. getConfigAsScalar), it will run to
     * render the proper value.
     *
     * stdClass or array types are converted to a JSON string.
     *
     * DateTime instances are converted to a string in the format Y-m-d H:i:s
     *
     * @param string $property
     *
     * @throws RuntimeException if property was not converted to scalar
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
        if ($this->{$property} instanceof stdClass || \is_array($this->{$property})) {
            return $this->toScalarJson($this->{$property});
        }
        if ($this->{$property} instanceof Date) {
            return $this->toScalarDateTime($this->{$property});
        }
        throw new RuntimeException(
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

    /**
     * @throws JsonException
     */
    public function toModel() : array
    {
        $data = \json_decode(\json_encode($this, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        foreach ($data as &$value) {
            if (\is_array($value)) {
                $value = \json_encode($value, JSON_THROW_ON_ERROR);
            }
        }
        return $data;
    }

    public function jsonSerialize() : array
    {
        if (empty(static::$jsonVars)) {
            return [];
        }
        $allowed = \array_flip(static::$jsonVars);
        $filtered = \array_intersect_key(\get_object_vars($this), $allowed);
        $allowed = \array_intersect_key($allowed, $filtered);
        $ordered = \array_replace($allowed, $filtered);
        return $ordered;
    }
}
