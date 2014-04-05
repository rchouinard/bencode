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
 * Bencode encoder
 *
 * @package Rych\Bencode
 * @author Ryan Chouinard <rchouinard@gmail.com>
 * @copyright Copyright (c) 2014, Ryan Chouinard
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 */
class Encoder
{

    /**
     * @var mixed Entity to be encoded.
     */
    private $data;

    /**
     * Class constructor
     *
     * @param mixed $data Entity to be encoded.
     * @return void
     */
    private function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Encode a value into a bencode entity
     *
     * @param mixed $data The value to be encoded.
     * @return string Returns the bencoded entity.
     */
    public static function encode($data)
    {
        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                $data = $data->toArray();
            } else {
                $data = (array) $data;
            }
        }

        $encoder = new self($data);
        return $encoder->doEncode();
    }

    /**
     * Encode a value into a bencode entity
     *
     * @param mixed $data The value to be encoded.
     * @return string Returns the bencoded entity.
     */
    private function doEncode($data = null)
    {
        $data = is_null($data) ? $this->data : $data;

        if (is_array($data) && (isset ($data[0]) || empty ($data))) {
            return $this->encodeList($data);
        } elseif (is_array($data)) {
            return $this->encodeDict($data);
        } elseif (is_integer($data) || is_float($data)) {
            $data = sprintf('%.0f', round($data, 0));
            return $this->encodeInteger($data);
        } else {
            return $this->encodeString($data);
        }
    }

    /**
     * Encode an integer into a bencode integer
     *
     * @param integer $data The integer to be encoded.
     * @return string Returns the bencoded integer.
     */
    private function encodeInteger($data = null)
    {
        $data = is_null($data) ? $this->data : $data;
        return sprintf('i%.0fe', $data);
    }

    /**
     * Encode a string into a bencode string
     *
     * @param string $data The string to be encoded.
     * @return string Returns the bencoded string.
     */
    private function encodeString($data = null)
    {
        $data = is_null($data) ? $this->data : $data;
        return sprintf('%d:%s', strlen($data), $data);
    }

    /**
     * Encode a numeric array into a bencode list
     *
     * @param array $data The numerically indexed array to be encoded.
     * @return string Returns the bencoded list.
     */
    private function encodeList(array $data = null)
    {
        $data = is_null($data) ? $this->data : $data;

        $list = '';
        foreach ($data as $value) {
            $list .= $this->doEncode($value);
        }

        return "l{$list}e";
    }

    /**
     * Encode an associative array into a bencode dictionary
     *
     * @param array $data The associative array to be encoded.
     * @return string Returns the bencoded dictionary.
     */
    private function encodeDict(array $data = null)
    {
        $data = is_null($data) ? $this->data : $data;
        ksort($data); // bencode spec requires dicts to be sorted alphabetically

        $dict = '';
        foreach ($data as $key => $value) {
            $dict .= $this->encodeString($key) . $this->doEncode($value);
        }

        return "d{$dict}e";
    }

}
