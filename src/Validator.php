<?php namespace Framework\MVC;

class Validator extends \Framework\Validation\Validator
{
	public static function inDatabase(
		string $field,
		array $data,
		string $table,
		string $column = null,
		string $connection = 'default'
	) : bool {
		App::language()->addDirectory(__DIR__ . '/Languages');
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
