<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Aplus\Mvc;

use JsonSerializable;

/**
 * Class Entity.
 *
 * @package mvc
 */
abstract class Entity implements JsonSerializable
{
    /**
     * Populate the properties with data and run the init method.
     *
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->populate($data);
        $this->init();
    }

    /**
     * Populate the properties with data.
     *
     * @param array<string,mixed> $data
     */
    protected function populate(array $data) : void
    {
        foreach ($data as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * Used to initialize settings, set custom properties, etc.
     * Called in the constructor just after the properties be populated.
     */
    protected function init() : void
    {
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
     * Method used to serialize JSON with json_encode.
     *
     * @see https://www.php.net/manual/en/class.jsonserializable.php
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize() : array
    {
        return $this->toJson();
    }
}
