<?php

use Clue\QDataStream\Types;

class TypesTest extends TestCase
{
    public function testList()
    {
        $this->assertTrue(Types::isList(array()));
        $this->assertTrue(Types::isList(array(1, 'hello')));

        $this->assertFalse(Types::isList(true));
        $this->assertFalse(Types::isList(array('key' => 'value')));
        $this->assertFalse(Types::isList(array(1 => 'first')));
        $this->assertFalse(Types::isList(array(1 => 'world', 0 => 'hello')));
    }

    public function testMap()
    {
        $this->assertTrue(Types::isMap(array()));
        $this->assertTrue(Types::isMap(array('key' => 'value')));
        $this->assertTrue(Types::isMap(array(1 => 'first')));
        $this->assertTrue(Types::isMap(array(1 => 'world', 0 => 'hello')));

        $this->assertFalse(Types::isMap(true));
        $this->assertFalse(Types::isMap(array(1, 'hello')));
    }

    public function testTypeDateTime()
    {
        $this->assertEquals(Types::TYPE_QDATETIME, Types::getTypeByValue(new \DateTime()));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidType()
    {
        Types::getNameByType(123456);
    }
}
