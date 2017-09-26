<?php

namespace Clue\QDataStream;

use Iodophor\Io\Reader as IoReader;
use Iodophor\Io\StringReader;

class Reader
{
    private $types;
    private $userTypeMap;
    private $hasNull = true;
    private $buffer = '';

    public static function fromString($str, Types $types = null, $userTypeMap = array())
    {
        return new self(new StringReader($str), $types, $userTypeMap);
    }

    public function __construct(IoReader $reader, Types $types = null, $userTypeMap = array())
    {
        if ($types === null) {
            $types = new Types();
        }

        $this->buffer = $reader->read($reader->getSize());
        $this->types = $types;
        $this->userTypeMap = $userTypeMap;
    }

    public function readQVariant($asNative = true)
    {
        // https://github.com/sandsmark/QuasselDroid/blob/master/QuasselDroid/src/main/java/com/iskrembilen/quasseldroid/qtcomm/QVariant.java#L92
        $type = $this->readUInt();

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
        $length = $this->readUInt();

        $list = array();
        for ($i = 0; $i < $length; ++$i) {
            $list []= $this->readQVariant($asNative);
        }

        return $list;
    }

    public function readQVariantMap($asNative = true)
    {
        $length = $this->readUInt();

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
        return $this->conv($this->read(2));
    }

    public function readQStringList()
    {
        $length = $this->readUInt();

        $list = array();
        for ($i = 0; $i < $length; ++$i) {
            $list []= $this->readQString();
        }

        return $list;
    }

    public function readQByteArray()
    {
        $length = $this->readUInt();

        if ($length === 0xFFFFFFFF) {
            return null;
        }

        return $this->read($length);
    }

    public function readInt()
    {
        $ret = unpack('l', $this->readBE(4));

        return $ret[1];
    }

    public function readUInt()
    {
        $ret = unpack('N', $this->read(4));

        return $ret[1];
    }

    public function readShort()
    {
        $ret = unpack('s', $this->readBE(2));

        return $ret[1];
    }

    public function readUShort()
    {
        $ret = unpack('n', $this->read(2));

        return $ret[1];
    }

    public function readChar()
    {
        $ret = unpack('c', $this->read(1));

        return $ret[1];
    }

    public function readUChar()
    {
        $ret = unpack('C', $this->read(1));

        return $ret[1];
    }

    public function readBool()
    {
        return $this->read(1) !== "\x00" ? true : false;
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

    /**
     * Reads a QTime from the stream and returns a DateTime with current timezone
     *
     * The QTime will be sent as the number of milliseconds since midnight,
     * without any awareness of timezone or DST properties. Thus, reading this
     * in will assume it is relative to the current timezone.
     *
     * @return \DateTime
     */
    public function readQTime()
    {
        $msec = $this->readUInt();

        $time = strtotime('midnight') + $msec / 1000;

        $dt = \DateTime::createFromFormat('U.u', sprintf('%.6F', $time));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        return $dt;
    }

    /**
     * Reads a QDateTime from the stream and returns a DateTime with current timezone
     *
     * @return \DateTime|NULL
     */
    public function readQDateTime()
    {
        $day = $this->readUInt();
        $msec = $this->readUInt();
        /*$isUtc = */ $this->readBool();

        if ($day === 0 && $msec === 0xFFFFFFFF) {
            return null;
        }

        // days since unix epoche in seconds plus msec in seconds
        $time = ($day - 2440588) * 86400 + $msec / 1000;

        $dt = \DateTime::createFromFormat('U.u', sprintf('%.6F', $time));
        $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        return $dt;
    }

    private function conv($str)
    {
        // transcode UTF-16 (big endian) to UTF-8
        return mb_convert_encoding($str, 'UTF-8', 'UTF-16BE');
    }

    private function read($bytes)
    {
        if ($bytes === 0) {
            return '';
        }

        if (!isset($this->buffer[$bytes - 1])) {
            throw new \UnderflowException('Not enough data in buffer');
        }

        $data = substr($this->buffer, 0, $bytes);
        $this->buffer = (string)substr($this->buffer, $bytes);

        return $data;
    }

    private function readBE($bytes)
    {
        $data = $this->read($bytes);

        // check if machine byte order is already BE, otherwise reverse LE to BE
        if (pack('S', 1) !== "\x00\x01") {
            $data = strrev($data);
        }

        return $data;
    }
}
