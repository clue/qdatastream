<?php

namespace Clue\QDataStream;

use Iodophor\Io\Reader as IoReader;
use Iodophor\Io\StringReader;

class Reader
{
    private $reader;
    private $types;
    private $userTypeMap;
    private $hasNull = true;

    public static function fromString($str, Types $types = null, $userTypeMap = array())
    {
        return new self(new StringReader($str), $types, $userTypeMap);
    }

    public function __construct(IoReader $reader, Types $types = null, $userTypeMap = array())
    {
        if ($types === null) {
            $types = new Types();
        }

        $this->reader = $reader;
        $this->types = $types;
        $this->userTypeMap = $userTypeMap;
    }

    public function readQVariant($asNative = true)
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

        $value = $this->$name($asNative);

        // wrap in QVariant if requested and this is not a UserType
        if (!$asNative && $type !== Types::TYPE_QUSER_TYPE) {
            $value = new QVariant($value, $type);
        }

        return $value;
    }

    public function readQVariantList($asNative = true)
    {
        $length = $this->reader->readUInt32BE();

        $list = array();
        for ($i = 0; $i < $length; ++$i) {
            $list []= $this->readQVariant($asNative);
        }

        return $list;
    }

    public function readQVariantMap($asNative = true)
    {
        $length = $this->reader->readUInt32BE();

        $map = array();
        for ($i = 0; $i < $length; ++$i) {
            $key = $this->readQString();
            $value = $this->readQVariant($asNative);

            $map[$key] = $value;
        }

        return $map;
    }

    public function readQString()
    {
        $str = $this->readQByteArray();
        if ($str !== null) {
            $str = $this->conv($str);
        }

        return $str;
    }

    public function readQChar()
    {
        return $this->conv($this->reader->read(2));
    }

    public function readQStringList()
    {
        $length = $this->reader->readUInt32BE();

        $list = array();
        for ($i = 0; $i < $length; ++$i) {
            $list []= $this->readQString(true);
        }

        return $list;
    }

    public function readQByteArray()
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

    public function readChar()
    {
        return $this->reader->readInt8();
    }

    public function readUChar()
    {
        return $this->reader->readUInt8();
    }

    public function readBool()
    {
        return $this->reader->readUInt8() ? true : false;
    }

    public function readQUserType($asNative = true)
    {
        // name is encoded as UTF-8 string (byte array) and ends with \0 as last byte
        $name = substr($this->readQByteArray(), 0, -1);

        $value = $this->readQUserTypeByName($name);

        if (!$asNative) {
            $value = new QVariant($value, $name);
        }

        return $value;
    }

    public function readQUserTypeByName($name)
    {
        if (!isset($this->userTypeMap[$name])) {
            throw new \UnexpectedValueException('Unknown user type "' . $name . '" does not have any data mapping');
        }
        $name = $this->userTypeMap[$name];

        return $name($this);
    }

    public function readQTime()
    {
        $msec = $this->readUInt();
        // TODO: losing sub-second precision here..
        $secondsSinceMidnight = round($msec / 1000);

        $dt = new \DateTime('midnight');
        $dt->modify('+' . $secondsSinceMidnight . ' seconds');

        return $dt;
    }

    public function readQDateTime()
    {
        $day = $this->readUInt();
        $msec = $this->readUInt();
        $isUtc = $this->readBool();

        if ($day === 0 && $msec === 0xFFFFFFFF) {
            return null;
        }

        $daysSinceUnixEpoche = $day - 2440588; // unix epoche
        // TODO: losing sub-second precision here..
        $secondsSinceMidnight = round($msec / 1000);

        $dt = new \DateTime('1970-01-01', $isUtc ? new \DateTimeZone('UTC') : null);
        $dt->modify('+' . $daysSinceUnixEpoche . ' days');
        $dt->modify('+' . $secondsSinceMidnight . ' seconds');

        return $dt;
    }

    private function conv($str)
    {
        // transcode UTF-16 (big endian) to UTF-8
        return mb_convert_encoding($str, 'UTF-8', 'UTF-16BE');
    }
}
