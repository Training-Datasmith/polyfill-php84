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

const REFLECTION_SAMPLE = 'sample-value';

\define('POLYFILL84_USER_INT', 123);
\define('POLYFILL84_NULL', null);

class ExampleNonStringable
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}

class ExampleStringable extends ExampleNonStringable
{
    public function __toString(): string
    {
        return 'ExampleStringable';
    }
}

class ReflectionConstantTest extends TestCase
{
    public function testMissingConstant()
    {
        $this->expectException(\ReflectionException::class);
        $this->expectExceptionMessage('does not exist');
        new \ReflectionConstant('POLYFILL84_NO_SUCH_CONSTANT');
    }

    public function testClassConstantNameRejected()
    {
        $this->expectException(\ReflectionException::class);
        new \ReflectionConstant('DateTime::ATOM');
    }

    public function testBuiltinInt()
    {
        $constant = new \ReflectionConstant('E_ERROR');
        $this->assertSame('E_ERROR', $constant->getName());
        $this->assertSame('', $constant->getNamespaceName());
        $this->assertSame('E_ERROR', $constant->getShortName());
        $this->assertSame(\E_ERROR, $constant->getValue());
        $this->assertFalse($constant->isDeprecated());
        $this->assertStringContainsString('int E_ERROR', (string) $constant);
        $this->assertStringContainsString('{ 1 }', (string) $constant);
    }

    public function testUserIntAndNull()
    {
        $intConstant = new \ReflectionConstant('POLYFILL84_USER_INT');
        $this->assertStringContainsString('int POLYFILL84_USER_INT', (string) $intConstant);
        $this->assertStringContainsString('{ 123 }', (string) $intConstant);
        $this->assertStringNotContainsString('persistent', (string) $intConstant);

        $nullConstant = new \ReflectionConstant('POLYFILL84_NULL');
        $this->assertStringContainsString('null POLYFILL84_NULL', (string) $nullConstant);
    }

    public function testNamespacedConstant()
    {
        $constant = new \ReflectionConstant(__NAMESPACE__.'\\REFLECTION_SAMPLE');
        $this->assertSame(__NAMESPACE__, $constant->getNamespaceName());
        $this->assertSame('REFLECTION_SAMPLE', $constant->getShortName());
        $this->assertSame('sample-value', $constant->getValue());
    }

    public function testDeprecatedBuiltin()
    {
        $constant = new \ReflectionConstant('MT_RAND_PHP');
        $this->assertSame(1, $constant->getValue());

        if (\PHP_VERSION_ID >= 80300) {
            $this->assertTrue($constant->isDeprecated());
            $this->assertStringContainsString('deprecated', (string) $constant);
        } else {
            $this->assertFalse($constant->isDeprecated());
            $this->assertStringNotContainsString('deprecated', (string) $constant);
        }
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testStringableAndNonStringableObjectConstants()
    {
        $unique = 'POLYFILL84_STRINGABLE_'.uniqid('', true);
        \define($unique, new ExampleStringable('x'));
        $stringable = new \ReflectionConstant($unique);
        $text = (string) $stringable;
        $this->assertStringContainsString('ExampleStringable', $text);
        $this->assertStringContainsString($unique, $text);

        $unique2 = 'POLYFILL84_NONSTRING_'.uniqid('', true);
        \define($unique2, new ExampleNonStringable('x'));
        $nonStringable = new \ReflectionConstant($unique2);

        try {
            (string) $nonStringable;
            $this->fail('Expected Error');
        } catch (\Error $e) {
            $this->assertStringContainsString('could not be converted to string', $e->getMessage());
        }
    }

    public function testSerializeRejected()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Serialization of 'ReflectionConstant' is not allowed");
        serialize(new \ReflectionConstant('PHP_VERSION'));
    }

    public function testUserConstantAfterBuiltinCacheIsNotMarkedPersistent()
    {
        (string) new \ReflectionConstant('E_ERROR');

        $name = 'POLYFILL84_UNIQUE_'.uniqid('', true);
        \define($name, 'value');
        $text = (string) new \ReflectionConstant($name);
        $this->assertStringNotContainsString('persistent', $text);
    }
}
