<?php

declare(strict_types=1);

/**
 * Example: Using symfony/polyfill-php84 functions.
 *
 * This polyfill provides PHP 8.4 functions for PHP 8.1, 8.2, and 8.3.
 * On PHP 8.4+, native implementations are used automatically.
 *
 * Install:
 *   composer require symfony/polyfill-php84
 */

// --- mb_ucfirst() / mb_lcfirst(): multibyte first-character case ---
$title = mb_ucfirst('héllo world');
var_dump($title); // string "Héllo world"

$lower = mb_lcfirst('HÉLLO');
var_dump($lower); // string "hÉLLO"

// --- array_find(): find first element matching a predicate ---
$numbers = [1, 3, 5, 8, 9];
$firstEven = array_find($numbers, fn($n) => $n % 2 === 0);
var_dump($firstEven); // int(8)

$notFound = array_find($numbers, fn($n) => $n > 100);
var_dump($notFound); // NULL

// --- array_find_key(): find key of first matching element ---
$users = ['alice' => 25, 'bob' => 17, 'carol' => 30];
$firstAdultKey = array_find_key($users, fn($age) => $age >= 18);
var_dump($firstAdultKey); // string(5) "alice"

// --- array_any(): true if any element matches predicate ---
$hasNegative = array_any([1, -2, 3], fn($n) => $n < 0);
var_dump($hasNegative); // bool(true)

$allPositive = array_any([1, 2, 3], fn($n) => $n < 0);
var_dump($allPositive); // bool(false)

// --- array_all(): true if all elements match predicate ---
$allAdults = array_all($users, fn($age) => $age >= 18);
var_dump($allAdults); // bool(false)  — bob is 17

$allPositive = array_all([1, 2, 3], fn($n) => $n > 0);
var_dump($allPositive); // bool(true)

// --- fpow(): float power (always returns float) ---
$result = fpow(2.0, 10.0);
var_dump($result); // float(1024)

// --- mb_trim() / mb_ltrim() / mb_rtrim(): multibyte-safe whitespace trimming ---
$spaced = "  héllo  ";
var_dump(mb_trim($spaced));  // string "héllo"
var_dump(mb_ltrim($spaced)); // string "héllo  "
var_dump(mb_rtrim($spaced)); // string "  héllo"

// Trim specific characters
var_dump(mb_trim('***héllo***', '*')); // string "héllo"

// --- grapheme_str_split(): split multibyte string into grapheme clusters ---
$emoji = "a😀b";
$clusters = grapheme_str_split($emoji, 1);
var_dump($clusters); // array ["a", "😀", "b"]

// --- bcdivmod(): BC math division with remainder ---
[$quotient, $remainder] = bcdivmod('17', '5');
var_dump($quotient);  // string(1) "3"
var_dump($remainder); // string(1) "2"
