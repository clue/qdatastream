<?php

namespace Clue\QDataStream;

class Types
{
    // https://github.com/sandsmark/QuasselDroid/blob/master/QuasselDroid/src/main/java/com/iskrembilen/quasseldroid/qtcomm/QMetaType.java
    const TYPE_BOOL = 1;
    const TYPE_INT = 2;
    const TYPE_UINT = 3;
    const TYPE_VARIANT_MAP = 8;
    const TYPE_VARIANT_LIST = 9;
    const TYPE_STRING = 10;
    const TYPE_STRING_LIST = 11;
    const TYPE_BYTE_ARRAY = 12;
    const TYPE_SHORT = 130;
    const TYPE_USHORT = 133;

    public function getTypeByValue($value)
    {
        if (is_int($value)) {
            return self::TYPE_INT;
        } elseif (is_string($value)) {
            return self::TYPE_STRING;
        } elseif (is_bool($value)) {
            return self::TYPE_BOOL;
        } elseif ($this->isList($value)) {
            return self::TYPE_VARIANT_LIST;
        } elseif ($this->isMap($value)) {
            return self::TYPE_VARIANT_MAP;
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
            Types::TYPE_VARIANT_MAP => 'VariantMap',
            Types::TYPE_VARIANT_LIST => 'VariantList',
            Types::TYPE_STRING => 'String',
            Types::TYPE_STRING_LIST => 'StringList',
            Types::TYPE_BYTE_ARRAY => 'ByteArray',
            Types::TYPE_SHORT => 'Short',
            Types::TYPE_USHORT => 'UShort',
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
