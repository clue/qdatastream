<?php

namespace Clue\QDataStream;

// http://doc.qt.io/qt-4.8/qdatastream.html#details
class Writer
{
    private $userTypeMap;
    private $hasNull = true;

    private $buffer = '';

    /**
     * @param array $userTypeMap
     */
    public function __construct($userTypeMap = array())
    {
        $this->userTypeMap = $userTypeMap;
    }

    /**
     * Returns concatenated write buffer as a single string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->buffer;
    }

    /**
     * @param int $int INT32
     * @return void
     */
    public function writeInt($int)
    {
        $this->writeBE(pack('l', $int));
    }

    /**
     * @param int $int UINT32
     * @return void
     */
    public function writeUInt($int)
    {
        $this->buffer .= pack('N', $int);
    }

    /**
     * @param int $int INT16
     * @return void
     */
    public function writeShort($int)
    {
        $this->writeBE(pack('s', $int));
    }

    /**
     * @param int $int UINT16
     * @return void
     */
    public function writeUShort($int)
    {
        $this->buffer .= pack('n', $int);
    }

    /**
     * @param int $int INT8
     * @return void
     */
    public function writeChar($int)
    {
        $this->buffer .= pack('c', $int);
    }

    /**
     * @param int $int UINT8
     * @return void
     */
    public function writeUChar($int)
    {
        $this->buffer .= pack('C', $int);
    }

    /**
     * @param string[] $strings array of text strings in UTF-8 encoding (will be transcoded to UTF-16BE)
     * @return void
     */
    public function writeQStringList(array $strings)
    {
        $this->writeUInt(count($strings));

        foreach ($strings as $string) {
            $this->writeQString($string);
        }
    }

    /**
     * @param string|null $str text string in UTF-8 encoding (will be transcoded to UTF-16BE)
     * @return void
     * @see self::writeQByteArray() for writing binary data
     */
    public function writeQString($str)
    {
        if ($str !== null && $str !== '') {
            $str = $this->conv($str);
        }

        $this->writeQByteArray($str);
    }

    /**
     * @param string $char single text character in UTF-8 encoding. This will be
     *     transcoded to UTF-16BE (or actually UCS-2BE because it only supports
     *     BMP characters) and uses "?" replacement character otherwise.
     * @return void
     */
    public function writeQChar($char)
    {
        // transcode to UTF-16BE and assume result to be exactly two bytes, otherwise use "?"
        $char = $this->conv($char);
        $this->buffer .= isset($char[1]) && !isset($char[2]) ? $char : "\x00?";
    }

    /**
     * @param string|null $bytes binary byte string
     * @return void
     * @see self::writeQString() for writing text string
     */
    public function writeQByteArray($bytes)
    {
        if ($bytes === null) {
            $this->buffer .= "\xFF\xFF\xFF\xFF";
        } else {
            $this->writeUInt(strlen($bytes));
            $this->buffer .= $bytes;
        }
    }

    /**
     * @param bool $value
     * @return void
     */
    public function writeBool($value)
    {
        // http://docs.oracle.com/javase/7/docs/api/java/io/DataOutput.html#writeBoolean%28boolean%29
        $this->buffer .= $value ? "\x01" : "\x00";
    }

    /**
     * @param QVariant|mixed $value
     * @return void
     * @throws \UnexpectedValueException if an unknown QUserType is encountered
     */
    public function writeQVariant($value)
    {
        if ($value instanceof QVariant) {
            $type = $value->getType();
            $value = $value->getValue();
        } else {
            $type = Types::getTypeByValue($value);
        }

        if (is_string($type)) {
            $this->writeUInt(Types::TYPE_QUSER_TYPE);
            if ($this->hasNull) {
                $this->buffer .= "\x00";
            }

            return $this->writeQUserTypeByName($value, $type);
        }

        $name = 'write' . Types::getNameByType($type);
        if (!method_exists($this, $name)) {
            throw new \BadMethodCallException('Known variant type (' . $type . '), but has no "' . $name . '()" method'); // @codeCoverageIgnore
        }

        $this->writeUInt($type);
        if ($this->hasNull) {
            $this->buffer .= "\x00";
        }
        $this->$name($value);
    }

    /**
     * @param mixed  $value
     * @param string $userType
     * @return void
     * @throws \UnexpectedValueException if an unknown QUserType is encountered
     */
    public function writeQUserTypeByName($value, $userType)
    {
        if (!isset($this->userTypeMap[$userType])) {
            throw new \UnexpectedValueException('Unknown user type "' . $userType . '" does not have any data mapping');
        }
        $this->writeQByteArray($userType . "\x00");

        $fn = $this->userTypeMap[$userType];
        $fn($value, $this);
    }

    /**
     * @param array<QVariant|mixed> $list
     * @return void
     * @throws \UnexpectedValueException if an unknown QUserType is encountered
     */
    public function writeQVariantList(array $list)
    {
        $this->writeUInt(count($list));

        foreach ($list as $value) {
            $this->writeQVariant($value);
        }
    }

    /**
     * @param array|\stdClass $map
     * @return void
     * @throws \UnexpectedValueException if an unknown QUserType is encountered
     */
    public function writeQVariantMap($map)
    {
        if ($map instanceof \stdClass) {
            $map = (array)$map;
        }

        $this->writeUInt(count($map));

        foreach ($map as $key => $value) {
            $this->writeQString($key);
            $this->writeQVariant($value);
        }
    }

    /**
     * Writes a QTime for the given timestamp or DateTime object
     *
     * The QTime will only carry the number of milliseconds since midnight.
     * This means you should probably only use this for times within the current
     * day.
     *
     * If you pass a timestamp from any other day, it will write the number of
     * milliseconds that passed since that day's midnight. Note that reading
     * this number has no indication this is not the current day, so you're
     * likely going to lose the day information and may end up with wrong dates.
     *
     * The QTime will be sent as the number of milliseconds since midnight,
     * without any awareness of timezone or DST properties. Thus, writing this
     * will assume it is relative to the current timezone. This means that the
     * time "14:10:34.5108" will be 14h after midnight, irrespective of its
     * actual timezone. The receiving side may not be aware of your local
     * timezone, so it can only assume its own local timezone as a base.
     *
     * Make sure to use (i.e. convert via `setTimeZone()`) to the same timezone
     * on both sides or consider using the `writeQDateTime()` method instead,
     * which uses absolute time stamps and does not suffer from this.
     *
     * You can also pass a Unix timestamp to this function, this will be assumed
     * to be relative to the local midnight timestamp. If you need more control
     * over your timezone, consider passing a `DateTime` object instead.
     *
     * @param \DateTime|float $timestamp
     * @see self::writeQDateTime
     */
    public function writeQTime($timestamp)
    {
        if ($timestamp instanceof \DateTime) {
            $msec = $timestamp->format('H') * 3600000 +
                    $timestamp->format('i') * 60000 +
                    $timestamp->format('s') * 1000 +
                    (int)($timestamp->format('0.u') * 1000);
        } else {
            $msec = round(($timestamp - strtotime('midnight', (int)$timestamp)) * 1000);
        }

        $this->writeUInt($msec);
    }

    /**
     * @param \DateTime|float $timestamp
     * @return void
     */
    public function writeQDateTime($timestamp)
    {
        if ($timestamp instanceof \DateTime) {
            $timestamp = $timestamp->format('U.u');
        }

        // days:uint, seconds:uint, isUTC:uchar
        $this->buffer .= pack(
            'NNC',
            floor($timestamp / 86400) + 2440588,
            round(($timestamp - floor($timestamp / 86400) * 86400) * 1000),
            1
        );
    }

    /**
     * transcode UTF-8 to UTF-16BE
     *
     * @param string $str
     * @return string
     * @codeCoverageIgnore
     */
    private function conv($str)
    {
        // prefer mb_convert_encoding if available
        if (function_exists('mb_convert_encoding')) {
            mb_substitute_character(ord('?'));
            return mb_convert_encoding($str, 'UTF-16BE', 'UTF-8');
        }

        // otherwise match each UTF-8 character byte sequence, manually convert
        // it to its Unicode code point and then encode as UTF-16BE byte sequence.
        return preg_replace_callback('/./su', function ($m) {
            if (!isset($m[0][1])) {
                // U+0000 - U+007F single byte ASCII/UTF-8 character
                return "\x00" . $m[0];
            }

            if (isset($m[0][3])) {
                // U+10000 - U+10FFFF uses four UTF-8 bytes and 4 UTF-16 bytes
                // get code point and convert to higher and lower surrogate
                $code = ((ord($m[0][0]) & 0x07) << 18) + ((ord($m[0][1]) & 0x3F) << 12) + ((ord($m[0][2]) & 0x3F) << 6) + (ord($m[0][3]) & 0x3F);
                return pack('nn', ($code - 0x10000 >> 10) | 0xD800, ($code & 0x03FF) | 0xDC00);
            }

            if (isset($m[0][2])) {
                // U+0800 - U+FFFF uses three UTF-8 bytes
                $code = ((ord($m[0][0]) & 0x0F) << 12) + ((ord($m[0][1]) & 0x3F) << 6) + (ord($m[0][2]) & 0x3F);
            } else {
                // U+0080 - U+07FF uses two UTF-8 bytes
                $code = ((ord($m[0][0]) & 0x1F) << 6) + (ord($m[0][1]) & 0x3F);
            }

            return chr($code >> 8) . chr($code & 0xFF);
        }, $str);
    }

    private function writeBE($bytes)
    {
        // check if machine byte order is already BE, otherwise reverse LE to BE
        if (pack('S', 1) !== "\x00\x01") {
            $bytes = strrev($bytes);
        }

        $this->buffer .= $bytes;
    }
}
