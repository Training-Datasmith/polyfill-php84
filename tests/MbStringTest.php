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
 * @requires extension mbstring
 */
class MbStringTest extends TestCase
{
    private $savedEncoding;

    protected function setUp(): void
    {
        $this->savedEncoding = mb_internal_encoding();
    }

    protected function tearDown(): void
    {
        mb_internal_encoding($this->savedEncoding);
    }

    /**
     * @dataProvider ucFirstProvider
     */
    public function testMbUcfirst(string $input, string $expected)
    {
        $this->assertSame($expected, mb_ucfirst($input));
        $this->assertSame($expected, mb_ucfirst($input, 'UTF-8'));
    }

    public static function ucFirstProvider(): array
    {
        return [
            ['', ''],
            ['test', 'Test'],
            ['TEST', 'TEST'],
            ['TesT', 'TesT'],
            ['ａｂ', 'Ａｂ'],
            ['đắt quá!', 'Đắt quá!'],
            ['ǉ', 'ǈ'],
        ];
    }

    /**
     * @dataProvider lcFirstProvider
     */
    public function testMbLcfirst(string $input, string $expected)
    {
        $this->assertSame($expected, mb_lcfirst($input));
    }

    public static function lcFirstProvider(): array
    {
        return [
            ['', ''],
            ['Test', 'test'],
            ['tEST', 'tEST'],
            ['Ａｂ', 'ａｂ'],
            ['Đắt quá!', 'đắt quá!'],
        ];
    }

    /**
     * @requires PHP >= 8
     */
    public function testInvalidEncodingThrowsValueError()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('must be a valid encoding');

        mb_ucfirst('a', 'NULL');
    }

    public function testMbTrimDefaultWhitespace()
    {
        $this->assertSame('ABC', mb_trim("\0\t\nABC \0\t\n"));
        $this->assertSame("ABC \0\t\n", mb_ltrim("\0\t\nABC \0\t\n"));
        $this->assertSame("\0\t\nABC", mb_rtrim("\0\t\nABC \0\t\n"));
        $this->assertSame('あ', mb_trim(" \u{3000}あ\u{00A0} "));
    }

    public function testMbTrimCustomCharacters()
    {
        $this->assertSame('foo BAR Spa', mb_trim('foo BAR Spaß', 'ß', 'UTF-8'));
        $this->assertSame('いうお', mb_trim(' あいうお ', ' あ', 'UTF-8'));
        $this->assertSame('a-b', mb_rtrim('a-b-', '-'));
        $this->assertSame('f', mb_trim('foo', 'oo'));
    }

    public function testEmptyCharacterMaskIsIdentity()
    {
        $samples = [
            [" a\xC3 ", " a\xC3 "],
            [" \xE9 ", " \xE9 ", 'ISO-8859-1'],
        ];

        foreach ($samples as $sample) {
            $encoding = $sample[2] ?? null;
            $this->assertIsString(mb_trim($sample[0], '', $encoding));
            $this->assertSame($sample[1], mb_trim($sample[0], '', $encoding));
            $this->assertSame($sample[1], mb_ltrim($sample[0], '', $encoding));
            $this->assertSame($sample[1], mb_rtrim($sample[0], '', $encoding));
        }
    }

    public function testMbTrimHonorsEncoding()
    {
        $trimmed = mb_trim("\x81\x40\x82\xa0\x81\x40", "\x81\x40", 'SJIS');
        $this->assertSame('あ', mb_convert_encoding($trimmed, 'UTF-8', 'SJIS'));

        mb_internal_encoding('Shift_JIS');
        $strSjis = mb_convert_encoding("\u{3042}\u{3000}", 'Shift_JIS', 'UTF-8');
        $this->assertSame(1, mb_strlen(mb_trim($strSjis)));
    }
}
