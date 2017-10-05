# Changelog

## 0.7.1 (2017-10-05)

*   Add benchmarking example and significantly improve parsing performance
    (#26 by @clue)

    For example, the included benchmark should now run ~40 times faster!
    Parsing larger values (such as long arrays) is now significantly faster and
    scales approximaly linear with `O(n)`, while the old version scaled with
    `O(n^2)`. This is more obvious for larger arrays with thousands of entries
    and less so for smaller ones.

## 0.7.0 (2017-09-28)

*   Feature / BC break: Simplify `Reader` and `Writer` and remove dependency
    on Iodophor and simplify `Types` to be static only
    (#22 and #23 by @clue)

    The BC break mostly affects how the `Reader` and `Writer` now directly
    work on a buffer string instead of accepting a `Types` parameter and
    exposing Iodophor as a dependency.

    ```php
    // old
    $reader = Reader::fromString($buffer, $types, $map);
    $writer = new Writer($writer, $types, $map);

    // new
    $reader = new Reader($buffer, $map);
    $writer = new Writer($map);
    ```

*   Feature: Suggest installing `ext-mbstring` and use lossy conversion for `QString` and `QChar` otherwise
    (#25 by @clue)

    The `QString` and `QChar` types use the `ext-mbstring` for converting
    between different character encodings. If this extension is missing, then
    special characters outside of ASCII/ISO5589-1 range will be replaced with a
    `?` placeholder. This means that the string `hällo € 10!` will be
    converted to `hällo ? 10!` instead. Installing `ext-mbstring` is highly
    recommended.

*   BC break: Remove undocumented `writeType()`
    (#24 by @clue)

*   Improve test suite by adding PHPUnit to require-dev and fix Travis build config
    (#21 by @clue)

## 0.6.0 (2016-09-24)

*   Feature / BC break: QTime and QDateTime are relative to default time zone and obey given time zone
    (#18 by @clue)

    ```php
    date_default_timezone_set('GMT');
    $date = $reader->readQDateTime();
    assert($date->getTimeZone()->getName() === 'GMT');
    ```

*   Feature: Support millisecond accuracy for QTime and QDateTime
    (#20 by @clue)

*   Feature: Support QTime objects not within the current day
    (#17 by @clue)

*   Update class name references and improve test suite
    (#16 by @clue)

## 0.5.0 (2015-05-14)

*   BC break: Use QVariant class to encode all custom type handling instead
    of passing explicit type constants to
    ([#14](https://github.com/clue/php-qdatastream/pull/14))
    
    ```php
    // unchanged: automatic type guessing
    $writer->writeQVariant(10);

    // old: explicit types via type arguments
    $writer->writeQVariant(10, Types::TYPE_QCHAR);

    // new: explicit types via QVariant
    $writer->writeQVariant(new QVariant(10, Types::TYPE_QCHAR));
    ```

*   Feature: Support writing nested QVariantList/QVariantMap objects with
    explicit types.
    ([#14](https://github.com/clue/php-qdatastream/pull/14))

*   Feature: Support reading into QVariant objects in order to get access to the
    variant value plus the type encoding.
    ([#14](https://github.com/clue/php-qdatastream/pull/14))

## 0.4.0 (2015-05-11)

*   BC break: Prefix all Qt types with "Q" to be more in line with QDataStream
    ([#12](https://github.com/clue/php-qdatastream/pull/12))

*   Feature: Add QChar type (multibyte character)
    ([#13](https://github.com/clue/php-qdatastream/pull/13))

## 0.3.0 (2015-05-10)

*   BC break: Merge `writeVariantType()` into `writeVariant()`
    ([#9](https://github.com/clue/php-qdatastream/pull/9))

*   Feature: Support passing explicit types to variant list and map
    ([#10](https://github.com/clue/php-qdatastream/pull/10))

*   Feature: Support custom TYPE_USER_TYPE for writing
    ([#11](https://github.com/clue/php-qdatastream/pull/11))

## 0.2.0 (2015-05-01)

*   BC break: Remove size postfix from integer types
    ([#3](https://github.com/clue/php-qdatastream/pull/3))

*   Feature: Support custom TYPE_USER_TYPE for reading
    ([#4](https://github.com/clue/php-qdatastream/pull/4))

*   Feature: Improve explicit/custom type mapping
    ([#2](https://github.com/clue/php-qdatastream/pull/2))

*   Feature: Support Time and DateTime
    ([#7](https://github.com/clue/php-qdatastream/pull/7))

*   Feature: Support char and uchar
    ([#5](https://github.com/clue/php-qdatastream/pull/5))

*   Feature: Support short and ushort
    ([#1](https://github.com/clue/php-qdatastream/pull/1))

*   Fix: Fix writing StringList
    ([#6](https://github.com/clue/php-qdatastream/pull/6))

## 0.1.0 (2015-04-28)

*   First tagged release
