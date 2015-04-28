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
