<?php

use Clue\QDataStream\Reader;

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

        $value = $reader->readVariant();

        $this->assertEquals(255, $value);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testUserTypeUnknown()
    {
        $in = "\x00\x00\x00\x7F" . "\x00" . "\x00\x00\x00\x05" . "demo\x00" . "\x00\x00\x00\xFF";

        $reader = Reader::fromString($in);
        $reader->readVariant();
    }
}
