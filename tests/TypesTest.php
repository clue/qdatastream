<?php

use Clue\QDataStream\Types;

class TypesTest extends TestCase
{
    public function setUp()
    {
        $this->types = new Types();
    }

    public function testList()
    {
        $this->assertTrue($this->types->isList(array()));
        $this->assertTrue($this->types->isList(array(1, 'hello')));

        $this->assertFalse($this->types->isList(true));
        $this->assertFalse($this->types->isList(array('key' => 'value')));
        $this->assertFalse($this->types->isList(array(1 => 'first')));
        $this->assertFalse($this->types->isList(array(1 => 'world', 0 => 'hello')));
    }

    public function testMap()
    {
        $this->assertTrue($this->types->isMap(array()));
        $this->assertTrue($this->types->isMap(array('key' => 'value')));
        $this->assertTrue($this->types->isMap(array(1 => 'first')));
        $this->assertTrue($this->types->isMap(array(1 => 'world', 0 => 'hello')));

        $this->assertFalse($this->types->isMap(true));
        $this->assertFalse($this->types->isMap(array(1, 'hello')));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidType()
    {
        $this->types->getNameByType(123456);
    }
}
