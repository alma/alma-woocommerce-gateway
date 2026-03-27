# Unique Cart ID Generation

## Problem

The `CartHelper::generateUniqueCartId()` function generates a unique identifier stored as `BIGINT(20) UNSIGNED` in MySQL. The original implementation caused a **fatal `TypeError`** on PHP 8.0+ (especially 32-bit systems):

```
Uncaught TypeError: CartHelper::generateUniqueCartId():
Return value must be of type int, string returned
```

### Root cause

The old code used **string concatenation** to build the ID:

```php
$timestamp = round( microtime( true ) * 1000 ); // float
$random    = mt_rand( 10000, 99999 );            // int
$id        = $timestamp . $random;               // ← always a string!
return $id;                                      // return type: int → TypeError
```

The `.` operator in PHP **always produces a string**, regardless of the operand types. On PHP 8.0+, the engine cannot silently coerce an 18-digit numeric string to `int` when it exceeds `PHP_INT_MAX` on 32-bit platforms (2 147 483 647) — resulting in a `TypeError`.

Additionally, the defensive `strlen()` guard that truncated oversized IDs was **dead code**: a 13-digit timestamp + 5-digit random = 18 digits, never exceeding the 20-digit `BIGINT` limit (would only trigger in ~31 000 years).

## Solution

Replaced string concatenation with **bit-shifting arithmetic**, which always returns a native PHP `int`:

```php
private const RANDOM_BITS = 20;

public static function generateUniqueCartId(): int {
    $timestamp = time();
    $random    = random_int( 0, ( 1 << self::RANDOM_BITS ) - 1 );

    return ( $timestamp << self::RANDOM_BITS ) | $random;
}
```

### How it works

1. **`time()`** — Unix timestamp in seconds (~10 digits, fits in 34 bits)
2. **`<< 20`** — shifts the timestamp left by 20 bits, freeing the lower 20 bits
3. **`random_int(0, 1_048_575)`** — fills the lower 20 bits with a random value
4. **`|`** (bitwise OR) — combines both parts into a single integer

```
Bit layout (64-bit int):
┌──────────────────────────────────┬────────────────────┐
│  Timestamp (upper 44 bits)       │  Random (20 bits)  │
└──────────────────────────────────┴────────────────────┘
```

### Properties

| Property                  | Value                                      |
|---------------------------|--------------------------------------------|
| Result type               | Always `int` (no string coercion)          |
| Approximate digit count   | ~16 digits                                 |
| Chronological sorting     | ✅ Natural — higher bits = timestamp        |
| Uniqueness per second     | 1 048 576 possible values (2²⁰)           |
| PHP_INT_MAX safe (64-bit) | ✅ Max ~1.86 × 10¹⁵ ≪ 9.2 × 10¹⁸          |
| BIGINT(20) UNSIGNED safe  | ✅ Well within the 18.4 × 10¹⁸ limit       |

### Other fixes

- **Removed `RandomException` catch**: `RandomException` only exists in PHP 8.3+. On PHP 7.4–8.2, `random_int()` throws `\Exception`, so the `catch (RandomException $e)` block never triggered and left `$random` undefined. Since `random_int()` practically never fails on modern systems, the try/catch was removed entirely.
- **Replaced `mt_rand()` with `random_int()`**: cryptographically secure and uniform distribution.

## Test coverage

Unit tests in `tests/Unit/Infrastructure/Helper/CartHelperTest.php` cover:

| Test                                | Assertion                                        |
|-------------------------------------|--------------------------------------------------|
| `ReturnsAnInteger`                  | Return type is `int`                             |
| `ReturnsPositiveValue`              | Value is > 0                                     |
| `FitsInBigintUnsigned`              | Does not exceed BIGINT(20) max                   |
| `FitsInPhpIntMax`                   | Does not exceed `PHP_INT_MAX`                    |
| `ContainsTimestampInUpperBits`      | Timestamp extractable via `>> 20`                |
| `RandomPartIsWithinBounds`          | Lower 20 bits in [0, 2²⁰ − 1]                   |
| `ProducesDifferentValues`           | 100 consecutive calls → 100 unique IDs           |
| `IsRoughlyChronological`            | Later ID > earlier ID                            |

