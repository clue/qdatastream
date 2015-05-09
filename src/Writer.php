<?php

namespace Clue\QDataStream;

use InvalidArgumentException;
use Iodophor\Io\StringWriter as IoWriter;

// http://doc.qt.io/qt-4.8/qdatastream.html#details
class Writer
{
    private $writer;
    private $types;
    private $hasNull = true;

    public function __construct(IoWriter $writer = null, Types $types = null)
    {
        if ($writer === null) {
            $writer = new IoWriter();
        }
        if ($types === null) {
            $types = new Types();
        }

        $this->writer = $writer;
        $this->types = $types;
    }

    public function __toString()
    {
        return $this->writer->toString();
    }

    public function writeType($type)
    {
        $this->writer->writeUInt32BE($type);
        if ($this->hasNull) {
            $this->writer->writeUInt8(0);
        }
    }

    public function writeInt($int)
    {
        $this->writer->writeInt32BE($int);
    }

    public function writeUInt($int)
    {
        $this->writer->writeUInt32BE($int);
    }

    public function writeShort($int)
    {
        $this->writer->writeInt16BE($int);
    }

    public function writeUShort($int)
    {
        $this->writer->writeUInt16BE($int);
    }

    public function writeChar($int)
    {
        $this->writer->writeInt8($int);
    }

    public function writeUChar($int)
    {
        $this->writer->writeUInt8($int);
    }

    public function writeStringList(array $strings)
    {
        $this->writer->writeUInt32BE(count($strings));

        foreach ($strings as $string) {
            $this->writeString($string);
        }
    }

    public function writeString($str)
    {
        if ($str !== null) {
            // transcode UTF-8 to UTF-16 (big endian)
            $str = mb_convert_encoding($str, 'UTF-16BE', 'UTF-8');
        }

        $this->writeByteArray($str);
    }

    public function writeByteArray($bytes)
    {
        if ($bytes === null) {
            $this->writer->writeUInt32BE(0xFFFFFFFF);
        } else {
            $this->writer->writeUInt32BE(strlen($bytes));
            $this->writer->write($bytes);
        }
    }

    public function writeBool($value)
    {
        // http://docs.oracle.com/javase/7/docs/api/java/io/DataOutput.html#writeBoolean%28boolean%29
        $this->writer->writeUInt8($value ? 1 : 0);
    }

    public function writeVariant($value, $type = null)
    {
        if ($type === null) {
            $type = $this->types->getTypeByValue($value);
        }

        if (is_string($type)) {
            $this->writeType(Types::TYPE_USER_TYPE);

            return $this->writeUserTypeByName($value, $type);
        }

        $name = 'write' . $this->types->getNameByType($type);
        if (!method_exists($this, $name)) {
            throw new \BadMethodCallException('Known variant type (' . $type . '), but has no "' . $name . '()" method');
        }

        $this->writeType($type);
        $this->$name($value);
    }

    public function writeVariantList(array $list)
    {
        $this->writer->writeUInt32BE(count($list));

        foreach ($list as $value) {
            $this->writeVariant($value);
        }
    }

    public function writeVariantMap(array $map)
    {
        $this->writer->writeUInt32BE(count($map));

        foreach ($map as $key => $value) {
            $this->writeString($key);
            $this->writeVariant($value);
        }
    }

    public function writeTime($timestamp)
    {
        if ($timestamp instanceof \DateTime) {
            $timestamp = $timestamp->format('U.u');
        }

        $msec = round(($timestamp - strtotime('midnight')) * 1000);
        $this->writer->writeUInt32BE($msec);
    }

    public function writeDateTime($timestamp)
    {
        if ($timestamp instanceof \DateTime) {
            $timestamp = $timestamp->format('U.u');
        }
        $msec = round(($timestamp % 86400) * 1000);
        $days = floor($timestamp / 86400) + 2440588;

        $this->writer->writeUInt32BE($days);
        $this->writer->writeUInt32BE($msec);
        $this->writer->writeInt8(1);
    }
}
