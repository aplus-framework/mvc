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

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Framework\Date\Date;
use Framework\HTTP\URL;
use JsonException;
use OutOfBoundsException;
use ReflectionProperty;
use stdClass;

/**
 * Class Entity.
 *
 * @todo In PHP 8.4 add property hooks to validate config properties.
 *
 * @package mvc
 */
abstract class Entity implements \JsonSerializable //, \Stringable
{
    /**
     * Sets the flags that will be used to encode/decode JSON in internal
     * methods of this Entity class.
     */
    public int $_jsonFlags = \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
    | \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR;
    /**
     * Sets the name of the properties that will be visible when this Entity is
     * JSON encoded.
     *
     * @var array<string>
     */
    public array $_jsonVars = [];
    /**
     * This timezone is used to convert times in the {@see Entity::toModel()}
     * method.
     *
     * Note that it must be the same timezone as the database configurations.
     *
     * @see Model::timezone()
     */
    public string $_timezone = '+00:00';

    /**
     * @param array<string,mixed> $properties
     */
    public function __construct(array $properties)
    {
        $this->populate($properties);
        $this->init();
    }

    public function __isset(string $property) : bool
    {
        return isset($this->{$property});
    }

    public function __unset(string $property) : void
    {
        unset($this->{$property});
    }

    /**
     * @param string $property
     * @param mixed $value
     *
     * @throws OutOfBoundsException If property is not defined
     */
    public function __set(string $property, mixed $value) : void
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
        throw $this->propertyNotDefined($property);
    }

    /**
     * @param string $property
     *
     * @throws OutOfBoundsException If property is not defined
     *
     * @return mixed
     */
    public function __get(string $property) : mixed
    {
        $method = $this->renderMethodName('get', $property);
        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }
        if (\property_exists($this, $property)) {
            return $this->{$property};
        }
        throw $this->propertyNotDefined($property);
    }

    protected function propertyNotDefined(string $property) : OutOfBoundsException
    {
        return new OutOfBoundsException('Property not defined: ' . $property);
    }

    /**
     * Used to initialize settings, set custom properties, etc.
     * Called in the constructor just after the properties be populated.
     */
    protected function init() : void
    {
    }

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
            return $type . $properties[$property];
        }
        $name = \ucwords($property, '_');
        $name = \strtr($name, ['_' => '']);
        $properties[$property] = $name;
        return $type . $name;
    }

    /**
     * @param array<string,mixed> $properties
     */
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
        if (!\property_exists($this, $name)) {
            throw $this->propertyNotDefined($name);
        }
        if ($value !== null) {
            $rp = new ReflectionProperty($this, $name);
            $propertyType = $rp->getType()?->getName(); // @phpstan-ignore-line
            if ($propertyType !== null) {
                $value = $this->typeHint($propertyType, $value);
            }
        }
        $this->{$name} = $value;
    }

    /**
     * Tries to convert the value according to the property type.
     *
     * @param string $propertyType
     * @param mixed $value
     *
     * @return mixed
     */
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

    /**
     * Override this method to set customizable property types.
     *
     * @param string $propertyType
     * @param string $valueType
     * @param mixed $value
     *
     * @return mixed
     */
    protected function typeHintCustom(string $propertyType, string $valueType, mixed $value) : mixed
    {
        return null;
    }

    /**
     * Tries to convert the property value to native PHP types.
     *
     * @param string $propertyType
     * @param string $valueType
     * @param mixed $value
     *
     * @return mixed
     */
    protected function typeHintNative(string $propertyType, string $valueType, mixed $value) : mixed
    {
        if ($propertyType === 'array') {
            return $valueType === 'string'
                ? \json_decode($value, true, flags: $this->_jsonFlags)
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
                ? (object) \json_decode($value, flags: $this->_jsonFlags)
                : (object) $value;
        }
        return null;
    }

    /**
     * Tries to convert the property value using Aplus Framework types.
     *
     * @param string $propertyType
     * @param string $valueType
     * @param mixed $value
     *
     * @throws Exception
     *
     * @return mixed
     */
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
     * Convert the Entity to an associative array accepted by Model methods.
     *
     * @throws Exception in case of error creating DateTimeZone
     * @throws JsonException in case of error while encoding/decoding JSON
     *
     * @return array<string,scalar>
     */
    public function toModel() : array
    {
        $jsonVars = $this->_jsonVars;
        $this->_jsonVars = \array_keys($this->getObjectVars());
        // @phpstan-ignore-next-line
        $data = \json_decode(\json_encode($this, $this->_jsonFlags), true, 512, $this->_jsonFlags);
        foreach ($data as $property => &$value) {
            if (\is_array($value)) {
                $value = \json_encode($value, $this->_jsonFlags);
                continue;
            }
            $type = \get_debug_type($this->{$property});
            if (\is_subclass_of($type, DateTimeInterface::class)) {
                $datetime = DateTime::createFromFormat(DateTimeInterface::ATOM, $value);
                // @phpstan-ignore-next-line
                $datetime->setTimezone(new DateTimeZone($this->_timezone));
                $value = $datetime->format('Y-m-d H:i:s'); // @phpstan-ignore-line
            }
        }
        unset($value);
        $this->_jsonVars = $jsonVars;
        return $data;
    }

    public function jsonSerialize() : stdClass
    {
        if (!$this->_jsonVars) {
            return new stdClass();
        }
        $allowed = \array_flip($this->_jsonVars);
        $filtered = \array_intersect_key($this->getObjectVars(), $allowed);
        $allowed = \array_intersect_key($allowed, $filtered);
        $ordered = \array_replace($allowed, $filtered);
        return (object) $ordered;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getObjectVars() : array
    {
        $result = [];
        foreach (\get_object_vars($this) as $key => $value) {
            if (!\str_starts_with($key, '_')) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
