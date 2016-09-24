<?php

namespace Clue\QDataStream;

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
        $this->writer->writeUInt32BE(count($list));

        foreach ($list as $value) {
            $this->writeQVariant($value);
        }
    }

    public function writeQVariantMap(array $map)
    {
        $this->writer->writeUInt32BE(count($map));

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
