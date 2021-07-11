<?php declare(strict_types=1);
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\MVC;

class Validator extends \Framework\Validation\Validator
{
    public static function inDatabase(
        string $field,
        array $data,
        string $table,
        string $column = null,
        string $connection = 'default'
    ) : bool {
        $result = App::database($connection)
            ->select()
            ->columns($column ?? $field)
            ->from($table)
            ->whereEqual($column ?? $field, static::getData($field, $data))
            ->limit(1)
            ->run();
        return (bool) $result->numRows();
    }

    public static function notInDatabase(
        string $field,
        array $data,
        string $table,
        string $column = null,
        string $connection = 'default'
    ) : bool {
        return ! static::inDatabase($field, $data, $table, $column, $connection);
    }
}
