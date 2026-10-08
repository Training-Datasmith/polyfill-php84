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
 * @requires extension bcmath
 */
class BcDivModTest extends TestCase
{
    private $savedScale;

    protected function setUp(): void
    {
        $this->savedScale = \PHP_VERSION_ID >= 70300 ? bcscale() : (int) (ini_get('bcmath.scale') ?: 0);
    }

    protected function tearDown(): void
    {
        if (\PHP_VERSION_ID >= 70300) {
            bcscale($this->savedScale);
        } else {
            ini_set('bcmath.scale', (string) $this->savedScale);
        }
    }

    /**
     * @dataProvider divModProvider
     */
    public function testQuotientAndRemainder(string $num1, string $num2, ?int $scale, array $expected)
    {
        $this->assertSame($expected, bcdivmod($num1, $num2, $scale));
    }

    public static function divModProvider(): array
    {
        return [
            ['1', '1', null, ['1', '0']],
            ['5', '2', null, ['2', '1']],
            ['5', '2', 2, ['2', '1.00']],
            ['7.2', '3', 2, ['2', '1.20']],
            ['-5', '2', 2, ['-2', '-1.00']],
        ];
    }

    public function testUsesBcscaleWhenScaleIsNull()
    {
        if (\PHP_VERSION_ID >= 70300) {
            bcscale(3);
        } else {
            ini_set('bcmath.scale', '3');
        }

        $this->assertSame(['2', '1.000'], bcdivmod('5', '2', null));
    }
}
