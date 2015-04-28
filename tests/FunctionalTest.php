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
}
