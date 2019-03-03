<?php

use Clue\QDataStream\QVariant;
use Clue\QDataStream\Reader;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    public function testUserTypeMapping()
    {
        $in = "\x00\x00\x00\x7F" . "\x00" . "\x00\x00\x00\x05" . "demo\x00" . "\x00\x00\x00\xFF";

        $map = array(
            'demo' => function (Reader $reader) {
                return $reader->readUInt();
            }
        );

        $reader = new Reader($in, $map);

        $value = $reader->readQVariant();

        $this->assertEquals(255, $value);
    }

    public function testReadNullQTimeIsExactlyMidnight()
    {
        date_default_timezone_set('UTC');

        $midnight = new DateTime('midnight');

        $in = "\x00\x00\x00\x00";
        $reader = new Reader($in);

        $value = $reader->readQTime();

        $this->assertEquals($midnight, $value);
    }

    public function testReadNullQTimeIsExactlyMidnightWithCorrectTimezone()
    {
        date_default_timezone_set('Europe/Berlin');

        $midnight = new DateTime('midnight');

        $in = "\x00\x00\x00\x00";
        $reader = new Reader($in);

        $value = $reader->readQTime();

        $this->assertEquals($midnight, $value);

        $this->assertEquals('Europe/Berlin', $value->getTimezone()->getName());
    }

    public function testReadQVariantWithNullQTimeIsExactlyMidnight()
    {
        date_default_timezone_set('UTC');

        $midnight = new DateTime('midnight');

        $in = "\x00\x00\x00\x0f" . "\x00" . "\x00\x00\x00\x00";
        $reader = new Reader($in);

        $value = $reader->readQVariant();

        $this->assertEquals($midnight, $value);
    }

    /**
     * @expectedException UnderflowException
     */
    public function testReadBeyondLimitThrows()
    {
        $in = "\x00\x00";

        $reader = new Reader($in);
        $reader->readInt();
    }

    /**
     * @expectedException UnderflowException
     */
    public function testReadUintBeyondLimitThrows()
    {
        $in = "\x00\x00";

        $reader = new Reader($in);
        $reader->readUInt();
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testQVariantTypeUnknown()
    {
        $in = "\x00\x00\x00\x00" . "\x00";

        $reader = new Reader($in);
        $reader->readQVariant();
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testQUserTypeUnknown()
    {
        $in = "\x00\x00\x00\x7F" . "\x00" . "\x00\x00\x00\x05" . "demo\x00" . "\x00\x00\x00\xFF";

        $reader = new Reader($in);
        $reader->readQVariant();
    }

    public function testBool()
    {
        $in = "\x00";

        $reader = new Reader($in);
        $this->assertFalse($reader->readBool());
    }

    /**
     * @expectedException UnderflowException
     */
    public function testBoolBeyondLimitThrows()
    {
        $in = "";

        $reader = new Reader($in);
        $this->assertFalse($reader->readBool());
    }

    public function testQCharAscii()
    {
        $in = "\x00o";

        $reader = new Reader($in);
        $this->assertEquals('o', $reader->readQChar());
    }

    public function testQCharWideUmlaut()
    {
        $in = "\x00\xC4";

        $reader = new Reader($in);
        $this->assertEquals('Ä', $reader->readQChar());
    }

    public function testQCharWideCent()
    {
        $in = "\x00\xA2";

        $reader = new Reader($in);
        $this->assertEquals('¢', $reader->readQChar());
    }

    public function testQCharWideEuro()
    {
        $in = "\x20\xAC";

        $reader = new Reader($in);
        $this->assertEquals('€', $reader->readQChar());
    }

    public function provideQString()
    {
        return array(
            'hello-umlaut' => array(
                "\x00\x00\x00\x0a" . "\x00h\x00e\x00l\x00l\x00\xf6",
                'hellö'
            ),
            'with-newline' => array(
                "\x00\x00\x00\x06" . "\x00a\x00\n\00b",
                "a\nb"
            ),
            'wide-euro' => array(
                "\x00\x00\x00\x02" . "\x20\xAC",
                '€'
            ),
            'wide-supplementary-plane' => array(
                "\x00\x00\x00\x04" . "\xd8\x00\xdf\x48",
                '𐍈'
            ),
            'wide-violin' => array(
                "\x00\x00\x00\x04" . "\xD8\x34\xDD\x1E",
                '𝄞'
            )
        );
    }

    /**
     * @dataProvider provideQString
     * @requires extension mbstring
     * @param string $binary
     * @param string $expected
     */
    public function testQString($binary, $expected)
    {
        $reader = new Reader($binary);

        $this->assertEquals($expected, $reader->readQString());
    }

    /**
     * @dataProvider provideQString
     * @param string $binary
     * @param string $expected
     */
    public function testQStringWithoutExtension($binary, $expected)
    {
        $reader = new Reader($binary);

        $ref = new ReflectionProperty($reader, 'supportsExtMbstring');
        $ref->setAccessible(true);
        $ref->setValue($reader, false);

        $this->assertEquals($expected, $reader->readQString());
    }
}
