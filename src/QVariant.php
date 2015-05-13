<?php

namespace Clue\QDataStream;

class QVariant
{
    private $value;
    private $type;

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
