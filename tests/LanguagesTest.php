<?php namespace Tests\MVC;

use PHPUnit\Framework\TestCase;

/**
 * Class LanguagesTest.
 *
 * @runTestsInSeparateProcesses
 */
class LanguagesTest extends TestCase
{
	protected $langDir = __DIR__ . '/../src/Languages/';

	protected function getCodes()
	{
		$codes = \array_filter(\glob($this->langDir . '*'), 'is_dir');
		$length = \strlen($this->langDir);
		foreach ($codes as &$dir) {
			$dir = \substr($dir, $length);
		}
		return $codes;
	}

	public function testKeys()
	{
		$rules = ['inDatabase', 'notInDatabase'];
		foreach ($this->getCodes() as $code) {
			$lines = require $this->langDir . $code . '/validation.php';
			$lines = \array_keys($lines);
			\sort($lines);
			$this->assertEquals($rules, $lines, 'Language: ' . $code);
		}
	}
}
