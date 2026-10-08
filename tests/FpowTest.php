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

class FpowTest extends TestCase
{
    public function testOrdinaryPowers()
    {
        $this->assertSame(1024.0, fpow(2.0, 10.0));
        $this->assertSame(0.5, fpow(2.0, -1.0));
        $this->assertSame(100.0, fpow(10.0, 2.0));
        $this->assertSame(-8.0, fpow(-2.0, 3.0));
        $this->assertSame(1.0, fpow(0.0, 0.0));
        $this->assertSame(1.0, fpow(5.0, 0.0));
    }

    public function testNonFinite()
    {
        $this->assertSame(1.0, fpow(1.0, \INF));
        $this->assertSame(0.0, fpow(2.0, -\INF));
        $this->assertTrue(is_nan(fpow(-1.0, 0.5)));
        $this->assertTrue(is_nan(fpow(\NAN, 2.0)));
        $inf = fpow(2.0, \INF);
        $this->assertTrue(is_infinite($inf));
        $this->assertGreaterThan(0, $inf);
    }

    /**
     * PHP 7.2 already returns IEEE floats without warnings for 0**negative; no polyfill branch required.
     */
    public function testZeroToNegativePowerOnPhp72()
    {
        $errors = [];
        set_error_handler(static function ($errno, $errstr) use (&$errors) {
            $errors[] = $errstr;

            return true;
        });

        try {
            $results = [
                fpow(0.0, -1.0),
                fpow(-0.0, -1.0),
                fpow(-0.0, -2.0),
            ];
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $errors);
        foreach ($results as $result) {
            $this->assertTrue(is_float($result));
            $this->assertTrue(is_infinite($result));
        }
        $this->assertSame('INF', (string) $results[0]);
        $this->assertSame('-INF', (string) $results[1]);
        $this->assertSame('INF', (string) $results[2]);
    }
}
