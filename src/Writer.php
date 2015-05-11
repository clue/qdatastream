<?php

namespace Clue\QDataStream;

use InvalidArgumentException;
use Iodophor\Io\StringWriter as IoWriter;

// http://doc.qt.io/qt-4.8/qdatastream.html#details
class Writer
{
    private $writer;
    private $types;
    private $userTypeMap;
    private $hasNull = true;

    public function __construct(IoWriter $writer = null, Types $types = null, $userTypeMap = array())
    {
        if ($writer === null) {
            $writer = new IoWriter();
        }
        if ($types === null) {
            $types = new Types();
        }

        $this->writer = $writer;
        $this->types = $types;
        $this->userTypeMap = $userTypeMap;
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

    public function writeQStringList(array $strings)
    {
        $this->writer->writeUInt32BE(count($strings));

        foreach ($strings as $string) {
            $this->writeQString($string);
        }
    }

    public function writeQString($str)
    {
        if ($str !== null) {
            $str = $this->conv($str);
        }

        $this->writeQByteArray($str);
    }

    public function writeQChar($char)
    {
        $this->writer->write($this->conv($char), 2);
    }

    public function writeQByteArray($bytes)
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

    public function writeQVariant($value, $type = null)
    {
        if ($type === null) {
            $type = $this->types->getTypeByValue($value);
        }

        if (is_string($type)) {
            $this->writeType(Types::TYPE_QUSER_TYPE);

            return $this->writeQUserTypeByName($value, $type);
        }

        $name = 'write' . $this->types->getNameByType($type);
        if (!method_exists($this, $name)) {
            throw new \BadMethodCallException('Known variant type (' . $type . '), but has no "' . $name . '()" method');
        }

        $this->writeType($type);
        $this->$name($value);
    }

    public function writeQUserTypeByName($value, $userType)
    {
        if (!isset($this->userTypeMap[$userType])) {
            throw new \UnexpectedValueException('Unknown user type "' . $userType . '" does not have any data mapping');
        }
        $this->writeQByteArray($userType . "\x00");

        $fn = $this->userTypeMap[$userType];
        $fn($value, $this);
    }

    public function writeQVariantList(array $list, $explicitTypes = array())
    {
        $this->writer->writeUInt32BE(count($list));

        foreach ($list as $index => $value) {
            $this->writeQVariant(
                $value,
                isset($explicitTypes[$index]) ? $explicitTypes[$index] : null
            );
        }
    }

    public function writeQVariantMap(array $map, $explicitTypes = array())
    {
        $this->writer->writeUInt32BE(count($map));

        foreach ($map as $key => $value) {
            $this->writeQString($key);
            $this->writeQVariant(
                $value,
                isset($explicitTypes[$key]) ? $explicitTypes[$key] : null
            );
        }
    }

    public function writeQTime($timestamp)
    {
        if ($timestamp instanceof \DateTime) {
            $timestamp = $timestamp->format('U.u');
        }

        $msec = round(($timestamp - strtotime('midnight')) * 1000);
        $this->writer->writeUInt32BE($msec);
    }

    public function writeQDateTime($timestamp)
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

    private function conv($str)
    {
        // transcode UTF-8 to UTF-16 (big endian)
        return mb_convert_encoding($str, 'UTF-16BE', 'UTF-8');
    }
}
