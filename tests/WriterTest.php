<?php

use Clue\QDataStream\Writer;

class WriterTest extends TestCase
{
    public function setUp()
    {
        $this->writer = new Writer();
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

    public function testBytesEmpty()
    {
        $this->writer->writeByteArray('');
        $this->assertEquals("\x00\x00\x00\x00", (string)$this->writer);
    }

    public function testBytesNull()
    {
        $this->writer->writeByteArray(null);
        $this->assertEquals("\xFF\xFF\xFF\xFF", (string)$this->writer);
    }

    public function testBytesTwo()
    {
        $this->writer->writeByteArray("\xAA\x12");
        $this->assertEquals("\x00\x00\x00\x02" . "\xAA\x12", (string)$this->writer);
    }

    public function testStringEmpty()
    {
        $this->writer->writeString('');
        $this->assertEquals("\x00\x00\x00\x00", (string)$this->writer);
    }

    public function testStringNull()
    {
        $this->writer->writeString(null);
        $this->assertEquals("\xFF\xFF\xFF\xFF", (string)$this->writer);
    }

    public function testStringHi()
    {
        $this->writer->writeString('Hi');
        $this->assertEquals("\x00\x00\x00\x04" . "\x00H\x00i", (string)$this->writer);
    }

    public function testVariantBoolTrue()
    {
        $this->writer->writeVariant(true);
        $this->assertEquals("\x00\x00\x00\x01\x00" . "\x01", (string)$this->writer);
    }

    public function testVariantBoolFalse()
    {
        $this->writer->writeVariant(false);
        $this->assertEquals("\x00\x00\x00\x01\x00" . "\x00", (string)$this->writer);
    }

    public function testVariantInteger()
    {
        $this->writer->writeVariant(31);
        $this->assertEquals("\x00\x00\x00\x02\x00" . "\x00\x00\x00\x1F", (string)$this->writer);
    }

    public function testVariantStringEmpty()
    {
        $this->writer->writeVariant('');
        $this->assertEquals("\x00\x00\x00\x0A\x00" . "\x00\x00\x00\x00", (string)$this->writer);
    }

    public function testVariantStringHi()
    {
        $this->writer->writeVariant('Hi');
        $this->assertEquals("\x00\x00\x00\x0A\x00" . "\x00\x00\x00\x04" . "\x00H\x00i", (string)$this->writer);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testVariantNullCanNotBeSerialized()
    {
        $this->writer->writeVariant(null);
    }

    public function testIntegersConcatenated()
    {
        $this->writer->writeInt(0);
        $this->writer->writeInt(256);
        $this->assertEquals("\x00\x00\x00\x00" . "\x00\x00\x01\x00", (string)$this->writer);
    }

    public function testVariantIntegersConcatenated()
    {
        $this->writer->writeVariant(0);
        $this->writer->writeVariant(256);
        $this->assertEquals("\x00\x00\x00\x02\x00" . "\x00\x00\x00\x00" . "\x00\x00\x00\x02\x00" . "\x00\x00\x01\x00", (string)$this->writer);
    }

}
