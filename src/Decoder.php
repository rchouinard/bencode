<?php

namespace Rych\Bencode;

use Rych\Bencode\Exception\RuntimeException;

/**
 * Bencode decoder class
 *
 * Decodes bencode encoded strings.
 */
class Decoder
{
    /**
     * The encoded source string
     *
     * @var string
     */
    private $source;

    /**
     * The length of the encoded source string
     *
     * @var integer
     */
    private $sourceLength;

    /**
     * The return type for the decoded value
     *
     * @var Bencode::TYPE_ARRAY|Bencode::TYPE_OBJECT
     */
    private $decodeType;

    /**
     * The current offset of the parser.
     *
     * @var integer
     */
    private $offset = 0;

    /**
     * Decoder constructor
     *
     * @param  string  $source The bencode encoded source.
     * @param  string  $decodeType Flag used to indicate whether the decoded
     *   value should be returned as an object or an array.
     * @return void
     */
    private function __construct($source, $decodeType)
    {
        $this->source = $source;
        $this->sourceLength = strlen($this->source);
        $this->decodeType = in_array($decodeType, array(Bencode::TYPE_ARRAY, Bencode::TYPE_OBJECT))
            ? $decodeType
            : Bencode::TYPE_ARRAY;
    }

    /**
     * Decode a bencode encoded string
     *
     * @param  string $source The string to decode.
     * @param  string $decodeType Flag used to indicate whether the decoded
     *   value should be returned as an object or an array.
     * @return mixed   Returns the appropriate data type for the decoded data.
     * @throws RuntimeException
     */
    public static function decode($source, $decodeType = Bencode::TYPE_ARRAY)
    {
        if (!is_string($source)) {
            throw new RuntimeException("Argument expected to be a string; Got " . gettype($source));
        }

        $decoder = new self($source, $decodeType);
        $decoded = $decoder->doDecode();

        if ($decoder->offset != $decoder->sourceLength) {
            throw new RuntimeException("Found multiple entities outside list or dict definitions");
        }

        return $decoded;
    }

    /**
     * Iterate over encoded entities in the source string and decode them
     *
     * @return mixed   Returns the decoded value.
     * @throws RuntimeException
     */
    private function doDecode()
    {
        switch ($this->getChar()) {
            case "i":
                ++$this->offset;
                return $this->decodeInteger();

            case "l":
                ++$this->offset;
                return $this->decodeList();

            case "d":
                ++$this->offset;
                return $this->decodeDict();

            default:
                if (ctype_digit($this->getChar())) {
                    return $this->decodeString();
                }
        }

        throw new RuntimeException("Unknown entity found at offset $this->offset");
    }

    /**
     * Decode a bencode encoded integer
     *
     * @return integer Returns the decoded integer.
     * @throws RuntimeException
     */
    private function decodeInteger()
    {
        $offsetOfE = strpos($this->source, "e", $this->offset);
        if (false === $offsetOfE) {
            throw new RuntimeException("Unterminated integer entity at offset $this->offset");
        }

        $currentOffset = $this->offset;
        if ("-" == $this->getChar($currentOffset)) {
            ++$currentOffset;
        }

        if ($offsetOfE === $currentOffset) {
            throw new RuntimeException("Empty integer entity at offset $this->offset");
        }

        while ($currentOffset < $offsetOfE) {
            if (!ctype_digit($this->getChar($currentOffset))) {
                throw new RuntimeException("Non-numeric character found in integer entity at offset $this->offset");
            }
            ++$currentOffset;
        }

        $value = substr($this->source, $this->offset, $offsetOfE - $this->offset);

        // One last check to make sure zero-padded integers don't slip by, as
        // they're not allowed per bencode specification.
        $absoluteValue = (string) abs($value);
        if (1 < strlen($absoluteValue) && "0" == $value[0]) {
            throw new RuntimeException("Illegal zero-padding found in integer entity at offset $this->offset");
        }

        $this->offset = $offsetOfE + 1;

        // The +0 auto-casts the chunk to either an integer or a float(in cases
        // where an integer would overrun the max limits of integer types)
        return $value + 0;
    }

    /**
     * Decode a bencode encoded string
     *
     * @return string  Returns the decoded string.
     * @throws RuntimeException
     */
    private function decodeString()
    {
        if ("0" === $this->getChar() && ":" != $this->getChar($this->offset + 1)) {
            throw new RuntimeException(
                "Illegal zero-padding in string entity length declaration at offset $this->offset"
            );
        }

        $offsetOfColon = strpos($this->source, ":", $this->offset);
        if (false === $offsetOfColon) {
            throw new RuntimeException("Unterminated string entity at offset $this->offset");
        }

        $contentLength = (int) substr($this->source, $this->offset, $offsetOfColon);
        if (($contentLength + $offsetOfColon + 1) > $this->sourceLength) {
            throw new RuntimeException("Unexpected end of string entity at offset $this->offset");
        }

        $value = substr($this->source, $offsetOfColon + 1, $contentLength);
        $this->offset = $offsetOfColon + $contentLength + 1;

        return $value;
    }

    /**
     * Decode a bencode encoded list
     *
     * @return array   Returns the decoded array.
     * @throws RuntimeException
     */
    private function decodeList()
    {
        $list = array();
        $terminated = false;
        $listOffset = $this->offset;

        while (false !== $this->getChar()) {
            if ("e" == $this->getChar()) {
                $terminated = true;
                break;
            }

            $list[] = $this->doDecode();
        }

        if (!$terminated && false === $this->getChar()) {
            throw new RuntimeException("Unterminated list definition at offset $listOffset");
        }

        $this->offset++;

        return $list;
    }

    /**
     * Decode a bencode encoded dictionary
     *
     * @return array   Returns the decoded array.
     * @throws RuntimeException
     */
    private function decodeDict()
    {
        $dict = array();
        $terminated = false;
        $dictOffset = $this->offset;

        while (false !== $this->getChar()) {
            if ("e" == $this->getChar()) {
                $terminated = true;
                break;
            }

            $keyOffset = $this->offset;
            if (!ctype_digit($this->getChar())) {
                throw new RuntimeException("Invalid dictionary key at offset $keyOffset");
            }

            $key = $this->decodeString();
            if (isset($dict[$key])) {
                throw new RuntimeException("Duplicate dictionary key at offset $keyOffset");
            }

            $dict[$key] = $this->doDecode();
        }

        if (!$terminated && false === $this->getChar()) {
            throw new RuntimeException("Unterminated dictionary definition at offset $dictOffset");
        }

        $this->offset++;

        return $dict;
    }

    /**
     * Fetch the character at the specified source offset
     *
     * If offset is not provided, the current offset is used.
     *
     * @param  integer $offset The offset to retrieve from the source string.
     * @return string|false Returns the character found at the specified
     *   offset. If the specified offset is out of range, FALSE is returned.
     */
    private function getChar($offset = null)
    {
        if (null === $offset) {
            $offset = $this->offset;
        }

        if (empty($this->source) || $this->offset >= $this->sourceLength) {
            return false;
        }

        return $this->source[$offset];
    }
}
