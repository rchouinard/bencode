<?php

namespace Rych\Bencode;

use Rych\Bencode;
use Rych\Bencode\Exception\RuntimeException;

require_once 'Rych/Bencode.php';
require_once 'Rych/Bencode/Exception/RuntimeException.php';

class Decoder
{

    protected $_source;
    protected $_decodeType;
    protected $_sourceLength;
    protected $_offset = 0;

    protected function __construct($source, $decodeType)
    {
        $this->_source = $source;
        $this->_sourceLength = strlen($this->_source);

        $decodeTypes = array (
            Bencode::TYPE_ARRAY,
            Bencode::TYPE_OBJECT
        );
        if (!in_array($decodeType, $decodeTypes)) {
            $decodeType = Bencode::TYPE_ARRAY;
        }

        $this->_decodeType = $decodeType;
    }

    static public function decode($source, $decodeType = Bencode::TYPE_ARRAY)
    {
        if (!is_string($source)) {
            throw new RuntimeException(
                'Argument expected to be a string; Got ' . gettype($source)
            );
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
     * Decode $_source from the current offset.
     *
     * @return  mixed
     */
    protected function _decode()
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
                if ($this->_isDigit($this->_getChar())) {
                    return $this->_decodeString();
                }

        }

        throw new RuntimeException(
            "Unknown entity found at offset {$this->_offset}"
        );
    }

    protected function _decodeInteger()
    {
        // Calculate the offset of the end of the integer definition
        $offsetOfE = strpos($this->_source, 'e', $this->_offset);
        if (false === $offsetOfE) {
            throw new RuntimeException('Unterminated integer entity');
        }

        // Local offset record
        $currentOffset = $this->_offset;

        // Skip first character if it is a '-' for validation
        if ('-' == $this->_getChar($currentOffset)) {
            ++$currentOffset;
        }

        // Check to see if the entity is empty ("ie" or "i-e")
        if ($offsetOfE === $currentOffset) {
            throw new RuntimeException('Empty integer entity');
        }

        // Check each character to make sure it's a digit
        for (; $currentOffset < $offsetOfE; ++$currentOffset) {
            if (!$this->_isDigit($this->_getChar($currentOffset))) {
                throw new RuntimeException(
                    'Non-numeric character found in integer entity'
                );
            }
        }

        // Pull the whole record from the encoded source
        $value = substr(
            $this->_source,
            $this->_offset,
            $offsetOfE - $this->_offset
        );

        // One last check to make sure zero-padded integers don't slip by, as
        // they're not allowed per bencode specification.
        $absoluteValue = trim($value, '-');
        if (1 < strlen($absoluteValue) && '0' == substr($absoluteValue, 0, 1)) {
            // TODO: Could probably just trigger a warning here
            throw new RuntimeException(
                'Leading zero found in integer entity'
            );
        }

        // Advance the global offset
        $this->_offset = $offsetOfE + 1;

        // The +0 auto-casts the chunk to either an integer or a float (in cases
        // where an integer would overrun the max limits of integer types)
        return $value + 0;
    }

    protected function _decodeList()
    {
        $list = array ();
        $terminated = false;

        // Loop through and decode each item
        while (false !== $this->_getChar()) {

            if ('e' == $this->_getChar()) {
                $terminated = true;
                break;
            }

            $list[] = $this->_decode();
        }

        // Check if we ran out of characters, or found the "e"
        if (!$terminated && false === $this->_getChar()) {
            throw new RuntimeException('Unterminated list definition');
        }

        // Advance the global offset
        ++$this->_offset;

        return $list;
    }

    protected function _decodeDict()
    {
        $dict = array ();
        $terminated = false;

        while (false !== $this->_getChar()) {

            if ('e' == $this->_getChar()) {
                $terminated = true;
                break;
            }

            // A dict key must be a string, and all strings are formatted
            // <length>:<content>, so we can be reasonably sure that if the
            // current offset is not a digit, it's not a string definition.
            if (!$this->_isDigit($this->_getChar())) {
                throw new RuntimeException('Invalid dictionary key');
            }

            $key = $this->_decodeString();

            // Check for duplicate keys
            if (isset ($dict[$key])) {
                // TODO: This could probably just trigger a warning...
                throw new RuntimeException('Duplicate dictionary key');
            }

            $dict[$key] = $this->_decode();
        }

        // Check if we ran out of characters before we found the 'e'
        if (!$terminated && false === $this->_getChar()) {
            throw new RuntimeException(
                'Unterminated dictionary definition'
            );
        }

        // Advance the global offset
        ++$this->_offset;

        return $dict;
    }

    protected function _decodeString()
    {
        // Check for invalid content length declarations
        if ('0' === $this->_getChar() && ':' != $this->_getChar($this->_offset + 1)) {
            // TODO: Trigger a warning instead?
            throw new RuntimeException(
                'Found leading zero in string entity length declaration'
            );
        }

        // Find the colon
        // *points to belly* -- FOUND IT! :-D
        $offsetOfColon = strpos($this->_source, ':', $this->_offset);
        if (false === $offsetOfColon) {
            throw new RuntimeException('Unterminated string entity');
        }

        // Find the length of the string
        $contentLength = (int) substr(
            $this->_source,
            $this->_offset,
            $offsetOfColon
        );

        // Check if we have the entire string, or if our source is truncated
        if (($contentLength + $offsetOfColon + 1) > $this->_sourceLength) {
            throw new RuntimeException('Unexpected end of string');
        }

        // Pull the string from the source
        $value = substr($this->_source, $offsetOfColon + 1, $contentLength);

        // Advance the global offset
        $this->_offset = $offsetOfColon + $contentLength + 1;

        return $value;
    }

    protected function _getChar($offset = null)
    {
        if (null === $offset) {
            $offset = $this->_offset;
        }

        if (empty ($this->_source) || $this->_offset >= $this->_sourceLength) {
            return false;
        }

        return $this->_source{$offset};
    }

    protected function _isDigit($char)
    {
        return in_array(
            $char,
            array ('0', '1', '2', '3', '4', '5', '6', '7', '8', '9')
        );
    }

}
