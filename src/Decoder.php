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
    private $source;

    /**
     * @var string
     */
    private $decodeType;

    /**
     * @var integer
     */
    private $sourceLength;

    /**
     * @var integer
     */
    private $offset = 0;

    /**
     * Class constructor
     *
     * @param string $source The bencode string to be decoded.
     * @param string $decodeType currently unused.
     * @return void
     */
    private function __construct($source, $decodeType)
    {
        $this->source = $source;
        $this->sourceLength = strlen($this->source);
        if ($decodeType != Bencode::TYPE_ARRAY && $decodeType != Bencode::TYPE_OBJECT) {
            $decodeType = Bencode::TYPE_ARRAY;
        }
        $this->decodeType = $decodeType;
    }

    /**
     * Decode a bencode entity into a value
     *
     * @param string $source The bencode string to be decoded.
     * @param string $decodeType currently unused.
     * @return mixed Returns the decoded value.
     * @throws \Rych\Bencode\Exception\RuntimeException
     */
    public static function decode($source, $decodeType = Bencode::TYPE_ARRAY)
    {
        if (!is_string($source)) {
            throw new RuntimeException('Argument expected to be a string; Got ' . gettype($source));
        }

        $decoder = new self($source, $decodeType);
        $decoded = $decoder->doDecode();

        if ($decoder->offset != $decoder->sourceLength) {
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
     * @throws \Rych\Bencode\Exception\RuntimeException
     */
    private function doDecode()
    {
        switch ($this->getChar()) {

            case 'i':
                ++$this->offset;
                return $this->decodeInteger();
                break;

            case 'l':
                ++$this->offset;
                return $this->decodeList();
                break;

            case 'd':
                ++$this->offset;
                return $this->decodeDict();
                break;

            default:
                if (ctype_digit($this->getChar())) {
                    return $this->decodeString();
                }

        }

        throw new RuntimeException('Unknown entity found at offset ' . $this->offset);
    }

    /**
     * Decode a bencode integer into an integer
     *
     * @return integer Returns the decoded integer.
     * @throws \Rych\Bencode\Exception\RuntimeException
     */
    private function decodeInteger()
    {
        $offsetOfE = strpos($this->source, 'e', $this->offset);
        if (false === $offsetOfE) {
            throw new RuntimeException('Unterminated integer entity at offset ' . $this->offset);
        }

        $currentOffset = $this->offset;
        if ('-' == $this->getChar($currentOffset)) {
            ++$currentOffset;
        }

        if ($offsetOfE === $currentOffset) {
            throw new RuntimeException('Empty integer entity at offset ' . $this->offset);
        }

        while ($currentOffset < $offsetOfE) {
            if (!ctype_digit($this->getChar($currentOffset))) {
                throw new RuntimeException('Non-numeric character found in integer entity at offset ' . $this->offset);
            }
            ++$currentOffset;
        }

        $value = substr($this->source, $this->offset, $offsetOfE - $this->offset);

        // One last check to make sure zero-padded integers don't slip by, as
        // they're not allowed per bencode specification.
        $absoluteValue = (string) abs($value);
        if (1 < strlen($absoluteValue) && '0' == $value[0]) {
            throw new RuntimeException('Illegal zero-padding found in integer entity at offset ' . $this->offset);
        }

        $this->offset = $offsetOfE + 1;

        // The +0 auto-casts the chunk to either an integer or a float(in cases
        // where an integer would overrun the max limits of integer types)
        return $value + 0;
    }

    /**
     * Decode a bencode string into a string
     *
     * @return string Returns the decoded string.
     * @throws \Rych\Bencode\Exception\RuntimeException
     */
    private function decodeString()
    {
        if ('0' === $this->getChar() && ':' != $this->getChar($this->offset + 1)) {
            throw new RuntimeException('Illegal zero-padding in string entity length declaration at offset ' . $this->offset);
        }

        $offsetOfColon = strpos($this->source, ':', $this->offset);
        if (false === $offsetOfColon) {
            throw new RuntimeException('Unterminated string entity at offset ' . $this->offset);
        }

        $contentLength = (int) substr($this->source, $this->offset, $offsetOfColon);
        if (($contentLength + $offsetOfColon + 1) > $this->sourceLength) {
            throw new RuntimeException('Unexpected end of string entity at offset ' . $this->offset);
        }

        $value = substr($this->source, $offsetOfColon + 1, $contentLength);
        $this->offset = $offsetOfColon + $contentLength + 1;

        return $value;
    }

    /**
     * Decode a bencode list into a numeric array
     *
     * @return array Returns the decoded array.
     * @throws \Rych\Bencode\Exception\RuntimeException
     */
    private function decodeList()
    {
        $list = array ();
        $terminated = false;
        $listOffset = $this->offset;

        while (false !== $this->getChar()) {
            if ('e' == $this->getChar()) {
                $terminated = true;
                break;
            }

            $list[] = $this->doDecode();
        }

        if (!$terminated && false === $this->getChar()) {
            throw new RuntimeException('Unterminated list definition at offset ' . $listOffset);
        }

        ++$this->offset;

        return $list;
    }

    /**
     * Decode a bencode dictionary into an associative array
     *
     * @return array Returns the decoded array.
     * @throws \Rych\Bencode\Exception\RuntimeException
     */
    private function decodeDict()
    {
        $dict = array ();
        $terminated = false;
        $dictOffset = $this->offset;

        while (false !== $this->getChar()) {
            if ('e' == $this->getChar()) {
                $terminated = true;
                break;
            }

            $keyOffset = $this->offset;
            if (!ctype_digit($this->getChar())) {
                throw new RuntimeException('Invalid dictionary key at offset ' . $keyOffset);
            }

            $key = $this->decodeString();
            if (isset ($dict[$key])) {
                throw new RuntimeException('Duplicate dictionary key at offset ' . $keyOffset);
            }

            $dict[$key] = $this->doDecode();
        }

        if (!$terminated && false === $this->getChar()) {
            throw new RuntimeException('Unterminated dictionary definition at offset ' . $dictOffset);
        }

        ++$this->offset;

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
    private function getChar($offset = null)
    {
        if (null === $offset) {
            $offset = $this->offset;
        }

        if (empty ($this->source) || $this->offset >= $this->sourceLength) {
            return false;
        }

        return $this->source[$offset];
    }

}
