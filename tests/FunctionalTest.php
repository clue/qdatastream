<?php

use Clue\QDataStream\Reader;
use Clue\QDataStream\Writer;

class FunctionalTest extends TestCase
{
    public function testString()
    {
        $in = 'hellö';

        $writer = new Writer();
        $writer->writeString($in);

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals($in, $reader->readString());
    }
    public function testVariantAutoTypes()
    {
        $in = array(
            'hello' => 'world',
            'bool' => true,
            'year' => 2015,
            'list' => array(
                'first',
                'second'
            )
        );

        $writer = new Writer();
        $writer->writeVariant($in);

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals($in, $reader->readVariant());
    }

    public function testShorts()
    {
        $writer = new Writer();
        $writer->writeShort(-100);
        $writer->writeUShort(60000);

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals(-100, $reader->readShort());
        $this->assertEquals(60000, $reader->readUShort());
    }

    public function testChars()
    {
        $writer = new Writer();
        $writer->writeChar(-100);
        $writer->writeUChar(250);

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals(-100, $reader->readChar());
        $this->assertEquals(250, $reader->readUChar());
    }
}
