<?php
/*
 * This file is part of Aplus Framework MVC Library.
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
    protected string $langDir = __DIR__ . '/../src/Languages/';

    /**
     * @return array<int,string>
     */
    protected function getCodes() : array
    {
        // @phpstan-ignore-next-line
        $codes = \array_filter((array) \glob($this->langDir . '*'), 'is_dir');
        $length = \strlen($this->langDir);
        $result = [];
        foreach ($codes as &$dir) {
            if ($dir === false) {
                continue;
            }
            $result[] = \substr($dir, $length);
        }
        return $result;
    }

    /**
     * @param array<int,string> $rules
     * @param string $file
     *
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

    /**
     * @return array<string,array<mixed>>
     */
    public function languageProvider() : array
    {
        $files = [
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
