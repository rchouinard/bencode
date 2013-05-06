<?php

namespace Rych\Bencode\Tests;

use Rych\Bencode\Decoder;

class DecoderTest extends \PHPUnit_Framework_TestCase
{

    public function testCanDecodeString()
    {
        $this->assertEquals(
            'string',
            Decoder::decode('6:string')
        );
    }

    public function testUnterminatedStringThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('6:stri');
    }

    public function testZeroPaddedLengthInStringDefThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('03:foo');
    }

    public function testMissingColonInStringDefThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('3foo');
    }

    public function testCanDecodeInteger()
    {
        $this->assertEquals(
            '42',
            Decoder::decode('i42e')
        );

        $this->assertEquals(
            '-42',
            Decoder::decode('i-42e')
        );

        $this->assertEquals(
            '0',
            Decoder::decode('i0e')
        );
    }

    public function testEmptyIntegerThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('ie');
    }

    public function testNonDigitCharInIntegerDefThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('iae');
    }

    public function testLeadingZeroInIntegerDefThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('i042e');
    }

    public function testUnterminatedIntegerThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('i42');
    }

    public function testCanDecodeList()
    {
        $this->assertEquals(
            array ('foo', 'bar'),
            Decoder::decode('l3:foo3:bare')
        );
    }

    public function testUnterminatedListThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('l3:foo3:bar');
    }

    public function testDecodeDict()
    {
        $this->assertEquals(
            array ('foo' => 'bar'),
            Decoder::decode('d3:foo3:bare')
        );
    }

    public function testUnterminatedDictThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('d3:foo3:bar');
    }

    public function testDuplicateDictKeyThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('d3:foo3:bar3:foo3:bare');
    }

    public function testNonStringDictKeyThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('di42e3:bare');
    }

    public function testUnknownEntityThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('a3:fooe');
    }

    public function testDecodeNonStringThrowsException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode(array ());
    }

    public function testDecodeMultipleTypesOutsideOfListOrDictShouldThrowException()
    {
        $this->setExpectedException('Rych\Bencode\Exception\RuntimeException');
        Decoder::decode('3:foo3:bar');
    }

}
