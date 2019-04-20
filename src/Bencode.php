<?php

namespace Rych\Bencode;

/**
 * Bencode class
 *
 * Provides static convenience methods for encoding and decoding bencode
 * encoded strings.
 */
class Bencode
{
    const TYPE_ARRAY = "array";
    const TYPE_OBJECT = "object"; // NOT IMPLEMENTED

    /**
     * Decode a bencode encoded string
     *
     * @param  string  $string The string to decode.
     * @param  string  $decodeType Flag used to indicate whether the decoded
     *   value should be returned as an object or an array.
     * @return mixed   Returns the appropriate data type for the decoded data.
     */
    public static function decode($string, $decodeType = self::TYPE_ARRAY)
    {
        return Decoder::decode($string, $decodeType);
    }

    /**
     * Encode a value into a bencode encoded string
     *
     * @param  mixed   $value The value to encode.
     * @return string  Returns a bencode encoded string.
     */
    public static function encode($value)
    {
        return Encoder::encode($value);
    }
}
