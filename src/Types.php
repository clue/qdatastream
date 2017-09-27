<?php

namespace Clue\QDataStream;

final class Types
{
    // https://github.com/sandsmark/QuasselDroid/blob/master/QuasselDroid/src/main/java/com/iskrembilen/quasseldroid/qtcomm/QMetaType.java
    const TYPE_BOOL = 1;
    const TYPE_INT = 2;
    const TYPE_UINT = 3;
    const TYPE_QCHAR = 7;
    const TYPE_QVARIANT_MAP = 8;
    const TYPE_QVARIANT_LIST = 9;
    const TYPE_QSTRING = 10;
    const TYPE_QSTRING_LIST = 11;
    const TYPE_QBYTE_ARRAY = 12;
    const TYPE_QTIME = 15;
    const TYPE_QDATETIME = 16;
    const TYPE_QUSER_TYPE = 127;
    const TYPE_SHORT = 130;
    const TYPE_CHAR = 131;
    const TYPE_USHORT = 133;
    const TYPE_UCHAR = 134;

    /**
     * Tries to guess the type constant based on the data type of the given value
     *
     * @param mixed $value
     * @return int see TYPE_* constants
     * @throws \InvalidArgumentException if type can not be guessed
     */
    public static function getTypeByValue($value)
    {
        if (is_int($value)) {
            return self::TYPE_INT;
        } elseif (is_string($value)) {
            return self::TYPE_QSTRING;
        } elseif (is_bool($value)) {
            return self::TYPE_BOOL;
        } elseif (self::isList($value)) {
            return self::TYPE_QVARIANT_LIST;
        } elseif (self::isMap($value)) {
            return self::TYPE_QVARIANT_MAP;
        } elseif ($value instanceof \DateTime) {
            return self::TYPE_QDATETIME;
        } else {
            throw new \InvalidArgumentException('Can not guess variant type for type "' . gettype($value) . '"');
        }
    }

    /**
     * Returns the type name string for the given type constant
     *
     * @param int $type
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getNameByType($type)
    {
        static $map = array(
            self::TYPE_BOOL => 'Bool',
            self::TYPE_INT => 'Int',
            self::TYPE_UINT => 'UInt',
            self::TYPE_QCHAR => 'QChar',
            self::TYPE_QVARIANT_MAP => 'QVariantMap',
            self::TYPE_QVARIANT_LIST => 'QVariantList',
            self::TYPE_QSTRING => 'QString',
            self::TYPE_QSTRING_LIST => 'QStringList',
            self::TYPE_QBYTE_ARRAY => 'QByteArray',
            self::TYPE_QTIME => 'QTime',
            self::TYPE_QDATETIME => 'QDateTime',
            self::TYPE_QUSER_TYPE => 'QUserType',
            self::TYPE_SHORT => 'Short',
            self::TYPE_CHAR => 'Char',
            self::TYPE_USHORT => 'UShort',
            self::TYPE_UCHAR => 'UChar',
        );

        if (!isset($map[$type])) {
            throw new \InvalidArgumentException('Invalid/unknown variant type (' . $type . ')');
        }

        return $map[$type];
    }

    /**
     * Checks whether the given argument is a list (vector array)
     *
     * An empty array is considered both a list and a map.
     *
     * @param mixed $array
     * @return bool
     */
    public static function isList($array)
    {
        if (!is_array($array)) {
            return false;
        }

        $expected = 0;
        foreach ($array as $key => $unused) {
            if ($key !== $expected++) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks whether the given argument is a map (hash map / assoc array)
     *
     * An empty array is considered both a list and a map.
     *
     * @param array $array
     * @return boolean
     */
    public static function isMap($array)
    {
        return ($array === array() || (is_array($array) && !self::isList($array)));
    }
}
