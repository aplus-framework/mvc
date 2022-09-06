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
    protected int $_jsonOptions = \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
    | \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR;
    /**
     * @var array<string>
     */
    protected array $_jsonVars = [];
    protected string $_timezone = '+00:00';

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
        if ( ! \property_exists($this, $name)) {
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
                ? (object) \json_decode($value, flags: $this->jsonOptions())
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
        return $this->_jsonOptions;
    }

    protected function timezone() : DateTimeZone
    {
        return new DateTimeZone($this->_timezone);
    }

    /**
     * Convert the Entity to an associative array accepted by Model methods.
     *
     * @throws JsonException
     *
     * @return array<string,scalar>
     */
    public function toModel() : array
    {
        $jsonVars = $this->getJsonVars();
        $this->setJsonVars(\array_keys($this->getObjectVars()));
        // @phpstan-ignore-next-line
        $data = \json_decode(\json_encode($this, $this->jsonOptions()), true, 512, $this->jsonOptions());
        foreach ($data as $property => &$value) {
            if (\is_array($value)) {
                $value = \json_encode($value, $this->jsonOptions());
                continue;
            }
            $type = \get_debug_type($this->{$property});
            if (\is_subclass_of($type, DateTimeInterface::class)) {
                $datetime = DateTime::createFromFormat(DateTimeInterface::ATOM, $value);
                $datetime->setTimezone($this->timezone()); // @phpstan-ignore-line
                $value = $datetime->format('Y-m-d H:i:s'); // @phpstan-ignore-line
            }
        }
        unset($value);
        $this->setJsonVars($jsonVars);
        return $data;
    }

    public function jsonSerialize() : stdClass
    {
        if ( ! $this->getJsonVars()) {
            return new stdClass();
        }
        $allowed = \array_flip($this->getJsonVars());
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
            if ( ! \str_starts_with($key, '_')) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * @return array<string>
     */
    public function getJsonVars() : array
    {
        return $this->_jsonVars;
    }

    /**
     * @param array<string> $vars
     */
    public function setJsonVars(array $vars) : static
    {
        $this->_jsonVars = $vars;
        return $this;
    }
}
