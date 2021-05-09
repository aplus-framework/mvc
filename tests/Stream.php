<?php namespace Tests\MVC;

class Stream extends \php_user_filter
{
	public static string $output = '';

	public function filter($in, $out, &$consumed, $closing)
	{
		while ($bucket = \stream_bucket_make_writeable($in)) {
			static::$output .= $bucket->data;
			$consumed += $bucket->datalen;
			\stream_bucket_append($out, $bucket);
		}
		return \PSFS_FEED_ME;
	}

	public static function init()
	{
		\stream_filter_register('stream', __CLASS__);
		\stream_filter_append(\STDOUT, 'stream');
	}

	public static function reset()
	{
		static::$output = '';
	}
}
