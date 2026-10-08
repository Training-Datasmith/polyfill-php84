<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php84;

use PHPUnit\Framework\TestCase;

/**
 * @requires extension intl
 */
class GraphemeStrSplitTest extends TestCase
{
    /**
     * @dataProvider splitProvider
     */
    public function testSplits(string $string, int $length, array $expected)
    {
        $this->assertIsArray(grapheme_str_split($string, $length));
        $this->assertSame($expected, grapheme_str_split($string, $length));
    }

    public static function splitProvider(): array
    {
        return [
            'empty' => ['', 1, []],
            'ascii length 1' => ['PHP', 1, ['P', 'H', 'P']],
            'ascii length 2' => ['PHP', 2, ['PH', 'P']],
            'cjk' => ['你好', 1, ['你', '好']],
        ];
    }

    public function testDefaultLengthIsOne()
    {
        $this->assertSame(['P', 'H', 'P'], grapheme_str_split('PHP'));
    }

    /**
     * @dataProvider invalidLengthProvider
     */
    public function testInvalidLength(int $length)
    {
        try {
            grapheme_str_split('abc', $length);
            $this->fail('Expected ValueError was not thrown');
        } catch (\ValueError $e) {
            $this->assertStringContainsString('must be greater than 0', $e->getMessage());
            $this->assertStringContainsString('1073741823', $e->getMessage());
            $this->assertStringNotContainsString('1073741823.', $e->getMessage());
        }
    }

    public static function invalidLengthProvider(): array
    {
        return [
            'negative' => [-1],
            'zero' => [0],
            'too large' => [1073741824],
        ];
    }
}
