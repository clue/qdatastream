<?php

use Clue\QDataStream\Writer;
use Clue\QDataStream\Types;
use Clue\QDataStream\QVariant;

class WriterTest extends TestCase
{
    public function setUp()
    {
        $this->writer = new Writer(array(
            'year' => function ($data, Writer $writer) {
                $writer->writeUShort($data);
            },
            'user' => function ($data, Writer $writer) {
                $writer->writeShort($data['id']);
                $writer->writeQString($data['name']);
            }
        ));
    }

    public function testBoolTrue()
    {
        $this->writer->writeBool(true);
        $this->assertEquals("\x01", (string)$this->writer);
    }

    public function testBoolTrueAsInteger()
    {
        $this->writer->writeBool(10);
        $this->assertEquals("\x01", (string)$this->writer);
    }

    public function testBoolFalse()
    {
        $this->writer->writeBool(false);
        $this->assertEquals("\x00", (string)$this->writer);
    }

    public function testInteger()
    {
        $this->writer->writeInt(31);
        $this->assertEquals("\x00\x00\x00\x1F", (string)$this->writer);
    }

    public function testQBytesEmpty()
    {
        $this->writer->writeQByteArray('');
        $this->assertEquals("\x00\x00\x00\x00", (string)$this->writer);
    }

    public function testQBytesNull()
    {
        $this->writer->writeQByteArray(null);
        $this->assertEquals("\xFF\xFF\xFF\xFF", (string)$this->writer);
    }

    public function testQBytesTwo()
    {
        $this->writer->writeQByteArray("\xAA\x12");
        $this->assertEquals("\x00\x00\x00\x02" . "\xAA\x12", (string)$this->writer);
    }

    public function testQStringEmpty()
    {
        $this->writer->writeQString('');
        $this->assertEquals("\x00\x00\x00\x00", (string)$this->writer);
    }

    public function testQStringNull()
    {
        $this->writer->writeQString(null);
        $this->assertEquals("\xFF\xFF\xFF\xFF", (string)$this->writer);
    }

    public function testQStringHi()
    {
        $this->writer->writeQString('Hi');
        $this->assertEquals("\x00\x00\x00\x04" . "\x00H\x00i", (string)$this->writer);
    }

    public function testQTimeExactlyMidnightIsNullMilliseconds()
    {
        date_default_timezone_set('UTC');

        $now = gmmktime(0, 0, 0, 9, 19, 2016);

        $this->writer->writeQTime($now);
        $this->assertEquals("\x00\x00\x00\x00", (string)$this->writer);
    }

    public function testQTimeExactlyMidnightIsNullMillisecondsWithTimezone()
    {
        date_default_timezone_set('Europe/Berlin');

        $now = mktime(0, 0, 0, 9, 19, 2016);

        $this->writer->writeQTime($now);
        $this->assertEquals("\x00\x00\x00\x00", (string)$this->writer);
    }

    public function testQTimeExactlyMidnightIsNullMillisecondsFromForeignTimezone()
    {
        date_default_timezone_set('Europe/Berlin');

        $now = new DateTime();
        $now->setTime(0, 0, 0);

        date_default_timezone_set('UTC');

        $this->writer->writeQTime($now);
        $this->assertEquals("\x00\x00\x00\x00", (string)$this->writer);
    }

    public function testQTimeMillisecondsAfterMidnight()
    {
        date_default_timezone_set('UTC');

        $now = gmmktime(0, 0, 0, 9, 19, 2016) + 0.018;

        $this->writer->writeQTime($now);
        $this->assertEquals("\x00\x00\x00\x12", (string)$this->writer);
    }

    public function testQVariantBoolTrue()
    {
        $this->writer->writeQVariant(true);
        $this->assertEquals("\x00\x00\x00\x01\x00" . "\x01", (string)$this->writer);
    }

    public function testQVariantBoolFalse()
    {
        $this->writer->writeQVariant(false);
        $this->assertEquals("\x00\x00\x00\x01\x00" . "\x00", (string)$this->writer);
    }

    public function testQVariantInteger()
    {
        $this->writer->writeQVariant(31);
        $this->assertEquals("\x00\x00\x00\x02\x00" . "\x00\x00\x00\x1F", (string)$this->writer);
    }

    public function testQVariantStringEmpty()
    {
        $this->writer->writeQVariant('');
        $this->assertEquals("\x00\x00\x00\x0A\x00" . "\x00\x00\x00\x00", (string)$this->writer);
    }

    public function testQVariantStringHi()
    {
        $this->writer->writeQVariant('Hi');
        $this->assertEquals("\x00\x00\x00\x0A\x00" . "\x00\x00\x00\x04" . "\x00H\x00i", (string)$this->writer);
    }

    public function testQVariantExplicit()
    {
        $this->writer->writeQVariant(new QVariant(2015, Types::TYPE_USHORT));
        $this->assertEquals("\x00\x00\x00\x85\x00" . "\x07\xDF", (string)$this->writer);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testQVariantNullCanNotBeSerialized()
    {
        $this->writer->writeQVariant(null);
    }

    public function testIntegersConcatenated()
    {
        $this->writer->writeInt(0);
        $this->writer->writeInt(256);
        $this->assertEquals("\x00\x00\x00\x00" . "\x00\x00\x01\x00", (string)$this->writer);
    }

    public function testQVariantIntegersConcatenated()
    {
        $this->writer->writeQVariant(0);
        $this->writer->writeQVariant(256);
        $this->assertEquals("\x00\x00\x00\x02\x00" . "\x00\x00\x00\x00" . "\x00\x00\x00\x02\x00" . "\x00\x00\x01\x00", (string)$this->writer);
    }

    public function testQUserTypeSimple()
    {
        $this->writer->writeQUserTypeByName(2015, 'year');
        $this->assertEquals("\x00\x00\x00\x05" . "year\x00" . "\x07\xDF", (string)$this->writer);
    }

    public function testQVariantUserType()
    {
        $this->writer->writeQVariant(new QVariant(2015, 'year'));
        $this->assertEquals("\x00\x00\x00\x7F" . "\x00" . "\x00\x00\x00\x05" . "year\x00" . "\x07\xDF", (string)$this->writer);
    }

    public function testQUserTypeComplex()
    {
        $user = array(
            'name' => 'test',
            'id' => 10
        );
        $this->writer->writeQUserTypeByName($user, 'user');
        $this->assertEquals("\x00\x00\x00\x05" . "user\x00" . "\x00\x0A" . "\x00\x00\x00\x08" . "\x00t\x00e\x00s\x00t", (string)$this->writer);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testQUserTypeInvalid()
    {
        $this->writer->writeQUserTypeByName(10, 'unknown');
    }
}
