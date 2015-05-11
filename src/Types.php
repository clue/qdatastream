<?php

namespace Clue\QDataStream;

class Types
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

    public function getTypeByValue($value)
    {
        if (is_int($value)) {
            return self::TYPE_INT;
        } elseif (is_string($value)) {
            return self::TYPE_QSTRING;
        } elseif (is_bool($value)) {
            return self::TYPE_BOOL;
        } elseif ($this->isList($value)) {
            return self::TYPE_QVARIANT_LIST;
        } elseif ($this->isMap($value)) {
            return self::TYPE_QVARIANT_MAP;
        } elseif ($value instanceof \DateTime) {
            return self::TYPE_QDATETIME;
        } else {
            throw new \InvalidArgumentException('Can not guess variant type for type "' . gettype($value) . '"');
        }
    }

    public function getNameByType($type)
    {
        static $map = array(
            Types::TYPE_BOOL => 'Bool',
            Types::TYPE_INT => 'Int',
            Types::TYPE_UINT => 'UInt',
            TYPES::TYPE_QCHAR => 'QChar',
            Types::TYPE_QVARIANT_MAP => 'QVariantMap',
            Types::TYPE_QVARIANT_LIST => 'QVariantList',
            Types::TYPE_QSTRING => 'QString',
            Types::TYPE_QSTRING_LIST => 'QStringList',
            Types::TYPE_QBYTE_ARRAY => 'QByteArray',
            Types::TYPE_QTIME => 'QTime',
            Types::TYPE_QDATETIME => 'QDateTime',
            Types::TYPE_QUSER_TYPE => 'QUserType',
            Types::TYPE_SHORT => 'Short',
            Types::TYPE_CHAR => 'Char',
            Types::TYPE_USHORT => 'UShort',
            Types::TYPE_UCHAR => 'UChar',
        );

        if (!isset($map[$type])) {
            throw new \InvalidArgumentException('Invalid/unknown variant type (' . $type . ')');
        }

        return $map[$type];
    }

    public function isList($array)
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

    public function isMap($array)
    {
        return ($array === array() || (is_array($array) && !$this->isList($array)));
    }
}
