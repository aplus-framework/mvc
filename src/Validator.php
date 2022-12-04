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

use LogicException;

/**
 * Class Validator.
 *
 * @package mvc
 */
class Validator extends \Framework\Validation\Validator
{
    /**
     * Validates database table not unique value.
     *
     * @param string $field
     * @param array<string,mixed> $data
     * @param string $tableColumn
     * @param string $ignoreColumn
     * @param int|string $ignoreValue
     * @param string $connection
     *
     * @return bool
     */
    public static function notUnique(
        string $field,
        array $data,
        string $tableColumn,
        string $ignoreColumn = '',
        int | string $ignoreValue = '',
        string $connection = 'default'
    ) : bool {
        return ! static::unique(
            $field,
            $data,
            $tableColumn,
            $ignoreColumn,
            $ignoreValue,
            $connection
        );
    }

    /**
     * Validates database table unique value.
     *
     * You can ignore rows where a column has a certain value.
     * Useful when updating a row in the database.
     *
     * @param string $field
     * @param array<string,mixed> $data
     * @param string $tableColumn
     * @param string $ignoreColumn
     * @param int|string $ignoreValue
     * @param string $connection
     *
     * @return bool
     */
    public static function unique(
        string $field,
        array $data,
        string $tableColumn,
        string $ignoreColumn = '',
        int | string $ignoreValue = '',
        string $connection = 'default'
    ) : bool {
        $value = static::getData($field, $data);
        if ($value === null) {
            return false;
        }
        $ignoreValue = (string) $ignoreValue;
        [$table, $column] = \array_pad(\explode('.', $tableColumn, 2), 2, '');
        if ($column === '') {
            $column = $field;
        }
        if ($connection === '') {
            throw new LogicException(
                'The connection parameter must be set to be able to connect the database'
            );
        }
        $statement = App::database($connection)
            ->select()
            ->expressions(['count' => static fn () => 'COUNT(*)'])
            ->from($table)
            ->whereEqual($column, $value);
        if ($ignoreColumn !== '' && ! \preg_match('#^{(\w+)}$#', $ignoreValue)) {
            $statement->whereNotEqual($ignoreColumn, $ignoreValue);
        }
        return $statement->limit(1)->run()->fetch()->count < 1; // @phpstan-ignore-line
    }

    /**
     * Validates value exists in database table.
     *
     * @since 3.3
     *
     * @param string $field
     * @param array<string,mixed> $data
     * @param string $tableColumn
     * @param string $connection
     *
     * @return bool
     */
    public static function exist(
        string $field,
        array $data,
        string $tableColumn,
        string $connection = 'default'
    ) : bool {
        $value = static::getData($field, $data);
        if ($value === null) {
            return false;
        }
        [$table, $column] = \array_pad(\explode('.', $tableColumn, 2), 2, '');
        if ($column === '') {
            $column = $field;
        }
        if ($connection === '') {
            throw new LogicException(
                'The connection parameter must be set to be able to connect the database'
            );
        }
        return App::database($connection) // @phpstan-ignore-line
            ->select()
            ->expressions(['count' => static fn () => 'COUNT(*)'])
            ->from($table)
            ->whereEqual($column, $value)
            ->limit(1)
            ->run()
            ->fetch()->count > 0;
    }
}
