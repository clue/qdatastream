<?php

namespace Clue\QDataStream;

class Reader
{
    private $buffer = '';
    private $pos = 0;

    private $userTypeMap;
    private $hasNull = true;
    private $supportsExtMbstring;

    /**
     * @param string $buffer
     * @param array  $userTypeMap
     */
    public function __construct($buffer, $userTypeMap = array())
    {
        $this->buffer = $buffer;
        $this->userTypeMap = $userTypeMap;

        // prefer mb_convert_encoding if available
        $this->supportsExtMbstring = \function_exists('mb_convert_encoding');
    }

    /**
     * @return mixed
     * @throws \UnderflowException
     * @throws \UnexpectedValueException if an unknown QUserType is encountered
     */
    public function readQVariant()
    {
        // https://github.com/sandsmark/QuasselDroid/blob/master/QuasselDroid/src/main/java/com/iskrembilen/quasseldroid/qtcomm/QVariant.java#L92
        $type = $this->readUInt();

        if ($this->hasNull) {
            // $isNull = $this->readBool();
            $this->pos++;
        }

        switch ($type) {
            case Types::TYPE_BOOL:
                return $this->readBool();
            case Types::TYPE_INT:
                return $this->readInt();
            case Types::TYPE_UINT:
                return $this->readUInt();
            case Types::TYPE_QCHAR:
                return $this->readQChar();
            case Types::TYPE_QVARIANT_MAP:
                return $this->readQVariantMap();
            case Types::TYPE_QVARIANT_LIST:
                return $this->readQVariantList();
            case Types::TYPE_QSTRING:
                return $this->readQString();
            case Types::TYPE_QSTRING_LIST:
                return $this->readQStringList();
            case Types::TYPE_QBYTE_ARRAY:
                return $this->readQByteArray();
            case Types::TYPE_QTIME:
                return $this->readQTime();
            case Types::TYPE_QDATETIME:
                return $this->readQDateTime();
            case Types::TYPE_QUSER_TYPE:
                return $this->readQUserType();
            case Types::TYPE_SHORT:
                return $this->readShort();
            case Types::TYPE_CHAR:
                return $this->readChar();
            case Types::TYPE_USHORT:
                return $this->readUShort();
            case Types::TYPE_UCHAR:
                return $this->readUChar();
            default:
                throw new \UnexpectedValueException('Invalid/unknown variant type (' . $type . ')');
        }
    }

    /**
     * @return mixed[]
     * @throws \UnderflowException
     * @throws \UnexpectedValueException if an unknown QUserType is encountered
     */
    public function readQVariantList()
    {
        $length = $this->readUInt();

        $list = array();
        for ($i = 0; $i < $length; ++$i) {
            $list []= $this->readQVariant();
        }

        return $list;
    }

    /**
     * @return \stdClass
     * @throws \UnderflowException
     * @throws \UnexpectedValueException if an unknown QUserType is encountered
     */
    public function readQVariantMap()
    {
        $length = $this->readUInt();

        $map = new \stdClass();
        for ($i = 0; $i < $length; ++$i) {
            $key = $this->readQString();
            $value = $this->readQVariant();

            $map->$key = $value;
        }

        return $map;
    }

    /**
     * @return string|null text string in UTF-8 encoding (will be transcoded from UTF-16BE)
     * @throws \UnderflowException
     * @see self::readQByteArray() for reading binary data
     */
    public function readQString()
    {
        $length = $this->readUInt();

        if ($length === 0) {
            return '';
        } elseif ($length === 0xFFFFFFFF) {
            return null;
        }

        return $this->conv($this->read($length));
    }

    /**
     * @return string single text character in UTF-8 encoding (will be transcoded from UTF-16BE)
     * @throws \UnderflowException
     */
    public function readQChar()
    {
        return $this->conv($this->read(2));
    }

    /**
     * @return string[] array of text strings in UTF-8 encoding (will be transcoded from UTF-16BE)
     * @throws \UnderflowException
     */
    public function readQStringList()
    {
        $length = $this->readUInt();

        $list = array();
        for ($i = 0; $i < $length; ++$i) {
            $list []= $this->readQString();
        }

        return $list;
    }

    /**
     * @return string|null binary byte string
     * @throws \UnderflowException
     * @see self::readQString() for reading text strings
     */
    public function readQByteArray()
    {
        $length = $this->readUInt();

        if ($length === 0) {
            return '';
        } elseif ($length === 0xFFFFFFFF) {
            return null;
        }

        return $this->read($length);
    }

    /**
     * @return int INT32
     * @throws \UnderflowException
     */
    public function readInt()
    {
        $ret = unpack('l', $this->readBE(4));

        return $ret[1];
    }

    /**
     * @return int UINT32
     * @throws \UnderflowException
     */
    public function readUInt()
    {
        // this method is used all over this class, so it deserves a special
        // case for reading common case of 4 bytes without an expensive substr()
        // function call. Otherwise identical with parsing result of `read(4)`.
        if (!isset($this->buffer[$this->pos + 3])) {
            throw new \UnderflowException('Not enough data in buffer');
        }
        $ret = unpack('N', $this->buffer[$this->pos] . $this->buffer[$this->pos + 1] . $this->buffer[$this->pos + 2] . $this->buffer[$this->pos + 3]);
        $this->pos += 4;

        return $ret[1];
    }

    /**
     * @return int INT16
     * @throws \UnderflowException
     */
    public function readShort()
    {
        $ret = unpack('s', $this->readBE(2));

        return $ret[1];
    }

    /**
     * @return int UINT16
     * @throws \UnderflowException
     */
    public function readUShort()
    {
        $ret = unpack('n', $this->read(2));

        return $ret[1];
    }

    /**
     * @return int INT8
     * @throws \UnderflowException
     */
    public function readChar()
    {
        $ret = unpack('c', $this->read(1));

        return $ret[1];
    }

    /**
     * @return int UINT8
     * @throws \UnderflowException
     */
    public function readUChar()
    {
        $ret = unpack('C', $this->read(1));

        return $ret[1];
    }

    /**
     * @return bool
     * @throws \UnderflowException
     */
    public function readBool()
    {
        // this method is used all over this class, so it deserves a special
        // case for reading common case of 1 byte without an expensive substr()
        // function call. Otherwise identical with parsing result of `read(1)`.
        if (!isset($this->buffer[$this->pos])) {
            throw new \UnderflowException('Not enough data in buffer');
        }

        return $this->buffer[$this->pos++] !== "\x00";
    }

    /**
     * @return mixed
     * @throws \UnexpectedValueException if an unknown QUserType is encountered
     */
    public function readQUserType()
    {
        // name is encoded as UTF-8 string (byte array) and ends with \0 as last byte
        $name = substr($this->readQByteArray(), 0, -1);

        return $this->readQUserTypeByName($name);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \UnexpectedValueException if an unknown QUserType is encountered
     */
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
     * @throws \UnderflowException
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
     * @return \DateTime|null
     * @throws \UnderflowException
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

    /**
     * transcode UTF-16BE to UTF-8
     *
     * @param string $str
     * @return string
     */
    private function conv($str)
    {
        // prefer mb_convert_encoding if available
        if ($this->supportsExtMbstring) {
            return mb_convert_encoding($str, 'UTF-8', 'UTF-16BE');
        }

        // Otherwise convert each byte pair to its Unicode code point and
        // then manually encode as UTF-8 bytes.
        return preg_replace_callback('/(?:[\xD8-\xDB]...)|(?:..)/s', function ($m) {
            if (isset($m[0][3])) {
                // U+10000 - U+10FFFF uses four UTF-16 bytes and 4 UTF-8 bytes
                // get code point from higher and lower surrogate and convert
                list(, $higher, $lower) = unpack('n*', $m[0]);
                $code = (($higher & 0x03FF) << 10) + ($lower & 0x03FF) + 0x10000;

                return pack(
                    'c*',
                    $code >> 18 | 0xF0,
                    $code >> 12 & 0x3F | 0x80,
                    $code >> 6 & 0x3F | 0x80,
                    $code & 0x3F | 0x80
                );
            }

            list(, $code) = unpack('n', $m[0]);
            if ($code < 0x80) {
                // U+0000 - U+007F encodes as single ASCII/UTF-8 byte
                return chr($code);
            } elseif ($code < 0x0800) {
                // U+0080 - U+07FF encodes as two UTF-8 bytes
                return chr($code >> 6 | 0xC0) . chr($code & 0x3F | 0x80);
            } else {
                // U+0800 - U+FFFF encodes as three UTF-8 bytes
                return chr($code >> 12 | 0xE0) . chr($code >> 6 & 0x3F | 0x80) . chr($code & 0x3F | 0x80);
            }
            return '?';
        }, $str);
    }

    private function read($bytes)
    {
        if (!isset($this->buffer[$this->pos + $bytes - 1])) {
            throw new \UnderflowException('Not enough data in buffer');
        }

        $data = substr($this->buffer, $this->pos, $bytes);
        $this->pos += $bytes;

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
