# Changelog

## 0.4.0 (2014-05-11)

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
