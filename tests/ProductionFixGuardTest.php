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
 * Each test fails if its corresponding production fix (4.1 or 4.3) is reverted.
 */
class ProductionFixGuardTest extends TestCase
{
    /**
     * Guard for fix 4.1: length 0 must throw grapheme ValueError, not array_chunk's message.
     *
     * @requires extension intl
     */
    public function testGuardGraphemeRejectsZeroLength()
    {
        try {
            grapheme_str_split('ab', 0);
            $this->fail('Expected ValueError for length 0');
        } catch (\ValueError $e) {
            $this->assertStringContainsString('grapheme_str_split()', $e->getMessage());
            $this->assertStringContainsString('greater than 0', $e->getMessage());
            $this->assertStringNotContainsString('array_chunk', $e->getMessage());
        }
    }

    /**
     * Guard for fix 4.1: ValueError class must exist on PHP 7 when the stub is shipped.
     *
     * @requires PHP < 8
     */
    public function testGuardValueErrorStubLoaded()
    {
        $this->assertTrue(class_exists(\ValueError::class, false) || class_exists(\ValueError::class));
    }

    /**
     * Guard for fix 4.3: empty trim mask must not re-encode the string.
     *
     * @requires extension mbstring
     */
    public function testGuardEmptyTrimMaskDoesNotReencode()
    {
        $input = " a\xC3 ";
        $this->assertSame($input, mb_trim($input, ''));
    }
}
