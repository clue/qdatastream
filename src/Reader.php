<?php

namespace Clue\QDataStream;

use Clue\QDataStream\Writer;
use Iodophor\Io\Reader as IoReader;
use Iodophor\Io\StringReader;

class Reader
{
    private $reader;
    private $types;
    private $hasNull = true;

    public static function fromString($str, Types $types = null)
    {
        return new self(new StringReader($str), $types);
    }

    public function __construct(IoReader $reader, Types $types = null)
    {
        if ($types === null) {
            $types = new Types();
        }

        $this->reader = $reader;
        $this->types = $types;
    }

    public function readVariant()
    {
        // https://github.com/sandsmark/QuasselDroid/blob/master/QuasselDroid/src/main/java/com/iskrembilen/quasseldroid/qtcomm/QVariant.java#L92
        $type = $this->reader->readUInt32BE();

        if ($this->hasNull) {
            /*$isNull = */ $this->readBool();
        }

        $name = 'read' . $this->types->getNameByType($type);
        if (!method_exists($this, $name)) {
            throw new \BadMethodCallException('Known variant type (' . $type . '), but has no "' . $name . '()" method');
        }

        return $this->$name();
    }

    public function readVariantList()
    {
        $length = $this->reader->readUInt32BE();

        $list = array();
        for ($i = 0; $i < $length; ++$i) {
            $list []= $this->readVariant();
        }

        return $list;
    }

    public function readVariantMap()
    {
        $length = $this->reader->readUInt32BE();

        $map = array();
        for ($i = 0; $i < $length; ++$i) {
            $key = $this->readString();
            $value = $this->readVariant();

            $map[$key] = $value;
        }

        return $map;
    }

    public function readString()
    {
        $str = $this->readByteArray();
        if ($str === null) {
            return $str;
        }

        // transcode UTF-16 (big endian) to UTF-8
        return mb_convert_encoding($str, 'UTF-8', 'UTF-16BE');
    }

    public function readStringList()
    {
        $length = $this->reader->readUInt32BE();

        $list = array();
        for ($i = 0; $i < $length; ++$i) {
            $list []= $this->readString(true);
        }

        return $list;
    }

    public function readByteArray()
    {
        $length = $this->reader->readUInt32BE();

        if ($length === 0xFFFFFFFF) {
            return null;
        }

        return $this->reader->read($length);
    }

    public function readInt()
    {
        return $this->reader->readInt32BE();
    }

    public function readUInt()
    {
        return $this->reader->readUInt32BE();
    }

    public function readShort()
    {
        return $this->reader->readInt16BE();
    }

    public function readUShort()
    {
        return $this->reader->readUInt16BE();
    }

    public function readBool()
    {
        return $this->reader->readUInt8() ? true : false;
    }
}
