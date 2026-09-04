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

/**
 * Class Entity.
 *
 * @todo In PHP 8.4 add property hooks to validate config properties.
 *
 * @package mvc
 */
abstract class Entity implements \JsonSerializable
{
    /**
     * @param array<string,mixed> $properties
     */
    public function __construct(array $properties)
    {
        $this->populate($properties);
        $this->init();
    }

    /**
     * Used to initialize settings, set custom properties, etc.
     * Called in the constructor just after the properties be populated.
     */
    protected function init() : void
    {
    }

    /**
     * @param array<string,mixed> $properties
     */
    protected function populate(array $properties) : void
    {
        foreach ($properties as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * Convert the Entity to an associative array accepted by Model methods.
     *
     * @return array<string,scalar>
     */
    public function toModel() : array
    {
        return [];
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array<string,mixed>
     */
    public function toJson() : array
    {
        return [];
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize() : array
    {
        return $this->toJson();
    }
}
