<?php

use Clue\QDataStream\Reader;
use Clue\QDataStream\Writer;
use Clue\QDataStream\Types;
use Clue\QDataStream\QVariant;

class FunctionalTest extends TestCase
{
    public function testQString()
    {
        $in = 'hellö';

        $writer = new Writer();
        $writer->writeQString($in);

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals($in, $reader->readQString());
    }

    public function testQStringNull()
    {
        $writer = new Writer();
        $writer->writeQString(null);

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals(null, $reader->readQString());
    }

    public function testQVariantAutoTypes()
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
        $writer->writeQVariant($in);

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals($in, $reader->readQVariant());
    }

    public function testQVariantExplicitCharType()
    {
        $in = 100;

        $writer = new Writer();
        $writer->writeQVariant(new QVariant($in, Types::TYPE_CHAR));

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals($in, $reader->readQVariant());
    }

    public function testQVariantListSomeExplicit()
    {
        $in = array(
            new QVariant(-10, Types::TYPE_CHAR),
            20,
            -300
        );
        $expected = array(
            -10,
            20,
            -300
        );

        $writer = new Writer();
        $writer->writeQVariantList($in);

        $data = (string)$writer;

        $reader = Reader::fromString($data);
        $this->assertEquals($expected, $reader->readQVariantList());
    }

    public function testQVariantMapSomeExplicit()
    {
        $in = array(
            'id' => new QVariant(62000, Types::TYPE_USHORT),
            'name' => 'test'
        );
        $expected = array(
            'id' => 62000,
            'name' => 'test'
        );

        $writer = new Writer();
        $writer->writeQVariantMap($in);

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals($expected, $reader->readQVariantMap());
    }

    public function testQUserType()
    {
        $in = array(
            'id' => 62000,
            'name' => 'test'
        );

        $writer = new Writer(null, null, array(
            'user' => function ($data, Writer $writer) {
                $writer->writeUShort($data['id']);
                $writer->writeQString($data['name']);
            }
        ));
        $writer->writeQVariant(new QVariant($in, 'user'));

        $data = (string)$writer;
        $reader = Reader::fromString($data, null, array(
            'user' => function (Reader $reader) {
                return array(
                    'id' => $reader->readUShort(),
                    'name' => $reader->readQString()
                );
            }
        ));

        $this->assertEquals($in, $reader->readQVariant());
    }

    public function testQStringList()
    {
        $writer = new Writer();
        $writer->writeQStringList(array('hello', 'world'));

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals(array('hello', 'world'), $reader->readQStringList());
    }

    public function testQCharMultiple()
    {
        $writer = new Writer();
        $writer->writeQChar('a');
        $writer->writeQChar('ä');

        $data = (string)$writer;
        $reader = Reader::fromString($data);

        $this->assertEquals('a', $reader->readQChar());
        $this->assertEquals('ä', $reader->readQChar());
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

    public function testReadQTime()
    {
        $now = new \DateTime();

        $writer = new Writer();
        $writer->writeQTime($now);

        $in = (string)$writer;
        $reader = Reader::fromString($in);

        $dt = $reader->readQTime();
        $this->assertEquals($now, $dt);
    }

    public function testReadQTimeSubSecond()
    {
        $this->markTestIncomplete('Sub-second accuracy not implemented');

        $time = '2015-05-01 16:02:03.413705';
        $now = new \DateTime($time);

        $writer = new Writer();
        $writer->writeQTime($now);

        $in = (string)$writer;
        $reader = Reader::fromString($in);

        $dt = $reader->readQTime();
        $this->assertEquals($now->format('U.u'), $dt->format('U.u'));
    }

    public function testReadQTimeMicrotime()
    {
        $this->markTestIncomplete('Sub-second accuracy not implemented');

        $now = microtime(true);

        $writer = new Writer();
        $writer->writeQTime($now);

        $in = (string)$writer;
        $reader = Reader::fromString($in);

        $dt = $reader->readQTime();
        $this->assertEquals($now, $dt->format('U.u'));
    }

    public function testReadQDateTime()
    {
        $writer = new Writer();
        $writer->writeUInt(2457136); // day 2457136 - 2015-04-23
        $writer->writeUInt(50523000); // msec 50523000 - 14:02:03 UTC
        $writer->writeBool(true);

        $in = (string)$writer;
        $reader = Reader::fromString($in);

        $dt = $reader->readQDateTime();
        $this->assertEquals('2015-04-23 14:02:03', $dt->format('Y-m-d H:i:s'));

        $writer = new Writer();
        $writer->writeQDateTime($dt);

        $out = (string)$writer;

        $this->assertEquals($in, $out);
    }

    public function testReadQDateTimeNull()
    {
        $writer = new Writer();
        $writer->writeUInt(0);
        $writer->writeUInt(0xFFFFFFFF);
        $writer->writeBool(true);

        $in = (string)$writer;
        $reader = Reader::fromString($in);

        $dt = $reader->readQDateTime();
        $this->assertNull($dt);
    }
}
