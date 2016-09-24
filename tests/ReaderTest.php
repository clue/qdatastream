<?php

use Clue\QDataStream\Reader;
use Clue\QDataStream\QVariant;

class ReaderTest extends TestCase
{
    public function testUserTypeMapping()
    {
        $in = "\x00\x00\x00\x7F" . "\x00" . "\x00\x00\x00\x05" . "demo\x00" . "\x00\x00\x00\xFF";

        $map = array(
            'demo' => function (Reader $reader) {
                return $reader->readUInt();
            }
        );

        $reader = Reader::fromString($in, null, $map);

        $value = $reader->readQVariant();

        $this->assertEquals(255, $value);

        return Reader::fromString($in, null, $map);
    }

    public function testReadNullQTimeIsExactlyMidnight()
    {
        date_default_timezone_set('UTC');

        $midnight = new DateTime('midnight');

        $in = "\x00\x00\x00\x00";
        $reader = Reader::fromString($in);

        $value = $reader->readQTime();

        $this->assertEquals($midnight, $value);
    }

    public function testReadNullQTimeIsExactlyMidnightWithCorrectTimezone()
    {
        date_default_timezone_set('Europe/Berlin');

        $midnight = new DateTime('midnight');

        $in = "\x00\x00\x00\x00";
        $reader = Reader::fromString($in);

        $value = $reader->readQTime();

        $this->assertEquals($midnight, $value);

        $this->assertEquals('Europe/Berlin', $value->getTimezone()->getName());
    }

    /**
     * @depends testUserTypeMapping
     * @param Reader $reader
     */
    public function testUserTypeMappingAsVariant(Reader $reader)
    {
        $this->assertEquals(new QVariant(255, 'demo'), $reader->readQVariant(false));
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testQUserTypeUnknown()
    {
        $in = "\x00\x00\x00\x7F" . "\x00" . "\x00\x00\x00\x05" . "demo\x00" . "\x00\x00\x00\xFF";

        $reader = Reader::fromString($in);
        $reader->readQVariant();
    }
}
