<?php
/**
 * Rych Bencode
 *
 * Bencode serializer for PHP 5.3+.
 *
 * @package   Rych\Bencode
 * @copyright Copyright (c) 2014, Ryan Chouinard
 * @author    Ryan Chouinard <rchouinard@gmail.com>
 * @license   MIT License - http://www.opensource.org/licenses/mit-license.php
 */

namespace Rych\Bencode;

use PHPUnit_Framework_TestCase as TestCase;
use Rych\Bencode\Exception\RuntimeException;

/**
 * Bencode decoder test
 */
class DecoderTest extends TestCase
{

    /**
     * Test that strings are properly decoded
     *
     * @test
     */
    public function testCanDecodeString()
    {
        $this->assertEquals("string", Decoder::decode('6:string'));
    }

    /**
     * Test that an unterminated string triggers an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testUnterminatedStringThrowsException()
    {
        Decoder::decode("6:stri");
    }

    /**
     * Test that a zero-padded string length triggers an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testZeroPaddedLengthInStringThrowsException()
    {
        Decoder::decode("03:foo");
    }

    /**
     * Test that a missing colon triggers an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testMissingColonInStringThrowsException()
    {
        Decoder::decode("3foo");
    }

    /**
     * Test that integers are properly decoded
     *
     * @test
     */
    public function testCanDecodeInteger()
    {
        $this->assertEquals("42", Decoder::decode("i42e"));
        $this->assertEquals("-42", Decoder::decode("i-42e"));
        $this->assertEquals("0", Decoder::decode("i0e"));
    }

    /**
     * Test that an empty integer triggers an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testEmptyIntegerThrowsException()
    {
        Decoder::decode("ie");
    }

    /**
     * Test that a non-digit in an integer trigger an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testNonDigitCharInIntegerThrowsException()
    {
        Decoder::decode("iae");
    }

    /**
     * Test that a zero-padded integer triggers an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testLeadingZeroInIntegerThrowsException()
    {
        Decoder::decode("i042e");
    }

    /**
     * Test that an unterminated integer triggers an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testUnterminatedIntegerThrowsException()
    {
        Decoder::decode("i42");
    }

    /**
     * That that lists are properly decoded
     *
     * @test
     */
    public function testCanDecodeList()
    {
        $this->assertEquals(array("foo", "bar"), Decoder::decode("l3:foo3:bare"));
    }

    /**
     * Test that an unterminated lists triggers an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testUnterminatedListThrowsException()
    {
        Decoder::decode("l3:foo3:bar");
    }

    /**
     * Test that dictionaries are properly decoded
     *
     * @test
     */
    public function testDecodeDictionary()
    {
        $this->assertEquals(array("foo" => "bar"), Decoder::decode("d3:foo3:bare"));
    }

    /**
     * Test that an unterminated dictionary triggers an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testUnterminatedDictThrowsException()
    {
        Decoder::decode("d3:foo3:bar");
    }

    /**
     * Test that a duplicate dictionary key triggers an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testDuplicateDictionaryKeyThrowsException()
    {
        Decoder::decode("d3:foo3:bar3:foo3:bare");
    }

    /**
     * Test that a non-string dictionary key triggers an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testNonStringDictKeyThrowsException()
    {
        Decoder::decode("di42e3:bare");
    }

    /**
     * Test that an unknown entity triggers an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testUnknownEntityThrowsException()
    {
        Decoder::decode("a3:fooe");
    }

    /**
     * Test that attempting to decode a non-string triggers an exception
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testDecodeNonStringThrowsException()
    {
        Decoder::decode(array());
    }

    /**
     * Test that multiple entities must be in a list or dictionary
     *
     * @test
     * @expectedException RuntimeException
     */
    public function testDecodeMultipleTypesOutsideOfListOrDictShouldThrowException()
    {
        Decoder::decode("3:foo3:bar");
    }

}
