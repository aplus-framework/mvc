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

use Framework\Date\Date;
use Framework\HTTP\URL;
use JsonException;
use OutOfBoundsException;
use ReflectionProperty;
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
        unset($this->{$property});
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
                ? \json_decode($value, true, flags: $this->jsonOptions())
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
                ? \json_decode($value, flags: $this->jsonOptions())
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

    protected function jsonOptions() : int
    {
        return \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
            | \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR;
    }

    /**
     * Convert the Entity to an associative array accepted by Model methods.
     *
     * @throws JsonException
     */
    public function toModel() : array
    {
        $data = \json_decode(\json_encode($this, $this->jsonOptions()), true, 512, $this->jsonOptions());
        foreach ($data as &$value) {
            if (\is_array($value)) {
                $value = \json_encode($value, $this->jsonOptions());
            }
        }
        return $data;
    }

    public function jsonSerialize() : stdClass
    {
        if (empty(static::$jsonVars)) {
            return new stdClass();
        }
        $allowed = \array_flip(static::$jsonVars);
        $filtered = \array_intersect_key(\get_object_vars($this), $allowed);
        $allowed = \array_intersect_key($allowed, $filtered);
        $ordered = \array_replace($allowed, $filtered);
        return (object) $ordered;
    }
}
