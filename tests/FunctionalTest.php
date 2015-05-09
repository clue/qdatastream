<?php

use Clue\QDataStream\Reader;
use Clue\QDataStream\Writer;
use Clue\QDataStream\Types;

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

    public function testStringNull()
    {
        $writer = new Writer();
        $writer->writeString(null);

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals(null, $reader->readString());
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

    public function testVariantExplicitCharType()
    {
        $in = 100;

        $writer = new Writer();
        $writer->writeVariant($in, Types::TYPE_CHAR);

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals($in, $reader->readVariant());
    }

    public function testVariantListSomeExplicit()
    {
        $in = array(
            -10,
            20,
            -300
        );

        $writer = new Writer();
        $writer->writeVariantList($in, array(0 => Types::TYPE_CHAR));

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals($in, $reader->readVariantList());
    }

    public function testVariantMapSomeExplicit()
    {
        $in = array(
            'id' => 62000,
            'name' => 'test'
        );

        $writer = new Writer();
        $writer->writeVariantMap($in, array('id' => Types::TYPE_USHORT));

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals($in, $reader->readVariantMap());
    }

    public function testUserType()
    {
        $in = array(
            'id' => 62000,
            'name' => 'test'
        );

        $writer = new Writer(null, null, array(
            'user' => function ($data, Writer $writer) {
                $writer->writeUShort($data['id']);
                $writer->writeString($data['name']);
            }
        ));
        $writer->writeVariant($in, 'user');

        $data = (string)$writer;
        $reader = Reader::fromString($data, null, array(
            'user' => function (Reader $reader) {
                return array(
                    'id' => $reader->readUShort(),
                    'name' => $reader->readString()
                );
            }
        ));

        $this->assertEquals($in, $reader->readVariant());
    }

    public function testStringList()
    {
        $writer = new Writer();
        $writer->writeStringList(array('hello', 'world'));

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals(array('hello', 'world'), $reader->readStringList());
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

    public function testReadTime()
    {
        $now = new \DateTime();

        $writer = new Writer();
        $writer->writeTime($now);

        $in = (string)$writer;
        $reader = Reader::fromString($in);

        $dt = $reader->readTime();
        $this->assertEquals($now, $dt);
    }

    public function testReadTimeSubSecond()
    {
        $this->markTestIncomplete('Sub-second accuracy not implemented');

        $time = '2015-05-01 16:02:03.413705';
        $now = new \DateTime($time);

        $writer = new Writer();
        $writer->writeTime($now);

        $in = (string)$writer;
        $reader = Reader::fromString($in);

        $dt = $reader->readTime();
        $this->assertEquals($now->format('U.u'), $dt->format('U.u'));
    }

    public function testReadTimeMicrotime()
    {
        $this->markTestIncomplete('Sub-second accuracy not implemented');

        $now = microtime(true);

        $writer = new Writer();
        $writer->writeTime($now);

        $in = (string)$writer;
        $reader = Reader::fromString($in);

        $dt = $reader->readTime();
        $this->assertEquals($now, $dt->format('U.u'));
    }

    public function testReadDateTime()
    {
        $writer = new Writer();
        $writer->writeUInt(2457136); // day 2457136 - 2015-04-23
        $writer->writeUInt(50523000); // msec 50523000 - 14:02:03 UTC
        $writer->writeBool(true);

        $in = (string)$writer;
        $reader = Reader::fromString($in);

        $dt = $reader->readDateTime();
        $this->assertEquals('2015-04-23 14:02:03', $dt->format('Y-m-d H:i:s'));

        $writer = new Writer();
        $writer->writeDateTime($dt);

        $out = (string)$writer;

        $this->assertEquals($in, $out);
    }

    public function testReadDateTimeNull()
    {
        $writer = new Writer();
        $writer->writeUInt(0);
        $writer->writeUInt(0xFFFFFFFF);
        $writer->writeBool(true);

        $in = (string)$writer;
        $reader = Reader::fromString($in);

        $dt = $reader->readDateTime();
        $this->assertNull($dt);
    }
}
