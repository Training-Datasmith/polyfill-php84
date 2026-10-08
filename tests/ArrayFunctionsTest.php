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

class ArrayFunctionsTest extends TestCase
{
    public function testArrayFindReturnsFirstMatchAndStops()
    {
        $visited = 0;
        $result = array_find([false, 'keep', 'later'], static function ($value) use (&$visited) {
            ++$visited;
            if ('later' === $value) {
                throw new \RuntimeException('should not reach third element');
            }

            return 'keep' === $value;
        });

        $this->assertSame('keep', $result);
        $this->assertSame(2, $visited);
    }

    public function testArrayFindMissAndEmptyAreNull()
    {
        $callable = static function ($value): bool {
            return \strlen($value) > 2;
        };

        $this->assertNull(array_find([], $callable));
        $this->assertNull(array_find(['a', 'aa'], $callable));
    }

    public function testArrayFindKeyReturnsIntZero()
    {
        $this->assertSame(0, array_find_key(['first', 'second'], static function ($value): bool {
            return 'first' === $value;
        }));
    }

    public function testArrayFindKeyStringKey()
    {
        $array = ['a' => '1', 'b' => '12', 'c' => '123'];
        $this->assertSame('c', array_find_key($array, static function ($value): bool {
            return \strlen($value) > 2;
        }));
    }

    public function testArrayAny()
    {
        $byLength = static function ($value): bool {
            return \strlen($value) > 2;
        };
        $byKey = static function ($value, $key): bool {
            return is_numeric($key);
        };

        $this->assertFalse(array_any([], $byLength));
        $this->assertTrue(array_any(['a', 'aaa'], $byLength));
        $this->assertFalse(array_any(['a', 'aa'], $byLength));
        $this->assertTrue(array_any(['a' => '1', 'b' => '12', 'c' => '123'], $byLength));
        $this->assertTrue(array_any(['a' => '1', 'b' => '12', 'c' => '123', 3 => '1234'], $byKey));
        $this->assertFalse(array_any(['a' => '1', 'b' => '12', 'c' => '123'], $byKey));
    }

    public function testArrayAllVacuousAndStrict()
    {
        $byLength = static function ($value): bool {
            return \strlen($value) > 2;
        };

        $this->assertTrue(array_all([], static function (): bool {
            return false;
        }));
        $this->assertTrue(array_all(['aaa', 'aaa'], $byLength));
        $this->assertFalse(array_all(['aa', 'aaa'], $byLength));
    }

    public function testCallbackExceptionPropagates()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        array_find(['x'], static function (): void {
            throw new \RuntimeException('boom');
        });
    }
}
