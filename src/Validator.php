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
 * Class Validator.
 *
 * @package mvc
 */
class Validator extends \Framework\Validation\Validator
{
    public static function notUnique(
        string $field,
        array $data,
        string $table,
        string $column = null,
        string $connection = 'default'
    ) : bool {
        $value = static::getData($field, $data);
        if ($value === null) {
            return false;
        }
        if ($column === null || $column === '') {
            $column = $field;
        }
        $result = App::database($connection)
            ->select()
            ->columns($column)
            ->from($table)
            ->whereEqual($column, $value)
            ->limit(1)
            ->run();
        return (bool) $result->numRows();
    }

    public static function unique(
        string $field,
        array $data,
        string $table,
        string $column = null,
        string $connection = 'default'
    ) : bool {
        return ! static::notUnique($field, $data, $table, $column, $connection);
    }
}
