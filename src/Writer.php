<?php

namespace Clue\QDataStream;

use Iodophor\Io\StringWriter as IoWriter;

// http://doc.qt.io/qt-4.8/qdatastream.html#details
class Writer
{
    private $types;
    private $userTypeMap;
    private $hasNull = true;

    private $buffer = '';

    public function __construct(IoWriter $_ = null, Types $types = null, $userTypeMap = array())
    {
        if ($types === null) {
            $types = new Types();
        }

        $this->types = $types;
        $this->userTypeMap = $userTypeMap;
    }

    public function __toString()
    {
        return $this->buffer;
    }

    public function writeType($type)
    {
        $this->writeUInt($type);
        if ($this->hasNull) {
            $this->buffer .= "\x00";
        }
    }

    public function writeInt($int)
    {
        $this->writeBE(pack('l', $int));
    }

    public function writeUInt($int)
    {
        $this->buffer .= pack('N', $int);
    }

    public function writeShort($int)
    {
        $this->writeBE(pack('s', $int));
    }

    public function writeUShort($int)
    {
        $this->buffer .= pack('n', $int);
    }

    public function writeChar($int)
    {
        $this->buffer .= pack('c', $int);
    }

    public function writeUChar($int)
    {
        $this->buffer .= pack('C', $int);
    }

    public function writeQStringList(array $strings)
    {
        $this->writeUInt(count($strings));

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
        $this->buffer .= substr($this->conv($char), 0, 2);
    }

    public function writeQByteArray($bytes)
    {
        if ($bytes === null) {
            $this->buffer .= "\xFF\xFF\xFF\xFF";
        } else {
            $this->writeUInt(strlen($bytes));
            $this->buffer .= $bytes;
        }
    }

    public function writeBool($value)
    {
        // http://docs.oracle.com/javase/7/docs/api/java/io/DataOutput.html#writeBoolean%28boolean%29
        $this->buffer .= $value ? "\x01" : "\x00";
    }

    public function writeQVariant($value)
    {
        if ($value instanceof QVariant) {
            $type = $value->getType();
            $value = $value->getValue();
        } else {
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

    public function writeQVariantList(array $list)
    {
        $this->writeUInt(count($list));

        foreach ($list as $value) {
            $this->writeQVariant($value);
        }
    }

    public function writeQVariantMap(array $map)
    {
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
     * @param DateTime|float $timestamp
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

    private function conv($str)
    {
        // transcode UTF-8 to UTF-16 (big endian)
        return mb_convert_encoding($str, 'UTF-16BE', 'UTF-8');
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
