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

/**
 * Rych Bencode Component
 *
 * @package Rych\Bencode
 * @author Ryan Chouinard <rchouinard@gmail.com>
 * @copyright Copyright (c) 2014, Ryan Chouinard
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 */
class Bencode
{

    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object'; // NOT IMPLEMENTED

    /**
     * Decodes a bencoded string
     *
     * @param string $string The bencoded string to decode.
     * @param string $decodeType Flag used to indicate whether the decoded
     *     value should be returned as an object or an array.
     * @return mixed Returns the appropriate data type for the bencoded data.
     */
    public static function decode($string, $decodeType = self::TYPE_ARRAY)
    {
        return Decoder::decode($string, $decodeType);
    }

    /**
     * Encodes a value into a bencoded string
     *
     * @param mixed $value The value to bencode.
     * @return string Returns a bencoded string.
     */
    public static function encode($value)
    {
        return Encoder::encode($value);
    }

}
