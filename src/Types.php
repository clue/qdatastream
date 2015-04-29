<?php

namespace Clue\QDataStream;

class Types
{
    // https://github.com/sandsmark/QuasselDroid/blob/master/QuasselDroid/src/main/java/com/iskrembilen/quasseldroid/qtcomm/QMetaType.java
    const TYPE_BOOL = 1;
    const TYPE_INT32 = 2;
    const TYPE_UINT32 = 3;
    const TYPE_VARIANT_MAP = 8;
    const TYPE_VARIANT_LIST = 9;
    const TYPE_STRING = 10;
    const TYPE_STRING_LIST = 11;
    const TYPE_BYTE_ARRAY = 12;
    const TYPE_SHORT = 130;
    const TYPE_USHORT = 133;

    public static function getTypeByValue($value)
    {
        if (is_int($value)) {
            return self::TYPE_INT32;
        } elseif (is_string($value)) {
            return self::TYPE_STRING;
        } elseif (is_bool($value)) {
            return self::TYPE_BOOL;
        } elseif (self::isList($value)) {
            return self::TYPE_VARIANT_LIST;
        } elseif (self::isMap($value)) {
            return self::TYPE_VARIANT_MAP;
        } else {
            throw new \InvalidArgumentException('Can not guess variant type for type "' . gettype($value) . '"');
        }
    }

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

    public static function isMap($array)
    {
        return ($array === array() || (is_array($array) && !self::isList($array)));
    }
}
