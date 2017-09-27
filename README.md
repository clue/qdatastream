# clue/qdatastream [![Build Status](https://travis-ci.org/clue/php-qdatastream.svg?branch=master)](https://travis-ci.org/clue/php-qdatastream)

> Note: This project is in beta stage! Feel free to report any issues you encounter.

## Usage

### Writer

The `Writer` class can be used to build a buffer that data can be written to
and eventually be accessed as a binary string.

```php
$writer = new Writer();
$writer->writeUInt($user->id);
$writer->writeBool($user->active);
$writer->writeQString($user->name);

$data = (string)$writer
file_put_contents('user.dat', $data);
```

See the [class outline](src/Writer.php) for more details.

### Reader

The `Reader` class can be used to read data from a binary buffer string.

```php
$data = file_get_contents('user.dat');
$reader = new Reader($data);

$user = new stdClass();
$user->id = $reader->readUInt();
$user->active = $reader->readBool();
$user->name = $reader->readQString();
```

See the [class outline](src/Reader.php) for more details.

### QVariant

The `QVariant` class can be used to encapsulate any kind of data with an
explicit data type. When writing a `QVariant` to a buffer, it will also
include its data type so that reading it back in can be done automatically
without having to know the data type in advance.

```php
$variant = new QVariant(100, Types::TYPE_USHORT);

$writer = new Writer();
$writer->writeQVariant($variant);

$data = (string)$writer;
$reader = new Reader($data);
$value = $reader->readQVariant();

assert($value === 100);
```

See the [class outline](src/QVariant.php) for more details.

### Types

The `Types` class exists to work with different data types and offers a number
of public constants to work with explicit data types and is otherwise mostly
used internally only.

See the [class outline](src/Types.php) for more details.

## Install

The recommended way to install this library is [through Composer](http://getcomposer.org).
[New to Composer?](http://getcomposer.org/doc/00-intro.md)

This will install the latest supported version:

```bash
$ composer require clue/qdatastream:^0.6
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

## License

MIT
