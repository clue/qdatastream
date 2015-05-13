<?php

namespace Clue\QDataStream;

class QVariant
{
    private $value;
    private $type;

    /**
     * Create a new QVariant with the given explicit type
     *
     * You can use this class to explicitly define the types of your data instead
     * of relying on automatic guessing.
     *
     * Technically, Qt would represent a QVariant with a custom QUserType as two
     * nested objects. For ease of use, this library instead uses string identifiers
     * on the (outer) QVariant to mark it as a QUserType.
     *
     * @param mixed      $value the variant's native value to transport
     * @param int|string $type  type constant (see Types) for built-in types or a string for custom UserTypes
     */
    public function __construct($value, $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getType()
    {
        return $this->type;
    }
}
