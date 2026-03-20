# Architecture: polyfill-php84

## Purpose

Backports PHP 8.4 functions, constants, attributes, and class stubs to PHP 8.1, 8.2,
and 8.3. Enables libraries to target PHP 8.4 features while remaining installable on
older PHP 8.x versions.

## Directory Structure

```
Php84.php        # Pure-PHP implementations of new PHP 8.4 functions as static methods
bootstrap.php    # Defines global functions/constants from PHP 8.4 if running on PHP < 8.4
bootstrap82.php  # Variant for PHP 8.2+: skips features native to 8.2/8.3
Resources/
  stubs/
    Deprecated.php          # Stub for the #[Deprecated] attribute (new in PHP 8.4)
    ReflectionConstant.php  # Stub for the ReflectionConstant class (new in PHP 8.4)
```

## Key Design Decisions

The `#[Deprecated]` attribute stub is particularly valuable: it allows code to declare
deprecated elements using the new native attribute syntax, while running on PHP < 8.4
where the attribute class does not exist natively.

## Extension Points

None — drop-in function and class polyfill.
