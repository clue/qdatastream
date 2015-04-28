<?php

namespace Clue\QDataStream;

use Clue\QDataStream\Writer;
use Iodophor\Io\Reader as IoReader;
use Iodophor\Io\StringReader;

class Reader
{
    private $reader;
    private $hasNull = true;

    public static function fromString($str)
    {
        return new self(new StringReader($str));
    }

    public function __construct(IoReader $reader)
    {
        $this->reader = $reader;
    }

    public function readVariant()
    {
        // https://github.com/sandsmark/QuasselDroid/blob/master/QuasselDroid/src/main/java/com/iskrembilen/quasseldroid/qtcomm/QVariant.java#L92
        $type = $this->reader->readUInt32BE();

        if ($this->hasNull) {
            /*$isNull = */ $this->readBool();
        }

        if ($type === Types::TYPE_VARIANT_LIST) {
            return $this->readVariantList();
        }
        if ($type === Types::TYPE_VARIANT_MAP) {
            return $this->readVariantMap();
        }
        if ($type === Types::TYPE_STRING) {
            return $this->readString();
        }
        if ($type === Types::TYPE_STRING_LIST) {
            return $this->readStringList();
        }
        if ($type === Types::TYPE_BYTE_ARRAY) {
            return $this->readByteArray();
        }
        if ($type === Types::TYPE_INT32) {
            return $this->readInt();
        }
        if ($type === Types::TYPE_UINT32) {
            return $this->readUInt();
        }
        if ($type === Types::TYPE_BOOL) {
            return $this->readBool();
        }
        throw new \InvalidArgumentException('Invalid/unknown variant type (' . $type . ')');
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

    public function readBool()
    {
        return $this->reader->readUInt8() ? true : false;
    }
}
