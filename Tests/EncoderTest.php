<?php

namespace Rych\Bencode\Tests;

use Rych\Bencode\Encoder;

class EncoderTest extends \PHPUnit_Framework_TestCase
{

    public function testEncodeString()
    {
        $this->assertEquals(
            '6:string',
            Encoder::encode('string')
        );
    }

    public function testEncodeInteger()
    {
        $this->assertEquals(
            'i42e',
            Encoder::encode(42)
        );
        $this->assertEquals(
            'i-42e',
            Encoder::encode(-42)
        );
        $this->assertEquals(
            'i0e',
            Encoder::encode(0)
        );
    }

    public function testEncodeList()
    {
        $this->assertEquals(
            'l3:foo3:bare',
            Encoder::encode(array ('foo', 'bar'))
        );
    }

    public function testEncodeDict()
    {
        $this->assertEquals(
            'd3:foo3:bare',
            Encoder::encode(array ('foo' => 'bar'))
        );
    }

    public function testCanEncodeObjectWithoutToArray()
    {
        $object = new \stdClass;
        $object->string = 'foo';
        $object->integer = 42;

        $this->assertEquals(
            'd7:integeri42e6:string3:fooe',
            Encoder::encode($object)
        );
    }

    /**
     * Test regrassion of issue #1
     *
     * Encoder should treat numeric strings as strings rather than
     * integers.
     */
    public function testIssue1Regrassion()
    {
        $data = array (
            "Numeric string value" => "1",
            "1" => "Numeric string key",
        );

        $this->assertEquals(
            "d20:Numeric string value1:11:118:Numeric string keye",
            Encoder::encode($data)
        );
    }

}
