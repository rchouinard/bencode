<?php
/**
 * Rych Bencode Component
 *
 * @package Rych\Bencode
 * @author Ryan Chouinard <rchouinard@gmail.com>
 * @copyright Copyright (c) 2014, Ryan Chouinard
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 */

namespace Rych\Bencode;

use Rych\Bencode\Exception\RuntimeException;

/**
 * Bencode decoder
 *
 * @package Rych\Bencode
 * @author Ryan Chouinard <rchouinard@gmail.com>
 * @copyright Copyright (c) 2014, Ryan Chouinard
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 */
class Decoder
{

    /**
     * @var string
     */
    private $_source;

    /**
     * @var string
     */
    private $_decodeType;

    /**
     * @var integer
     */
    private $_sourceLength;

    /**
     * @var integer
     */
    private $_offset = 0;

    /**
     * Class constructor
     *
     * @param string $source The bencode string to be decoded.
     * @param string $decodeType currently unused.
     * @return void
     */
    private function __construct($source, $decodeType)
    {
        $this->_source = $source;
        $this->_sourceLength = strlen($this->_source);
        if ($decodeType != Bencode::TYPE_ARRAY && $decodeType != Bencode::TYPE_OBJECT) {
            $decodeType = Bencode::TYPE_ARRAY;
        }
        $this->_decodeType = $decodeType;
    }

    /**
     * Decode a bencode entity into a value
     *
     * @param string $source The bencode string to be decoded.
     * @param string $decodeType currently unused.
     * @return mixed Returns the decoded value.
     * @throws Rych\Bencode\Exception\RuntimeException
     */
    static public function decode($source, $decodeType = Bencode::TYPE_ARRAY)
    {
        if (!is_string($source)) {
            throw new RuntimeException('Argument expected to be a string; Got ' . gettype($source));
        }

        $decoder = new self($source, $decodeType);
        $decoded = $decoder->_decode();

        if ($decoder->_offset != $decoder->_sourceLength) {
            throw new RuntimeException(
                'Found multiple entities outside list or dict definitions'
            );
        }

        return $decoded;
    }

    /**
     * Decode a bencode entity into a value
     *
     * @return mixed Returns the decoded value.
     * @throws Rych\Bencode\Exception\RuntimeException
     */
    private function _decode()
    {
        switch ($this->_getChar()) {

            case 'i':
                ++$this->_offset;
                return $this->_decodeInteger();
                break;

            case 'l':
                ++$this->_offset;
                return $this->_decodeList();
                break;

            case 'd':
                ++$this->_offset;
                return $this->_decodeDict();
                break;

            default:
                if (ctype_digit($this->_getChar())) {
                    return $this->_decodeString();
                }

        }

        throw new RuntimeException('Unknown entity found at offset ' . $this->_offset);
    }

    /**
     * Decode a bencode integer into an integer
     *
     * @return integer Returns the decoded integer.
     * @throws Rych\Bencode\Exception\RuntimeException
     */
    private function _decodeInteger()
    {
        $offsetOfE = strpos($this->_source, 'e', $this->_offset);
        if (false === $offsetOfE) {
            throw new RuntimeException('Unterminated integer entity at offset ' . $this->_offset);
        }

        $currentOffset = $this->_offset;
        if ('-' == $this->_getChar($currentOffset)) {
            ++$currentOffset;
        }

        if ($offsetOfE === $currentOffset) {
            throw new RuntimeException('Empty integer entity at offset ' . $this->_offset);
        }

        while ($currentOffset < $offsetOfE) {
            if (!ctype_digit($this->_getChar($currentOffset))) {
                throw new RuntimeException('Non-numeric character found in integer entity at offset ' . $this->_offset);
            }
            ++$currentOffset;
        }

        $value = substr($this->_source, $this->_offset, $offsetOfE - $this->_offset);

        // One last check to make sure zero-padded integers don't slip by, as
        // they're not allowed per bencode specification.
        $absoluteValue = (string) abs($value);
        if (1 < strlen($absoluteValue) && '0' == $value[0]) {
            // TODO: Could probably just trigger a warning here
            throw new RuntimeException('Illegal zero-padding found in integer entity at offset ' . $this->_offset);
        }

        $this->_offset = $offsetOfE + 1;

        // The +0 auto-casts the chunk to either an integer or a float(in cases
        // where an integer would overrun the max limits of integer types)
        return $value + 0;
    }

    /**
     * Decode a bencode string into a string
     *
     * @return string Returns the decoded string.
     * @throws Rych\Bencode\Exception\RuntimeException
     */
    private function _decodeString()
    {
        if ('0' === $this->_getChar() && ':' != $this->_getChar($this->_offset + 1)) {
            // TODO: Trigger a warning instead?
            throw new RuntimeException('Illegal zero-padding in string entity length declaration at offset ' . $this->_offset);
        }

        $offsetOfColon = strpos($this->_source, ':', $this->_offset);
        if (false === $offsetOfColon) {
            throw new RuntimeException('Unterminated string entity at offset ' . $this->_offset);
        }

        $contentLength = (int) substr($this->_source, $this->_offset, $offsetOfColon);
        if (($contentLength + $offsetOfColon + 1) > $this->_sourceLength) {
            throw new RuntimeException('Unexpected end of string entity at offset ' . $this->_offset);
        }

        $value = substr($this->_source, $offsetOfColon + 1, $contentLength);
        $this->_offset = $offsetOfColon + $contentLength + 1;

        return $value;
    }

    /**
     * Decode a bencode list into a numeric array
     *
     * @return array Returns the decoded array.
     * @throws Rych\Bencode\Exception\RuntimeException
     */
    private function _decodeList()
    {
        $list = array ();
        $terminated = false;
        $listOffset = $this->_offset;

        while (false !== $this->_getChar()) {
            if ('e' == $this->_getChar()) {
                $terminated = true;
                break;
            }

            $list[] = $this->_decode();
        }

        if (!$terminated && false === $this->_getChar()) {
            throw new RuntimeException('Unterminated list definition at offset ' . $listOffset);
        }

        ++$this->_offset;

        return $list;
    }

    /**
     * Decode a bencode dictionary into an associative array
     *
     * @return array Returns the decoded array.
     * @throws Rych\Bencode\Exception\RuntimeException
     */
    private function _decodeDict()
    {
        $dict = array ();
        $terminated = false;
        $dictOffset = $this->_offset;

        while (false !== $this->_getChar()) {
            if ('e' == $this->_getChar()) {
                $terminated = true;
                break;
            }

            $keyOffset = $this->_offset;
            if (!ctype_digit($this->_getChar())) {
                throw new RuntimeException('Invalid dictionary key at offset ' . $keyOffset);
            }

            $key = $this->_decodeString();
            if (isset ($dict[$key])) {
                // TODO: This could probably just trigger a warning...
                throw new RuntimeException('Duplicate dictionary key at offset ' . $keyOffset);
            }

            $dict[$key] = $this->_decode();
        }

        if (!$terminated && false === $this->_getChar()) {
            throw new RuntimeException('Unterminated dictionary definition at offset ' . $dictOffset);
        }

        ++$this->_offset;

        return $dict;
    }

    /**
     * Fetch the character at the specified source offset
     *
     * If not offset is provided, the current offset is used.
     *
     * @param integer $offset the offset to retrieve from the source string.
     * @return string Returns the character found at the specified offset. If
     *     the specified offset is out of range, false is returned.
     */
    private function _getChar($offset = null)
    {
        if (null === $offset) {
            $offset = $this->_offset;
        }

        if (empty ($this->_source) || $this->_offset >= $this->_sourceLength) {
            return false;
        }

        return $this->_source[$offset];
    }

}
