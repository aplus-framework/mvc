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

use JsonSerializable;

/**
 * Class Entity.
 *
 * @package mvc
 */
abstract class Entity implements JsonSerializable
{
    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->populate($data);
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
     * @param array<string,mixed> $data
     */
    protected function populate(array $data) : void
    {
        foreach ($data as $name => $value) {
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
