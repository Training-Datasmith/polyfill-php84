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
 * @requires PHP >= 8.1
 */
class DeprecatedTest extends TestCase
{
    public function testConstructAndTargets()
    {
        $attr = new \Deprecated('do not use', '8.4');
        $this->assertSame('do not use', $attr->message);
        $this->assertSame('8.4', $attr->since);

        $empty = new \Deprecated();
        $this->assertNull($empty->message);
        $this->assertNull($empty->since);

        $classRef = new \ReflectionClass(\Deprecated::class);
        $meta = $classRef->getAttributes()[0];
        $this->assertSame(
            \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION | \Attribute::TARGET_CLASS_CONSTANT,
            $meta->getArguments()[0]
        );

        $methodRef = new \ReflectionMethod(DeprecatedAttributeHolder::class, 'method');
        $applied = $methodRef->getAttributes(\Deprecated::class)[0]->newInstance();
        $this->assertSame('gone', $applied->message);
        $this->assertSame('8.3', $applied->since);
    }

    public function testPropertiesAreReadonly()
    {
        $attr = new \Deprecated('x', '1.0');

        try {
            $attr->message = 'y';
            $this->fail('Expected Error when assigning to readonly property');
        } catch (\Error $e) {
            $this->assertStringContainsString('message', $e->getMessage());
        }
    }
}

class DeprecatedAttributeHolder
{
    #[\Deprecated('gone', '8.3')]
    public function method(): void
    {
    }
}
