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

use stdClass;

/**
 * Interface ModelInterface.
 *
 * @package mvc
 *
 * @see https://en.wikipedia.org/wiki/Create,_read,_update_and_delete
 */
interface ModelInterface
{
    /**
     * Create a new item.
     *
     * @param Entity|array<string,float|int|string|null>|stdClass $data
     *
     * @return false|int|string The created item id on success or false if it could not
     * be created
     */
    public function create(Entity | array | stdClass $data) : false | int | string;

    /**
     * Read an item based on id.
     *
     * @since 3.6
     *
     * @param int|string $id
     *
     * @return Entity|array<string,float|int|string|null>|stdClass|null The
     * item as array, Entity or stdClass or null if the item was not found
     */
    public function read(int | string $id) : Entity | array | stdClass | null;

    /**
     * Update based on id and return the number of updated items.
     *
     * @param int|string $id
     * @param Entity|array<string,float|int|string|null>|stdClass $data
     *
     * @return false|int|string The number of updated items or false if it could
     * not be updated
     */
    public function update(int | string $id, Entity | array | stdClass $data) : false | int | string;

    /**
     * Delete based on id.
     *
     * @param int|string $id
     *
     * @return false|int|string The number of deleted items or false if it could not be
     * deleted
     */
    public function delete(int | string $id) : false | int | string;
}
