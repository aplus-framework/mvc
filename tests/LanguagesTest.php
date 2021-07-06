<?php
/*
 * This file is part of The Framework MVC Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\MVC;

use PHPUnit\Framework\TestCase;

/**
 * Class LanguagesTest.
 *
 * @runTestsInSeparateProcesses
 */
final class LanguagesTest extends TestCase
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

	/**
	 * @dataProvider languageProvider
	 */
	public function testKeys(array $rules, string $file) : void
	{
		foreach ($this->getCodes() as $code) {
			$lines = require $this->langDir . $code . '/' . $file . '.php';
			$lines = \array_keys($lines);
			\sort($lines);
			self::assertSame($rules, $lines, 'File: ' . $file . '. Language: ' . $code);
		}
	}

	public function languageProvider() : array
	{
		$files = [
			'errors',
			'validation',
		];
		$data = [];
		foreach ($files as $file) {
			$data[$file] = [
				\array_keys(require $this->langDir . 'en/' . $file . '.php'),
				$file,
			];
		}
		return $data;
	}
}
