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
		$result = App::getDatabase($connection)
			->select()
			->columns($column ?? $field)
			->from($table)
			->where($column ?? $field, static::getData($field, $data))
			->limit(1)
			->run();
		if ($result) {
			return (bool) $result->numRows();
		}
		return false;
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
