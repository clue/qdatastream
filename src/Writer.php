<?php

namespace Clue\QDataStream;

use InvalidArgumentException;
use Iodophor\Io\StringWriter as IoWriter;

// http://doc.qt.io/qt-4.8/qdatastream.html#details
class Writer
{
    private $writer;
    private $hasNull = true;

    public function __construct(IoWriter $writer = null)
    {
        if ($writer === null) {
            $writer = new IoWriter();
        }
        $this->writer = $writer;
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

    public function writeInt32($int)
    {
        $this->writer->writeInt32BE($int);
    }

    public function writeUInt32($int)
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

    public function writeStringList(array $strings)
    {
        $this->writer->writeUInt32BE(count($strings));

        foreach ($strings as $string) {
            $this->writeString($strin);
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

    public function writeVariant($value)
    {
        $this->writeVariantType($value, Types::getTypeByValue($value));
    }

    public function writeVariantType($value, $type)
    {
        $map = array(
            Types::TYPE_INT32 => 'writeInt32',
            Types::TYPE_STRING => 'writeString',
            Types::TYPE_BOOL => 'writeBool',
            Types::TYPE_VARIANT_LIST => 'writeVariantList',
            Types::TYPE_VARIANT_MAP => 'writeVariantMap',
        );
        if (!isset($map[$type])) {
            throw new InvalidArgumentException('Invalid/unknown variant type (' . $type . ')');
        }

        $this->writeType($type);
        $this->$map[$type]($value);
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
}
