<?php

namespace Rych\Bencode;

/**
 * Bencode encoder class
 *
 * Encode values into bencode encoded strings.
 */
class Encoder
{
    /**
     * Value to encode
     *
     * @var mixed
     */
    private $data;

    /**
     * Encoder constructor
     *
     * @param  mixed   $data The value to encode.
     * @return void
     */
    private function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Encode a value into a bencode encoded string
     *
     * @param  mixed   $data The value to encode.
     * @return string  Returns the bencode encoded string.
     */
    public static function encode($data) : string
    {
        if (is_object($data)) {
            if (method_exists($data, "toArray")) {
                $data = $data->toArray();
            } else {
                $data = (array) $data;
            }
        }

        $encoder = new self($data);
        $encoded = $encoder->doEncode();

        return $encoded;
    }

    /**
     * Iterate over values and encode them
     *
     * @param  mixed   $data The value to encode.
     * @return string  Returns the bencode encoded string.
     */
    private function doEncode($data = null) : string
    {
        $data = is_null($data) ? $this->data : $data;

        if (is_array($data) && (isset($data[0]) || empty($data))) {
            return $this->encodeList($data);
        } elseif (is_array($data)) {
            return $this->encodeDict($data);
        } elseif (is_integer($data) || is_float($data)) {
            $data = sprintf("%.0f", round($data, 0));
            return $this->encodeInteger($data);
        } else {
            return $this->encodeString($data);
        }
    }

    /**
     * Encode an integer
     *
     * @param  integer $data The integer to be encoded.
     * @return string  Returns the bencode encoded integer.
     */
    private function encodeInteger(int $data = null) : string
    {
        $data = is_null($data) ? $this->data : $data;
        return sprintf("i%.0fe", $data);
    }

    /**
     * Encode a string
     *
     * @param  string  $data The string to be encoded.
     * @return string  Returns the bencode encoded string.
     */
    private function encodeString(string $data = null) : string
    {
        $data = is_null($data) ? $this->data : $data;
        return sprintf("%d:%s", strlen($data), $data);
    }

    /**
     * Encode a list
     *
     * @param  array   $data The list to be encoded.
     * @return string  Returns the bencode encoded list.
     */
    private function encodeList(array $data = null) : string
    {
        $data = is_null($data) ? $this->data : $data;

        $list = "";
        foreach ($data as $value) {
            $list .= $this->doEncode($value);
        }

        return "l{$list}e";
    }

    /**
     * Encode a dictionary
     *
     * @param  array   $data The dictionary to be encoded.
     * @return string  Returns the bencode encoded dictionary.
     */
    private function encodeDict(array $data = null) : string
    {
        $data = is_null($data) ? $this->data : $data;
        ksort($data); // bencode spec requires dicts to be sorted alphabetically

        $dict = "";
        foreach ($data as $key => $value) {
            $dict .= $this->encodeString($key) . $this->doEncode($value);
        }

        return "d{$dict}e";
    }
}
