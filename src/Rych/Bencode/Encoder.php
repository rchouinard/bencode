<?php

namespace Rych\Bencode;

class Encoder
{

    protected $_data;

    protected function __construct($data)
    {
        $this->_data = $data;
    }

    static public function encode($data)
    {
        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                $data = $data->toArray();
            } else {
                $data = (array) $data;
            }
        }

        $encoder = new self($data);
        return $encoder->_encode();
    }

    protected function _encode($data = null)
    {
        $data = is_null($data) ? $this->_data : $data;

        if (is_array($data) && (isset ($data[0]) || empty ($data))) {
            return $this->_encodeList($data);
        } else if (is_array($data)) {
            return $this->_encodeDict($data);
        } else if (is_numeric($data)) {
            $data = sprintf('%.0f', round($data, 0));
            return $this->_encodeInteger($data);
        } else {
            return $this->_encodeString($data);
        }
    }

    protected function _encodeInteger($data = null)
    {
        $data = is_null($data) ? $this->_data : $data;
        return sprintf('i%se', $data);
    }

    protected function _encodeString($data = null)
    {
        $data = is_null($data) ? $this->_data : $data;
        return sprintf('%d:%s', strlen($data), $data);
    }

    protected function _encodeList($data = null)
    {
        $data = is_null($data) ? $this->_data : $data;

        $list = '';
        foreach ($data as $item) {
            $list .= self::encode($item);
        }
        return "l{$list}e";
    }

    protected function _encodeDict($data = null)
    {
        $data = is_null($data) ? $this->_data : $data;
        ksort($data);

        $dict = '';
        foreach ($data as $key => $value) {

            $key = $this->_encodeString($key);
            $value = $this->_encode($value);

            $dict .= "{$key}{$value}";
        }

        return "d{$dict}e";
    }

}
